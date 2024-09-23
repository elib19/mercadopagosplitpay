<?php
/**
 * Plugin Name: Mercado Pago Split Payment
 * Description: Integração do Mercado Pago com suporte a split de pagamentos para WooCommerce.
 * Version: 1.0
 * Author: Eli Silva
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Carrega arquivos necessários
require_once plugin_dir_path(__FILE__) . 'install.php';
require_once plugin_dir_path(__FILE__) . 'uninstall.php';
require_once plugin_dir_path(__FILE__) . 'admin/class-mp-split-admin.php';
require_once plugin_dir_path(__FILE__) . 'includes/class-mp-split-wcfm.php';
require_once plugin_dir_path(__FILE__) . 'includes/helper.php'; // Certifique-se de incluir helper.php
require_once plugin_dir_path(__FILE__) . 'lib/mercadopago.php'; // Classe de integração

// Inicializa a classe de administração
function mp_split_init() {
    new MP_Split_Admin();
    new MP_Split_WCFM(); // Inicializa a classe de integração com WCFM
}
add_action('plugins_loaded', 'mp_split_init');
