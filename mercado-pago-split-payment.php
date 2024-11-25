<?php
/*
Plugin Name: Mercado Pago Split Payment
Description: Integração do Mercado Pago com o WooCommerce e WCFM para pagamento dividido usando OAuth 2.0.
Version: 1.0
Author: Seu Nome
*/

// Impede o acesso direto ao arquivo
if (!defined('ABSPATH')) {
    exit;
}

// Define constantes
define('MP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MP_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclui arquivos necessários
require_once MP_PLUGIN_DIR . 'includes/class-mp-gateway.php';
require_once MP_PLUGIN_DIR . 'includes/class-mp-oauth.php';

// Inicializa o plugin
function mp_init() {
    // Adiciona o gateway de pagamento ao WooCommerce
    add_filter('woocommerce_payment_gateways', function ($gateways) {
        $gateways[] = 'MP_Gateway';
        return $gateways;
    });

    // Adiciona scripts e estilos apenas na página de configurações de pagamento do WooCommerce
    if (isset($_GET['page']) && $_GET['page'] == 'wc-settings' && isset($_GET['tab']) && $_GET['tab'] == 'checkout') {
        wp_enqueue_script('mp-script', MP_PLUGIN_URL . 'assets/js/mp-script.js', array('jquery'), '1.0', true);
    }
}
add_action('plugins_loaded', 'mp_init');
