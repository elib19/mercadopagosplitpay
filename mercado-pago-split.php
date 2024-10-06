<?php
/**
 * Plugin Name: Mercado Pago Split para WooCommerce
 * Description: Integra o Mercado Pago com WooCommerce e WCFM com suporte para split de pagamento.
 * Version: 1.0
 * Author: Seu Nome
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Impede acesso direto
}

// Definindo constantes
define( 'MERCADO_PAGO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MERCADO_PAGO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

// Incluindo arquivos
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-admin.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-vendor.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-reports.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-helper.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-settings.php';

// Função de ativação
function mercado_pago_activate() {
    require_once MERCADO_PAGO_PLUGIN_DIR . 'install.php';
}
register_activation_hook( __FILE__, 'mercado_pago_activate' );

// Função de desativação
function mercado_pago_deactivate() {
    require_once MERCADO_PAGO_PLUGIN_DIR . 'uninstall.php';
}
register_deactivation_hook( __FILE__, 'mercado_pago_deactivate' );

// Inicializando o plugin
add_action( 'plugins_loaded', 'mercado_pago_init' );
function mercado_pago_init() {
    Mercado_Pago_Admin::init();
    Mercado_Pago_Vendor::init();
}
