<?php
/**
 * This is our main WRP_Hooks class
 */

//Exit on unecessary access
defined('ABSPATH') or exit;

class WRP_Hooks extends WRP_Main {

    //Main construct
    public function __construct(){

        /**
         * List of action hooks
         * #########################
         * #########################
         */
        add_action('wp_enqueue_scripts', array($this, 'wrp_scripts_enqueue_callback'));
        add_action('admin_enqueue_scripts', array($this, 'wrp_admin_scripts_enqueue_callback'));
        add_action('wp_head', array($this, 'wrp_header_callback'));

        //Add back the ratings template wrapper because it was removed by the 10 priority number from the wp_head callback function above
        add_action('woocommerce_single_product_summary', 'woocommerce_template_single_rating', 11);
        add_action('woocommerce_before_add_to_cart_quantity', array($this, 'wrp_render_rental_prices_single'));
        add_action('woocommerce_product_options_pricing', array($this, 'wrp_product_options_pricing_clbck'));
        add_action('woocommerce_before_shop_loop_item', array($this, 'wrp_woocommerce_before_shop_loop_item'));
        add_action('woocommerce_after_shop_loop_item', array($this, 'wrp_woocommerce_after_shop_loop_item'));
        add_action('manage_product_posts_custom_column', array($this, 'wrp_admin_product_posts_column'), 11, 2);
        
        //Action hook for saving custom meta value for product rental prices
        add_action('save_post', array($this, 'wrp_save_rental_product_clbck'), 10, 1);

        //Action hooks for custom cart content alterations
        add_action('woocommerce_cart_contents', array($this, 'wrp_cart_contents'));
        add_action('woocommerce_before_calculate_totals', array($this, 'wrp_before_calculate_totals'));

        //Action hooks for custom order details alterations
        add_action('woocommerce_add_order_item_meta', array($this, 'wrp_add_order_item_meta'), 10, 2);
        //add_action('woocommerce_new_order_item', array($this, 'wrp_new_order_item'), 10, 3);
        add_action('woocommerce_order_item_meta_start', array($this, 'wrp_order_item_meta_start'), 10, 3);

        //Action hooks for displaying order details in the WP Dashboard
        add_action('woocommerce_before_order_itemmeta', array($this, 'wrp_before_order_itemmeta'), 10, 3);

        /**
         * List of filter hooks
         * #########################
         * #########################
         */
        add_filter('product_type_options', array($this, 'wrp_product_type'), 10, 1);

        //Filter hook to show off the rental prices even if the standard product value is empty
        add_filter('woocommerce_is_purchasable', array($this, 'wrp_is_purchasable'), 30, 2);

        //Filter hook to do something about rental products added to the cart with normal products added
        add_filter('woocommerce_cart_item_visible', array($this, 'wrp_cart_item_visible'), 10, 3);

        //Filter hooks for cart metadata alterations
        add_filter('woocommerce_add_to_cart_validation', array($this, 'wrp_add_to_cart_validation'), 10, 3);
        add_filter( 'woocommerce_add_cart_item_data', array($this, 'wrp_add_cart_item_data'), 10, 2);
        add_filter('woocommerce_get_cart_item_from_session', array($this, 'wrp_get_cart_item_from_session'), 10, 3);
        add_filter('woocommerce_cart_item_price', array($this, 'wrp_cart_item_price'), 10, 3);
        add_filter('woocommerce_get_item_data', array($this, 'wrp_get_item_data'), 10, 2);
        add_filter( 'woocommerce_loop_add_to_cart_link',array( $this, 'wrp_custom_loop_add_to_cart' ), 10, 2 );
        add_filter( 'woocommerce_is_sold_individually', array( $this,'wrp_remove_all_quantity_fields'), 10, 2 );  

        //Action hook for API call on user date selection event.
        add_action( 'wp_ajax_add_new_price', array( $this, 'wrp_get_new_price' ) );
        add_action( 'wp_ajax_nopriv_add_new_price', array( $this, 'wrp_get_new_price' ) );
    }

