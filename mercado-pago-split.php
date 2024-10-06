<?php
/**
 * Plugin Name: Mercado Pago Split Plugin
 * Description: Integração do Mercado Pago com split de pagamento para WooCommerce e WCFM Marketplace.
 * Version: 1.0.0
 * Author: Eli Silva
 */

defined('ABSPATH') || exit;

if (!class_exists('Mercado_Pago_Split_Plugin')) {
    class Mercado_Pago_Split_Plugin {
        public function __construct() {
            // Inclui arquivos
            $this->include_files();

            // Inicializa classes
            Mercado_Pago_Settings::init();
            Mercado_Pago_Vendor::init();
            Mercado_Pago_Admin::init();

            // Hooks de ativação e desativação
            register_activation_hook(__FILE__, array($this, 'activate'));
            register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        }

        private function include_files() {
            include_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-settings.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-vendor.php';
            include_once plugin_dir_path(__FILE__) . 'includes/class-mercado-pago-admin.php';
            include_once plugin_dir_path(__FILE__) . 'includes/helper-functions.php';
            include_once plugin_dir_path(__FILE__) . 'includes/ajax-handler.php';
        }

        public function activate() {
            // Ação de ativação do plugin
            include_once plugin_dir_path(__FILE__) . 'install.php';
        }

        public function deactivate() {
            // Ação de desativação do plugin
            include_once plugin_dir_path(__FILE__) . 'uninstall.php';
        }
    }

    new Mercado_Pago_Split_Plugin();
}
