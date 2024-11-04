<?php
/**
 * Plugin Name: Mercado Pago Integration
 * Plugin URI: https://brasilnarede.online/
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0
 * Author: Eli Silva
 * Author URI: https://brasilnarede.online/
 * Text Domain: mercado-pago-split
 * Domain Path: /languages
 * License: GPL2
 */

// Impede acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Carregar arquivos necessários
require_once plugin_dir_path(__FILE__) . 'admin-settings.php';
require_once plugin_dir_path(__FILE__) . 'checkout-handler.php';
require_once plugin_dir_path(__FILE__) . 'oauth-handler.php';

// Ativar e desativar o plugin
register_activation_hook(__FILE__, 'mp_integration_activate');
register_deactivation_hook(__FILE__, 'mp_integration_deactivate');

function mp_integration_activate() {
    // Código para ativar o plugin
}

function mp_integration_deactivate() {
    // Código para desativar o plugin
}

// Inicializar o plugin
add_action('plugins_loaded', 'initialize_mercado_pago_integration');
function initialize_mercado_pago_integration() {
    // Carregar scripts e estilos necessários
    add_action('wp_enqueue_scripts', 'mp_enqueue_scripts');
}

function mp_enqueue_scripts() {
    wp_enqueue_script('mercado-pago-js', 'https://sdk.mercadopago.com/js/v2', array(), null, true);
    wp_enqueue_script('mp-custom-js', plugin_dir_url(__FILE__) . 'assets/mercado-pago.js', array('mercado-pago-js'), null, true);
}
