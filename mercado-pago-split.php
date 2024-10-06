<?php
/**
 * Plugin Name: MercadoPago Split Payments for WooCommerce & WCFM
 * Description: Plugin to integrate split payments with Mercado Pago, WooCommerce, and WCFM Marketplace.
 * Version: 1.0.0
 * Author: Seu Nome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Define constants
define( 'MP_SPLIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Include necessary files
require_once MP_SPLIT_PLUGIN_DIR . 'includes/install.php';
require_once MP_SPLIT_PLUGIN_DIR . 'includes/uninstall.php';
require_once MP_SPLIT_PLUGIN_DIR . 'includes/class-mp-split-helper.php';
require_once MP_SPLIT_PLUGIN_DIR . 'includes/class-mp-split-api.php';

// Register activation and deactivation hooks
register_activation_hook( __FILE__, 'mp_split_install' );
register_deactivation_hook( __FILE__, 'mp_split_uninstall' );

// Load CSS
function mp_split_enqueue_styles() {
    wp_enqueue_style( 'mp-split-style', plugins_url( 'assets/css/mp-split-style.css', __FILE__ ) );
}
add_action( 'wp_enqueue_scripts', 'mp_split_enqueue_styles' );

// Add settings page to WooCommerce
function mp_split_add_settings_page() {
    add_submenu_page( 'woocommerce', 'MercadoPago Split', 'MercadoPago Split', 'manage_options', 'mp-split-settings', 'mp_split_settings_page' );
}
add_action( 'admin_menu', 'mp_split_add_settings_page' );

// Load the settings page
function mp_split_settings_page() {
    require_once MP_SPLIT_PLUGIN_DIR . 'views/mp-split-settings-page.php';
}
