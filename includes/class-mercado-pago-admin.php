<?php

class Mercado_Pago_Admin {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
    }

    public static function add_admin_menu() {
        add_menu_page('Configurações Mercado Pago', 'Configurações Mercado Pago', 'manage_options', 'mercado-pago-settings', array(__CLASS__, 'settings_page'));
        add_submenu_page('mercado-pago-settings', 'Relatórios', 'Relatórios', 'manage_options', 'mercado-pago-reports', array(Mercado_Pago_Reports::class, 'reports_page'));
    }

    public static function settings_page() {
        require_once MERCADO_PAGO_PLUGIN_DIR . 'views/admin-settings.php';
    }
}
