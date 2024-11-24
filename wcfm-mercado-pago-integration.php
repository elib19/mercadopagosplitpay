<?php
/**
 * Plugin Name: Mercado Pago WCFM Integration
 * Plugin URI: https://juntoaqui.com.br
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: mercado-pago-wcfm
 */

// Bloquear acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Definir constantes
define('MP_WCFM_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MP_WCFM_PLUGIN_URL', plugin_dir_url(__FILE__));

// Carregar arquivos essenciais
require_once MP_WCFM_PLUGIN_DIR . 'includes/class-mp-oauth.php';
require_once MP_WCFM_PLUGIN_DIR . 'includes/class-wcfm-connector.php';
require_once MP_WCFM_PLUGIN_DIR . 'includes/class-token-updater.php';

// Inicializar o plugin
add_action('plugins_loaded', function () {
    if (class_exists('WCFMmp') && class_exists('WooCommerce')) {
        MP_WCFM_OAuth::init();
        MP_WCFM_Connector::init();
    } else {
        add_action('admin_notices', function () {
            echo '<div class="notice notice-error"><p><strong>O plugin Mercado Pago WCFM Integration requer o WooCommerce e o WCFM Marketplace ativos.</strong></p></div>';
        });
    }
});
