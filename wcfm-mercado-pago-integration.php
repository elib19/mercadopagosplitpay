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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Verifica se o WCFM Marketplace está ativo
if (class_exists('WCFMmp')) {
    // Incluir arquivos necessários
    require_once plugin_dir_path(__FILE__) . 'includes/config.php';
    require_once plugin_dir_path(__FILE__) . 'includes/token_handler.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wcfm-mercado-pago-gateway.php';
    require_once plugin_dir_path(__FILE__) . 'includes/class-wcfm-mercado-pago-auth.php';

    // Inicializa o plugin
    add_action('plugins_loaded', 'wcfm_mercado_pago_init');
}

function wcfm_mercado_pago_init() {
    // Verifica se o WCFM Marketplace está ativo
    if (class_exists('WCFMmp')) {
        new WCFMmp_Gateway_Mercado_Pago();
        new WCFM_Mercado_Pago_Auth();
    }
}
?>