    /**
     * Callback for any wp_head hook codes
     */
    final public function wrp_header_callback(){

        global $post;

        //Do anything for products
        if( $post->post_type = 'product' ){

            /**
             * Check if rental prices are set including the boolean variable
             * Note: This applies to single product page
             */
            if( $this->is_rental_product($post->ID) ){

                //Remove the sale price in the current product page
                remove_action('woocommerce_single_product_summary', 'woocommerce_template_single_price', 10);

            }

        }

        //Include the Date Range Picker lib
        ?>
        
        <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
        <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
        <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

        <?php

    }

    /**
     * Callback for any script enqueue codes in admin
     */
    final public function wrp_admin_scripts_enqueue_callback(){
        //Add admin css
        wp_enqueue_style('wrp-stylesheet-admin', WRP_ABSURL . 'assets/css/admin.css', array(), WRP_VERSION);
    }

    
    /**
     * Callback for any script enqueue codes in public
     */
    final public function wrp_scripts_enqueue_callback(){

        //Add the WRP stylesheet
        wp_enqueue_style('wrp-stylesheet-public', WRP_ABSURL . 'assets/css/style.css', array(), WRP_VERSION);

        //Add the Wordpress Dashicons
        wp_enqueue_style( 'dashicons' );

        //Add the WRP script js file
        // wp_enqueue_script('wrp-script-public', WRP_ABSURL . 'assets/js/script.js', array(), WRP_VERSION, true);
        // wp_localize_script( 'wrp-script-public', 'ajax_obj', array( 'ajaxurl' => admin_url( 'admin-ajax.php' )));
        wp_register_script( 'wrp-script-public', WRP_ABSURL . 'assets/js/script.js', array(),WRP_VERSION, true );
        wp_localize_script( 'wrp-script-public', 'ajax_obj', array( 'ajax_url' => admin_url( 'admin-ajax.php' )));
        // wp_localize_script('WRP', 'ajax_object',array(
        //         'ajax_url' => esc_url( admin_url( 'admin-ajax.php' ) ),
        //         'hide_notice_nonce' => wp_create_nonce( 'hide-notice' )
        //     )
        // );
         wp_enqueue_script( 'wrp-script-public' );

    }

    

    /**
    *
    * Adds a custom text link on product archive page
    * param str $content - Current text
    * param WC_Product $product
    * return str $content - Custom or current text
    */

   final public function wrp_custom_loop_add_to_cart( $content, $product ) {

        if ( ! $product ) return;

        if( $this->is_rental_product($product->id) ){

            $product_id = is_callable( array( $product, 'get_id' ) ) ? $product->get_id() : $product->id;

            $link    = get_permalink( $product_id );
            $label   = apply_filters( 'wrp_product_add_to_cart_text','Select date(s)', $product );
            $content = apply_filters(
                'wrp_loop_add_to_cart_link',
                '<a href="' . esc_url( $link ) . '" rel="nofollow" class="button">' . esc_html( $label  ) . '</a>',
                $product
            );
            
        }
        
        return $content;
    }

    /** @Hide from different product type group */
    final public function wrp_remove_all_quantity_fields( $return, $product ) {
        if( $this->is_rental_product($product->id) ){
            return true;
        }
    }   

    /**
     * Callback method for the action hook after the:
     * @hooked woocommerce_template_single_rating - 10
     * @hooked woocommerce_template_single_price - 10
     * See * Hook: woocommerce_single_product_summary in WooCommerce
     */
    final public function wrp_render_rental_prices_single(){
        global $post;

        //Check if rental prices are set including the boolean variable
        if( $this->is_rental_product($post->ID) ){
            $data = get_post_meta($post->ID, '_rent_prices', true);
            include_once WRP_TEMPLATE_DIR . 'content-product-rental-prices.php';
        }
        
    }

