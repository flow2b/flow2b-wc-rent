<?php
/**
 * This is our main WRP_Main class
 */

//Exit on unecessary access
defined('ABSPATH') or exit;

//Our main class that is extendable but methods cannot be overriden
class WRP_Main {
	
	//A single instance of the class WRP_Main
	protected static $wrp_instance = null;
	
	//JDS Instance ensuring that only 1 instance of the class is loaded
	final public static function instance($version){
		if(is_null(self::$wrp_instance)){
			self::$wrp_instance = new self($version);
		}
		return self::$wrp_instance;
	}
	
	//Cloning is forbidden
	public function __clone() {
		$error = new WP_Error('forbidden', 'Cloning is forbidden.');
		return $error->get_error_message();
	}
	
	//Unserializing instances of this class is forbidden.
	public function __wakeup() {
		$error = new WP_Error('forbidden', 'Unserializing instances of this class is forbidden.');
		return $error->get_error_message();
    }

    //Main construct
    public function __construct($version){
        register_activation_hook( WRP_PLUGIN_FILE, array( $this , 'activate' ) );
        $this->version = $version;
        $this->wrp_constants();
        $this->wrp_includes();
    }

    //Method to check if PHP is version 7.2.0 and above
    final public function activate() {
	
		//Check PHP Version and deactivate & die if it doesn't meet minimum requirements.
		if ( version_compare(PHP_VERSION, '7.2.0', '<=') ) {
			deactivate_plugins( plugin_basename( WRP_PLUGIN_FILE ) );
			wp_die( 'This plugin requires <b>PHP Version 7.2 and up</b>. <a href="' . admin_url('plugins.php') . '">Go Back</a>' );
        }
        
        //Check if WooCommerce is activated. Otherwise, do not activate the plugin.
        if( !is_plugin_active('woocommerce/woocommerce.php') ) {
            deactivate_plugins( plugin_basename( WRP_PLUGIN_FILE ) );
			wp_die( 'This plugin requires <b>WooCommerce<b>. <a href="' . admin_url('plugins.php') . '">Go Back</a>' );
        }
		
        //Do activate Stuff now...
        
	}

    //Define the constants
    final public function wrp_constants(){
		$this->define('WRP_ABSPATH', plugin_dir_path(WRP_PLUGIN_FILE));
		$this->define('WRP_ABSURL', plugin_dir_url(WRP_PLUGIN_FILE));
		$this->define('WRP_VERSION', $this->version);
		$this->define('WRP_TEMPLATE_DIR', plugin_dir_path(WRP_PLUGIN_FILE) . 'templates/');
    }

    //Method to defining constants if it's not set
	final public function define( $name, $value ) {
		if ( ! defined( $name ) ) {
			define( $name, $value );
		}
    }

    //Include the files to be used
    public function wrp_includes(){

        //Our test class
		//include_once WRP_ABSPATH . 'includes/class-wrp-test.php';
		
		//REST API extension class
		include_once WRP_ABSPATH . 'includes/class-wrp-rest.php';

		//Our hooks class
		include_once WRP_ABSPATH . 'includes/class-wrp-hooks.php';

	}
	
	/**
	 * Begin public methods
	 */

	/**
	 * Method for checking formatted multi-dimensional array for Rental Products meta
	 * Format:
		array(
			'regular_price' => '$10',
			'sale_price' => '0',
			'period_code' => 'daily',
			'period_name' => 'Daily'
		)
	 * Return type: bool
	*/
	final public function is_rental_prices_formatted(array $array): bool {

		//Remove any character strings
		$array['regular_price'] = floatval( $array['regular_price'] );
		$array['sale_price'] = floatval( $array['sale_price'] );

		if( empty($array['regular_price']) && empty($array['sale_price']) ){
			return false;
		}
		if( empty($array['period_code']) && empty($array['period_name']) ){
			return false;
		}
		return true;
	}

	/**
	 * Method for formatting rental prices that was added by the format:
		array(
			array(
				'regular_price' => '$10',
			),
			array(
				'sale_price' => '$10',
			),
			array(
				'period_name' => '$10',
			)
		)
	 */
	final public function format_rental_prices_added(array $data): array {
		if( !empty($data) ){
			//Define counter
			$counter = 0;
			//Container for a formatted array
			$array = array();
			foreach($data as $value){
				if( count($value) == 1 ){
					$array[key($value)] = $value[key($value)];
					$counter++;
				}
				if($counter == 3){
					$data = array_merge($data, array($array));
					$counter = 0;
				}
			}
			return $data;
		}
		return $data;
	}

	/**
	 * Return a validated rent_prices array
	 * @param rent_prices
	 */
	final public function validated_rental_prices(array $data): array {

		//Check if array is not empty
		if( !empty($data) ){

			//Normalize indexes
			$data = array_values( $data );

			//Loop through the elements
			foreach($data as $index => $array){
				if( $this->is_rental_prices_formatted($array) ){
					$array['regular_price'] = floatval( $array['regular_price'] );
					$array['sale_price'] = floatval( $array['sale_price'] );

					//Expected ouput input: "period code" output: "period-code"
					$array['period_code'] = sanitize_title( $array['period_code'] );

					//Set with period name if no period code is provided
					if( empty( $array['period_code'] ) ){
						$array['period_code'] = sanitize_title( $array['period_name'] );
					}

					$array['period_name'] = ucwords( sanitize_text_field( $array['period_name'] ) ); //every word in upper case
					$data[$index] =  $array;
				} else {
					unset($data[$index]);
				}
			}

			//Normalize indexes for possible unset
			$data = array_values( $data );

		}

		return $data;

	}

	/**
	 * Method for formatting Rental Product price table
	 * Return type: string
	 * @param rent_prices
	 * @param first_item
	 */
	final public function format_rental_price_table($data, bool $first_item = false): string {

		//Check if data is empty
		if( empty($data) || !is_array($data) ){
			return '<p>Rental Product is not configured properly.</p>';
		}

		//Validate the format of the rent_prices array
		$data = $this->validated_rental_prices($data);

		//Define the string
		$string = '';

		//Begin formatting to HTML
		if( !empty($data) ){

			$string = '<ul class="rental_option_list">';

			//Begin iterables
			foreach($data as $index => $value){

				//Default price value
				$price = 0;
				$price_dropped = 0;

				//Get the rental prices
				$regular_price = $value['regular_price'];
				$sale_price = $value['sale_price'];

				//For regular price
				if( !empty($regular_price) ){
					$price = $regular_price;
				}

				//When regular price and sale price are both present
				if( !empty($regular_price) && !empty($sale_price) ){
					$price = $sale_price;
					$price_dropped = $regular_price;
				}

				//Only render non-zero prices
				if( !empty( $price ) ){

					//Do this if we want to only show the first item of the loop
					if( $first_item ){
						$string .= '
							<li>
								<span class="price">' . ((!empty($price_dropped)) ? '<del>' . wc_price( $price_dropped ) . '</del> ' : '') . wc_price( $price ) . ' ' . $value['period_name'] . '</span>
							</li>
						';
						 break;
					}

					$string .= '
						<li>
							<label>
								<input type="radio" name="rental_price" value="' . $value['period_code'] . '"/>
								<span class="price">' . ((!empty($price_dropped)) ? '<del>' . wc_price( $price_dropped ) . '</del> ' : '') . wc_price( $price ) . '</span> ' . $value['period_name'] . '
							</label>
						</li>
					';
				}

			}

			$string .= '</ul>';

		}

		return $string;

	}

}