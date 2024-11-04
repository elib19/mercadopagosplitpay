<?php
/**
 * Plugin Name: Mercado Pago Integration
 * Plugin URI: https://juntoaqui.com.br/
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br/
 * Text Domain: mercado-pago-split
 * Domain Path: /languages
 * License: GPL2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Inclui os arquivos necessários
include_once dirname( __FILE__ ) . '/includes/class-wcfmmp-gateway-mercado-pago.php';
include_once dirname( __FILE__ ) . '/includes/class-mercado-pago-checkout.php';

// Inicializa o plugin
add_action( 'plugins_loaded', 'mercado_pago_split_init' );

function mercado_pago_split_init() {
    // Inicializa as classes de checkout e gateway
    new WCFMmp_Gateway_Mercado_Pago();
    new Mercado_Pago_Checkout();
}
