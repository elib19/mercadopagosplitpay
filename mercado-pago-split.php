<?php
/*
Plugin Name: Mercado Pago Split WooCommerce
Description: Plugin de split de pagamento Mercado Pago integrado ao WCFM Marketplace e WooCommerce.
Version: 1.0.0
Author: Eli Silva
*/

// Evitar acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Proteção básica
}

// Definir o caminho base do plugin
define( 'MP_SPLIT_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Carregar classes principais
require_once MP_SPLIT_PLUGIN_DIR . 'includes/class-mp-split-api.php';
require_once MP_SPLIT_PLUGIN_DIR . 'includes/class-mp-split-helper.php';
require_once MP_SPLIT_PLUGIN_DIR . 'includes/mp-split-settings.php';

// Hooks de instalação e desinstalação
register_activation_hook( __FILE__, 'mp_split_install' );
register_deactivation_hook( __FILE__, 'mp_split_uninstall' );

// Função de instalação
function mp_split_install() {
    require_once MP_SPLIT_PLUGIN_DIR . 'includes/install.php';
    mp_split_create_tables(); // Cria tabelas no banco de dados
}

// Função de desinstalação
function mp_split_uninstall() {
    require_once MP_SPLIT_PLUGIN_DIR . 'includes/uninstall.php';
    mp_split_remove_data(); // Remove dados e tabelas

    // Registra as opções do plugin
function mp_split_register_settings() {
    register_setting( 'mp_split_settings_group', 'mp_access_token' );
    register_setting( 'mp_split_settings_group', 'mp_sponsor_id' );
}
add_action( 'admin_init', 'mp_split_register_settings' );

}

// Iniciar o plugin
function mp_split_init() {
    // Adicionar hooks para adicionar configurações no painel do vendedor
    add_action( 'wcfm_vendors_settings', 'mp_split_add_vendor_settings' );
}
add_action( 'plugins_loaded', 'mp_split_init' );
