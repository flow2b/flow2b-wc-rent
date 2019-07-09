<?php
/**
 * This is the admin template for each product rental price
 */

//Exit on unecessary access
defined('ABSPATH') or exit;


?>
<tr>
    <td class="sort"></td>
    <td>
        <div class="rental_price_left">
            <?php echo esc_html_e( 'Regular Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>
        </div>
        <div class="rental_price_right">
            <input type="text" class="short wc_input_price" name="_rent_prices[<?php echo $index; ?>][regular_price]" value="<?php echo esc_attr( $regular_price ); ?>"/>
        </div>
        <div style="clear:both; margin: 10px 0;"></div>
        <div class="rental_price_left">
            <?php echo esc_html_e( 'Sale Price', 'woocommerce' ) . ' (' . get_woocommerce_currency_symbol() . ')'; ?>
        </div>
        <div class="rental_price_right">
            <input type="text" class="short wc_input_price" name="_rent_prices[<?php echo $index; ?>][sale_price]" value="<?php echo esc_attr( $sale_price ); ?>"/>
        </div>
        <div style="clear:both;"></div>
    </td>
    <td>
        <input type="text" name="_rent_prices[<?php echo $index; ?>][period_name]" value="<?php echo esc_attr( $rent_price['period_name'] ); ?>"/>
    </td>
    <td colspan="2"><a href="#" class="delete"></a></td>
</tr>