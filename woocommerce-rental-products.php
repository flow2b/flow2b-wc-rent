<?php
/**
 * Plugin Name:  WooCommerce Rental Products
 * Plugin URI:   https://virson.wordpress.com/
 * Description:  A WooCommerce plugin extension that extends the REST API to enable Rental product types. Custom fields are also added to the product editor. Additional Endpoint for Rental Price API setting.
 * Version:      1.0.0
 * Author:       Virson Ebillo
 * Author URI:   https://virson.wordpress.com/
*/

//Exit on unecessary access
defined('ABSPATH') or exit;

//Define main file path.
if (!defined('WRP_PLUGIN_FILE')){
	define('WRP_PLUGIN_FILE', __FILE__);
}

//Check if the class exists
if(!class_exists('WRP_Main')){
	include_once plugin_dir_path(WRP_PLUGIN_FILE) . 'includes/class-wrp-main.php';
}

/**
 * Instantiate the WRP_Main class
*/
function WRP(){
	return WRP_Main::instance('1.0.0');
}
WRP();
