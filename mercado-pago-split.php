<?php
/**
 * Plugin Name: Mercado Pago Split Payment for WCFM
 * Plugin URI: https://brasilnarede.online
 * Description: Integração do Mercado Pago com suporte a split de pagamentos para WooCommerce.
 * Version: 1.0.0
 * Author: Eli Silva
 * Author URI: https://brasilnarede.online
 * Text Domain: mercado-pago-split-wcfm
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Carrega arquivos necessários
require_once plugin_dir_path(__FILE__) . 'install.php';
require_once plugin_dir_path(__FILE__) . 'uninstall.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-mp-split-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mp-split-wcfm.php';
require_once plugin_dir_path(__FILE__) . 'lib/mercadopago.php'; // Inclusão da classe de integração com a API do Mercado Pago
require_once plugin_dir_path(__FILE__) . 'assets/css/admin-style.css'; // Inclusão do CSS do admin (se aplicável)
require_once plugin_dir_path(__FILE__) . 'assets/js/admin-script.js'; // Inclusão do JS do admin (se aplicável)
require_once plugin_dir_path(__FILE__) . 'includes/helper.php';

// Inicializa a classe de administração
function mp_split_init() {
    new MP_Split_Admin();
}
add_action('plugins_loaded', 'mp_split_init');
function mp_split_init() {
    new MP_Split_Admin();
    new MP_Split_WCFM(); // Inicializa a classe de integração com WCFM
}
add_action('plugins_loaded', 'mp_split_init');


