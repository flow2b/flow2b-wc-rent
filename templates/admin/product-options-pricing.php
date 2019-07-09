<?php
/**
 * This is the admin template for the product pricing options in the product editor page.
 */

//Exit on unecessary access
defined('ABSPATH') or exit;

global $thepostid;

//Get product meta values
//$test_rental_prices = get_post_meta($thepostid, '_test_rental_prices', true);
$rental_prices = get_post_meta($thepostid, '_rent_prices', true);
?>

<div class="form-field rental_prices_fields">
    <label><?php esc_html_e( 'Rental Price Options', 'woocommerce' ); ?></label>
    <table class="widefat">
        <thead>
            <tr>
                <th class="sort">&nbsp;</th>
                <th><?php esc_html_e( 'Rental Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?> <?php echo wc_help_tip( __( 'The rental price for each period.', 'woocommerce' ) ); ?></th>
                <th colspan="2"><?php esc_html_e( 'Period', 'woocommerce' ); ?> <?php echo wc_help_tip( __( 'The period name for each rental price.', 'woocommerce' ) ); ?></th>
                <th></th>
            </tr>
        </thead>
        <tbody class="ui-sortable">
            <?php
            if( !empty($rental_prices) ){

                //Validate the format of the rent_prices array
                $rental_prices = $this->validated_rental_prices($rental_prices);

                //Begin iterables
                foreach($rental_prices as $index => $rent_price){
                    $regular_price = $rent_price['regular_price'];
                    $sale_price = $rent_price['sale_price'];
                    require WRP_TEMPLATE_DIR . 'admin/product-rental-price.php';
                }

            }
            ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="5"><a href="#" class="add_rental button button-secondary" data-row="
                <?php
                $index = '';
                $regular_price = '';
                $sale_price = '';
                $rent_price = array(
                    'period_name' => ''
                );
                ob_start();
                require WRP_TEMPLATE_DIR . 'admin/product-rental-price.php';
                echo esc_attr( ob_get_clean() );
                ?>
                "><?php esc_html_e( 'Add Rental Plan', 'woocommerce' ); ?></a></th>
            </tr>
        </tfoot>
    </table>
</div>

<script type="text/javascript">

    //Define initial variables
    var sale_price_scheduled = $('p.sale_price_dates_fields').css('display'),
        is_init_rental = $('input#_rental').prop('checked');

    //Display rental price if enabled initially
    if(is_init_rental){
        show_hide_rental_prices();
    }

    //Do something about the rental product type checkbox option
    $('input#_rental').change( function() {
        show_hide_rental_prices();
    });

    //Define functon for hiding and showing rental price table
    function show_hide_rental_prices(){
        var is_rental = $( 'input#_rental:checked' ).length;
        if(is_rental){
            $('p._regular_price_field').hide();
            $('p._sale_price_field').hide();
            if(sale_price_scheduled != 'none'){
                $('p.sale_price_dates_fields').hide();
            }
            $('div.rental_prices_fields').show();
        } else {
            $('p._regular_price_field').show();
            $('p._sale_price_field').show();
            console.log(sale_price_scheduled);
            if(sale_price_scheduled != 'none'){
                $('p.sale_price_dates_fields').show();
            }
            $('div.rental_prices_fields').hide();
        }
    }

    //Rental Prices ordering.
    $('.rental_prices_fields tbody').sortable({
        items: 'tr',
        cursor: 'move',
        axis: 'y',
        handle: 'td.sort',
        scrollSensitivity: 40,
        forcePlaceholderSize: true,
        helper: 'clone',
        opacity: 0.65
    });

    //For deleting rental plans
	$('#woocommerce-product-data').on('click', '.rental_prices_fields a.delete', function() {
		$(this).closest( 'tr' ).remove();
		return false;
	});

    //For adding rental plans
	$('#woocommerce-product-data').on('click', '.rental_prices_fields a.add_rental', function() {
		$(this).closest( '.rental_prices_fields' ).find( 'tbody' ).append( $( this ).data( 'row' ) );
		return false;
	});
    
</script>