    /**
     * Callback method for product loops targetting the action hook: woocommerce_before_shop_loop_item
     */
    final  public function wrp_woocommerce_before_shop_loop_item(){

        global $product;

        /**
         * Check if rental prices are set including the boolean variable
         * Note: This applies to each product loop item
         */
        if( $this->is_rental_product($product->get_id()) ){

            //Remove the sale price in the current product loop item
            remove_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);

        }

    }

    /**
     * Callback method for product loops targetting the action hook: woocommerce_after_shop_loop_item
     */
    final public function wrp_woocommerce_after_shop_loop_item(){

        global $product;

        /**
         * Check if rental prices are set including the boolean variable
         * Note: This applies to each product loop item
         */
        if( $this->is_rental_product($product->get_id()) ){
            $data = get_post_meta($product->get_id(), '_rent_prices', true);
            $first_item = true; //Set this to true to only show the first item of the loop
            include WRP_TEMPLATE_DIR . 'content-product-rental-prices.php';
        }

        //Add back the price for non-rental products
        add_action('woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10);
        
    }

    /**
     * Callback method for adding product type (checkbox) on simple products
     * @param product_type_options
     * see get_product_type_options() method from WooCommerce plugin
     */
    final public function wrp_product_type(array $array): array {
        $array['rental'] = array(
            'id'            => '_rental',
            'wrapper_class' => 'show_if_simple',
            'label'         => __( 'Rental', 'woocommerce' ),
            'description'   => __( 'Rental products can be purchased by period options.', 'woocommerce' ),
            'default'       => 'no',
        );
        return $array;
    }

     /**
     * Callback for filter method on product post type column viewable in WP Dashboard -> All products
     */
    final public function wrp_get_new_price(){
        $endpoint = get_option('endpoint_url'); 
        $post_url = $endpoint.'/calcPrices';  
        extract($_POST);
        $start_arr  = explode(' ', $startDate);
        $start          =   $start_arr[0].'T'.$start_arr[1];

        $end_arr  = explode(' ', $endDate);
        $end          =   $end_arr[0].'T'.$end_arr[1];

        $body = array( 'items' => array(array('start' => $start,
                        'end'=>$end,
                        'sku' => $product_sku,
                        'qty' => '1')));

        $request = new WP_Http();
        $response = $request->post( $post_url, array( 'body' => json_encode($body) ) ); 

        // $response['response']['code'] = 200;
        if($response['response']['code'] == 200){
            // process responce here..
        /* $response['body'] ='{
                          "items": [
                            {
                              "start": "2019-05-02T05:00:00.000Z",
                              "end": "2019-05-07T11:59:00.000Z",
                              "sku": "GD3",
                              "qty": 1,
                              "dur": 13.00,
                              "periodCode": "Day",
                              "periodName": "Day",
                              "amount": 195.00,
                              "avQty": 5
                            }
                          ]
                        }';*/
            
            $price_array = json_decode($response['body']);
            $rentalPrice = $price_array->items[0];
    
            echo '<p class="rental_details">Total Rental duration: '.$rentalPrice->dur.' '.$rentalPrice->periodName.'</p>
                 <p class="rental_price">'.wc_price($rentalPrice->amount).'</p>'; 
        }
        else{
            echo 'Somthing went wrong Please try again later.';
        }
        die; //preventing from returning 0 in as result.
    }

    /**
     * Callback for filter method on product post type column viewable in WP Dashboard -> All products
     */
    final public function wrp_admin_product_posts_column($column, $id){

        /**
         * Check if rental prices are set including the boolean variable
         * Note: This applies to each product loop item
         */
        if( $this->is_rental_product($id) && $column == 'price' ):
            $data = get_post_meta($id, '_rent_prices', true);
            $first_item = true; //Set this to true to only show the first item of the loop
            include_once WRP_TEMPLATE_DIR . 'content-product-rental-prices.php';
            ?>
            <style>
                tr#post-<?php echo $id; ?> td span.woocommerce-Price-amount {
                    display: none;
                }
                tr#post-<?php echo $id; ?> td div.wrp_content_product_rental_prices span.woocommerce-Price-amount {
                    display: block;
                }
                tr#post-<?php echo $id; ?> td div.wrp_content_product_rental_prices del span.woocommerce-Price-amount {
                    opacity: 0.5;
                }
            </style>
        <?php endif;

    }

    /**
     * Callback method for adding HTML contents right below the product pricing options for simple products
     */
    final public function wrp_product_options_pricing_clbck(){
        include_once WRP_TEMPLATE_DIR . 'admin/product-options-pricing.php';
    }

    /**
     * Callback method for saving product rental prices from the product editor
     */
    final public function wrp_save_rental_product_clbck($post_id){

        if( isset($_POST['_rental']) ){
            update_post_meta( $post_id, '_rental', 'yes' );
        } else {
            update_post_meta( $post_id, '_rental', 'no' );
        }

        if(isset($_POST['_rent_prices'])){
            update_post_meta( $post_id, '_rent_prices', $this->validated_rental_prices( $this->format_rental_prices_added( $_POST['_rent_prices'] ) ) );
        }
        
    }

    /**
     * Callback method for showing off additional cart contents
     */
    final public function wrp_cart_contents(){

        //Define counter
        $counter = 0;

        //Loop through each product items from the cart
        foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

            //Check if they are rental products
            if( $this->is_rental_product($cart_item['product_id']) ){

                //Get date range whether it was set or is empty
                // $wrp_date_range = $cart_item['wrp_date_range'];

                //Set date range
                $date_start = (!empty($wrp_date_range) && !empty($wrp_date_range['date_start'])) ? $wrp_date_range['date_start'] : '';
                $date_end = (!empty($wrp_date_range) && !empty($wrp_date_range['date_end'])) ? $wrp_date_range['date_end'] : '';


                //Render the date range content
                // if( $counter == 0 ){

                   /* echo '
                    <tr class="wrp_date_range_row">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td><b>Set Date</b></td>
                        <td>
                            <span class="dashicons dashicons-calendar-alt"></span>
                            <input id="wrp_date_range" type="text" name="wrp_date_range" placeholder="From -- To" autocomplete="off"/>
                            <input type="hidden" name="wrp_date_start" value="' . $date_start . '"/>
                            <input type="hidden" name="wrp_date_end" value="' . $date_end . '"/>
                        </td>
                    </tr>
                    ';*/

                    // $counter++;

                // }

                //Get the product data
                $_product   = apply_filters( 'woocommerce_cart_item_product', $cart_item['data'], $cart_item, $cart_item_key );
                $product_id = apply_filters( 'woocommerce_cart_item_product_id', $cart_item['product_id'], $cart_item, $cart_item_key );

                //Now begin rendering the rental products the Woocommerce way
                if($_product && $_product->exists() && $cart_item['quantity'] > 0 ){
                    include WRP_TEMPLATE_DIR . 'cart-rental-products.php';
                }
                
            }

        }

    }

    /**
     * Filter method for changing visibility on rental products added to the cart with normal products added
     */
    final public function wrp_cart_item_visible($boolean, $cart_item, $cart_item_key){

        //Check if they are rental products
        if( $this->is_rental_product($cart_item['product_id']) ){
            return false;
        }

       return $boolean;

    }

    /**
     * Filter hook to validate a cart item in the cart
     * @param boolean
     * @param product_id
     * @param quantity
     */
    final public function wrp_add_to_cart_validation( $passed, $product_id, $quantity ){

        //Only for rental product types
        if( $this->is_rental_product($product_id) ){

            //Check if rental price plan is choosen. Otherwise, throw an error.
            if( empty($_POST['wrp_date_range']) ){
                wc_add_notice('Please make a selection.', 'error');
                return false;
            }

        }

        return $passed;

    }

    /**
     * Filter hook to store each custom product data as part of the cart item data session
     * @param cart_item_data
     * @param product_id
     */
    final public function wrp_add_cart_item_data( $cart_item_data, $product_id ){
        // echo "<pre>";print_r($cart_item_data);echo "</pre>";
        //Only for rental product types
        if( $this->is_rental_product($product_id) ){
            $cart_item_data['wrp_date_range']['date_start'] = $_POST['wrp_date_start'];
            $cart_item_data['wrp_date_range']['date_end'] = $_POST['wrp_date_end'];

            $cart_item_data['date_start'] = $_POST['wrp_date_start'];
            $cart_item_data['date_end'] = $_POST['wrp_date_end'];

        }

        return $cart_item_data;

    }

    /**
     * Filter hook to get each of the stored custom product data session and add it to the cart object
     * @param session_data
     * @param values
     * @param key
     */
    final public function wrp_get_cart_item_from_session( $session_data, $values, $key ){

        //Store the rental price session to the cart object
        // if( $this->is_rental_product($values['product_id']) && array_key_exists('rental_period_code', $values) && !isset($_POST['wrp_date_range']) && !isset($_POST['wrp_date_start']) && !isset($_POST['wrp_date_end']) ){
        //     $session_data['rental_period_code'] = $values['rental_period_code'];
        // }

        //Do this for cart update scenario
        if( $this->is_rental_product($values['product_id']) && isset($_POST['wrp_date_range']) && isset($_POST['wrp_date_start']) && isset($_POST['wrp_date_end']) ){
            // $session_data['wrp_date_range'] = array(
            //     'date_start' => sanitize_text_field($_POST['wrp_date_start']),
            //     'date_end' => sanitize_text_field($_POST['wrp_date_end'])
            // );

            $session_data['date_start'] = sanitize_text_field($_POST['wrp_date_start']);
            $session_data['date_end'] = sanitize_text_field($_POST['wrp_date_end']);
        }

        return $session_data;

    }

    /**
     * Action hook for overriding the rental product price in the cart
     * @param cart_object
     */
    final public function wrp_before_calculate_totals($cart_object){

        //Loop each cart items from the cart object
        foreach ( $cart_object->cart_contents as $key => $value ) {
            
            //Only do this for rental products
            if( $this->is_rental_product($value['product_id']) ){

                //Get the rental price using flow2b API
                $rental_price = $this->call_rental_price_api($value);

                //Check if rental price array is empty
                if(empty($rental_price->amount)){
                    return;
                }

                //Default price value
                $price = 0;

                //For price
                if( !empty($rental_price->amount) ){
                    $price = $rental_price->amount;
                }

                //Set and override the product price
                $value['data']->set_price($price);

                //Do the cart session filter hook again for changing some data during cart update
                add_filter('woocommerce_get_cart_item_from_session', array($this, 'wrp_get_cart_item_from_session'), 10, 3);

            }

        }

    }

    /**
     * Filter hook to change the price displayed on rental products
     * @param price
     * @param cart_item
     * @param cart_item_key
     */
    final public function wrp_cart_item_price($price, $cart_item, $cart_item_key){

        //Only do this for rental products
        if($this->is_rental_product($cart_item['product_id'])){

             //Get rental price array
            $rental_price_api_res = $this->call_rental_price_api($cart_item); 

            if(!empty($rental_price_api_res)){
                return '<span> ' .wc_price($rental_price_api_res->amount/$rental_price_api_res->dur) . '</span>';
            }            
          
        }
        
        return $price;
        
    }

    /**
     * Filter hook to add additional metadata in the cart and checkout page
     * @param item_data
     * @param cart_item
     */
    final public function wrp_get_item_data($item_data, $cart_item){
        
        //Do this for rental products
        if($this->is_rental_product($cart_item['product_id'])){

            if(isset($cart_item['wrp_date_range'])){
                $wrp_date_range = $cart_item['wrp_date_range'];
                $start = $cart_item['wrp_date_range']['date_start'];
                $end =  $cart_item['wrp_date_range']['date_end'];
            }else{
                $start = date('Y-m-d  h:i A') ;
                $end = date("Y-m-d", strtotime("+ 1 day"));
            }
            
            $rental_price_api_res = $this->call_rental_price_api($cart_item);
            $rate =  $rental_price_api_res->amount / $rental_price_api_res->dur;

            //Set the key and value for rental rate
            $item_data[] = array(
                'key' => 'Rate',
                'value' => '$'.$rate . ' / ' . $rental_price_api_res->periodName
            );

            //Set key and value for Duration
            $item_data[] = array(
                'key' => 'Duration',
                'value' => ''.$rental_price_api_res->dur . ' ' . $rental_price_api_res->periodName
            );      

            //Set key and value for Date Start
            $item_data[] = array(
                'key' => 'From',
                // 'value' => date('M d, Y h:i A', strtotime($cart_item['date_start']))
                'value' => date('M d, Y h:i A', strtotime($rental_price_api_res->start))
            );

            //Set key and value for Date End
            $item_data[] = array(
                'key' => 'To',
                // 'value' => date('M d, Y h:i A', strtotime($cart_item['date_end']))
                'value' => date('M d, Y h:i A', strtotime($rental_price_api_res->end))
            );                     

        }

        return $item_data;

    }

    /**
     * Action hook to add custom metadata to the order item
     * @param item_id
     * @param value
     */
    final public function wrp_add_order_item_meta( $item_id, $value){
        //Only do this for rental products with wrp_date_range metakey
        $wrp_date_range = $value['wrp_date_range'];
        if(!empty($wrp_date_range) && $this->is_rental_product($value['product_id'])){
            $wrp_date_range['product_id'] = $value['product_id'];
            // $wrp_date_range['rental_price_array'] = $this->get_rental_price($value['product_id'], $value['rental_period_code']);
            $wrp_date_range['rental_price_array'] = $this->call_rental_price_api($value);
            wc_add_order_item_meta($item_id, 'wrp_date_range', $wrp_date_range);
        }

    }

    /**
     * Action hook to add custom metadata to the order details during order post
     * @param item_id
     * @param item
     * @param order_id
     */
    final public function wrp_new_order_item($item_id, $item, $order_id){

        //Get all the data for each of the order items
        $item_data = $item->get_data();

        //Get the cart contents
        foreach(WC()->cart->get_cart() as $product_item){
            //echo '<pre>';
            //var_dump($product_item);
            //echo '</pre>';
        }

    }

    /**
     * Action hook for displaying custom metadata in the order details
     * @param item_id
     * @param item
     * @param order
     */
    final public function wrp_order_item_meta_start($item_id, $item, $order){
        // echo "wrp_order_item_meta_start";
        $product_id = $item->get_data()['product_id'];
        //Only do this for rental products
        if(!$this->is_rental_product( $product_id )){
            return;
        }

        //Loop through each of the metadata
        foreach($item->get_meta_data() as $product_metadata){

            //Get the data from the metadata object
            $product_data = $product_metadata->get_data();

            //Do this if the product data exists and only for wrp_date_range data key
            if(!empty($product_data) && $product_data['key'] == 'wrp_date_range'){

                //Get the product metadata
                $product_data_metadata = $product_data['value'];

                //Render the rental product metadata
                echo $this->render_rental_metadata($product_id,$product_data_metadata);

            }

        }

    }

    /**
     * Action hook for displaying order metadata in the order page inside the WP Dashboard
     * @param item_id
     * @param item
     * @param product
     */
    final public function wrp_before_order_itemmeta($item_id, $item, $product){
        // echo "wrp_before_order_itemmeta"; 
   // https://arboleaf.com  
         $product_id = $item->get_data()['product_id'];

        //Get the rental products order item metadata
        $rental_price_meta = wc_get_order_item_meta($item_id, 'wrp_date_range');

        //Render the rental product metadata
        echo $this->render_rental_metadata($product_id,$rental_price_meta);

    }

    /**
     * Filter hook to deal with rental products configured with empty standard price (before selecting rental checkbox)
     * @param boolean
     * @param product
     */
    final public function wrp_is_purchasable($boolean, $product){

        //For rental products
        if( $this->is_rental_product($product->get_id()) ){
            return true;
        }

        return $boolean;

    }

}

return new WRP_Hooks;
