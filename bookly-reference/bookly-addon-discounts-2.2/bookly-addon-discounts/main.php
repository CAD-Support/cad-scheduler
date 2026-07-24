<?php defined( 'ABSPATH' ) || exit; // Exit if accessed directly
/*
Plugin Name: Bookly Discounts (Add-on)
Plugin URI: https://www.booking-wp-plugin.com/?utm_source=bookly_admin&utm_medium=plugins_page&utm_campaign=plugins_page
Description: Bookly Discounts add-on allows you to set up conditional discounts that will be applied during checkout if the booking details match requirements set by you.
Version: 2.2
Author: Nota-Info
Author URI: https://www.booking-wp-plugin.com/?utm_source=bookly_admin&utm_medium=plugins_page&utm_campaign=plugins_page
Text Domain: bookly
Domain Path: /languages
License: Commercial
Update URI: https://hub.bookly.pro
*/

$addon = implode( DIRECTORY_SEPARATOR, array( str_replace( array( '/', '\\' ), DIRECTORY_SEPARATOR, WP_PLUGIN_DIR ), 'bookly-addon-pro', 'lib', 'addons', basename( __DIR__ ) ) );
if ( ! file_exists( $addon ) || $addon === __DIR__ ) {
    include_once __DIR__ . '/autoload.php';
    BooklyDiscounts\Lib\Boot::up();
}