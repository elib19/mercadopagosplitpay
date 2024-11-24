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
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Definir as credenciais do Mercado Pago (essas devem ser configuradas na página de configurações do plugin)
define('MERCADO_PAGO_CLIENT_ID', 'YOUR_CLIENT_ID');
define('MERCADO_PAGO_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');

// Função para incluir os arquivos necessários
function mercado_pago_gateway_split_plugin_files() {
    include_once plugin_dir_path(__FILE__) . 'includes/mercado-pago-oauth.php';
    include_once plugin_dir_path(__FILE__) . 'includes/mercado-pago-split.php';
}
add_action('plugins_loaded', 'mercado_pago_gateway_split_plugin_files');
