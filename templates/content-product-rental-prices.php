<?php
/**
 * This is the product content before the cart template.
 * Actual template is loaded before the quantity input field.
 */

//Exit on unecessary access
defined('ABSPATH') or exit;

	if( is_null($first_item) ){
	    $first_item = false;
	}
?>

<div class="wrp_content_product_rental_prices">

    <?php if( $first_item === false ): ?>
    <h4>Choose Rental Dates</h4>

    <?php endif; ?>

    <?php echo $this->format_rental_price_table($data, $first_item); ?>

</div>
