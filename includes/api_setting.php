<?php
/**
 * WooCommerce Rental Products for WooCommerce
 * Plugin URI:   https://virson.wordpress.com/
 * Description:  A WooCommerce plugin extension that extends the REST API to enable Rental product types. Custom fields are also added to the product editor.
 * Version:      1.0.0
 * Author:       Virson Ebillo
 * Author URI:   https://virson.wordpress.com/
*/
 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
	// creating a new sub tab in API settings
add_filter( 'woocommerce_get_sections_advanced','add_subtab' );
function add_subtab( $settings_tabs ) {
    $settings_tabs['flow2b_api_settings'] = __( 'Flow2b API Settings', 'woocommerce-custom-settings-tab' );
    return $settings_tabs;
}

	
// adding settings (HTML Form)
add_filter( 'woocommerce_get_settings_advanced', 'add_subtab_settings', 10, 2 );
function add_subtab_settings( $settings ) {
    $current_section = (isset($_GET['section']) && !empty($_GET['section']))? $_GET['section']:'';
    if ( $current_section == 'flow2b_api_settings' ) {
        $flow2b_api_settings = array();
        $flow2b_api_settings[] = array( 'name' => __( 'Flow2b API Settings', 'text-domain' ), 
                                   'type' => 'title', 
                                   'desc' => __( 'Add Endpoint Url For Flow2b API.', 'text-domain' ), 
                                   'id' => 'flow2b_api_settings'
                                  );

        $flow2b_api_settings[] = array(
                                    'name'     => __( 'Endpoint Url', 'text-domain'),
                                    'id'       => 'endpoint_url',
                                    'type'     => 'url',
                                    'default'  => get_option('endpoint_url'),
                                );

        $flow2b_api_settings[] = array( 'type' => 'sectionend', 'id' => 'endpoint_url' );             
        return $flow2b_api_settings;
    } else {
        // If not, return the standard settings
        return $settings;
    }
}

