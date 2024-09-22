<?php

class MP_Split_Admin {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'mp_add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'mp_register_settings' ) );
    }

    // Adicionar menu ao painel de administração
    public function mp_add_admin_menu() {
        add_menu_page(
            __( 'Mercado Pago Split', 'mercado-pago-split-wcfm' ),
            __( 'Mercado Pago Split', 'mercado-pago-split-wcfm' ),
            'manage_options',
            'mp-split-settings',
            array( $this, 'mp_settings_page' ),
            'dashicons-admin-settings'
        );
    }

    // Registrar configurações
    public function mp_register_settings() {
        register_setting( 'mp-split-settings-group', 'mp_access_token' );
        register_setting( 'mp-split-settings-group', 'mp_application_fee' );
        register_setting( 'mp-split-settings-group', 'mp_pix_key' ); // Nova configuração para chave PIX
    }

    // Renderizar página de configurações
    public function mp_settings_page() {
        require_once plugin_dir_path( __FILE__ ) . 'views/admin-settings-page.php';
    }
}

// Inicializar a classe de administração
new MP_Split_Admin();
