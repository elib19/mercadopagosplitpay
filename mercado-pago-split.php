<?php
/**
 * Plugin Name: Mercado Pago Integration
 * Plugin URI: https://brasilnarede.online/
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0
 * Author: Eli Silva
 * Author URI: https://brasilnarede.online/
 * License: GPL2
 */

// Evita o acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Inclui arquivos necessários
require_once plugin_dir_path(__FILE__) . 'install.php';
require_once plugin_dir_path(__FILE__) . 'uninstall.php';
require_once plugin_dir_path(__FILE__) . 'includes/admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/gateway.php'; // Inclui o gateway de pagamento

// Inicializa o plugin
add_action('plugins_loaded', 'mercado_pago_split_init');

function mercado_pago_split_init() {
    // Verifica se a classe de pagamento do WooCommerce existe
    if (class_exists('WC_Payment_Gateway')) {
        add_filter('woocommerce_payment_gateways', 'add_mercado_pago_gateway');
        function add_mercado_pago_gateway($gateways) {
            $gateways[] = 'WC_Mercado_Pago_Gateway'; // Adiciona o gateway de pagamento
            return $gateways;
        }
    }
}
