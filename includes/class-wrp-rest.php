<?php
/**
 * This is our WRP_Rest class that aims to extend WooCommerce REST API
 */

//Exit on unecessary access
defined('ABSPATH') or exit;

class WRP_Rest extends WRP_Main {

    //Main construct
    public function __construct(){

        /**
         * List of action hooks
         */
        add_action('rest_api_init', array($this, 'custom_rest_endpoints'));

    }

    /**
     * Method for adding custom rest fields
     */
    final public function custom_rest_endpoints(){

        /**
         * For rent_prices REST request
         */
        register_rest_field( 'product', // Object type.
            'rent_prices', // field slug.
            array(
                'get_callback' => function( $object, $field_name, $request ) {
                    return get_post_meta( $object[ 'id' ], '_' . $field_name, true );
                },
                'update_callback' => function( $value, $object, $field_name ){

                    //Check if the value is an array
                    if ( is_array( $value ) ) {

                        //Check if data is formatted properly
                        $value = $this->validated_rental_prices($value);

                        //Check if the array is not empty
                        if( !empty($value) ){
                            //Let's update the product property
                            update_post_meta( $object->id, '_rental', 'yes' );
                            return update_post_meta( $object->id, '_' . $field_name, $value );
                        }

                        return update_post_meta( $object->id, '_rental', 'no' );

                    }

                },
                'schema' => array(
                    'description' => __( 'Sample field customized.', 'woocommerce' ),
                    'type'        => 'array',
                    'context'     => array( 'view', 'edit' )
                ),
            )
        );

        /**
         * Fpr rental REST request
         */
        register_rest_field( 'product', // Object type.
            'rental', // field slug.
            array(
                'get_callback' => function( $object, $field_name, $request ) {
                    $value = get_post_meta( $object[ 'id' ], '_' . $field_name, true );
                    if ( wc_string_to_bool( $value ) ) {
                        return true;
                    } else {
                        return false;
                    }
                },
                'update_callback' => function( $value, $object, $field_name ){
                    if ( wc_string_to_bool( $value ) ) {
                        return update_post_meta( $object->id, '_' . $field_name, 'yes' );
                    } else {
                        return update_post_meta( $object->id, '_' . $field_name, 'no' );
                    }
                },
                'schema' => array(
                    'description' => __( 'If the product is rental.', 'woocommerce' ),
                    'type'        => 'boolean',
                    'context'     => array( 'view', 'edit' )
                ),
            )
        );

    }

}

return new WRP_Rest;