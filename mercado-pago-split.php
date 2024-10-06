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

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

// Inclui os arquivos necessários
require_once plugin_dir_path(__FILE__) . 'includes/gateway.php';
require_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-settings.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-vendor.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';
require_once plugin_dir_path(__FILE__) . 'includes/install.php';
require_once plugin_dir_path(__FILE__) . 'uninstall.php';

// Inicializa o plugin
add_action('plugins_loaded', function() {
    // Carrega o gateway de pagamento
    add_filter('woocommerce_payment_gateways', function($gateways) {
        $gateways[] = 'WC_Mercado_Pago_Gateway';
        return $gateways;
    });
});

// Inicializa a administração do plugin
Mercado_Pago_Admin::init();
Mercado_Pago_Vendor::init();
