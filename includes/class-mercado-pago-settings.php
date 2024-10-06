<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

class Mercado_Pago_Settings {
    public function __construct() {
        // Adiciona ações para registrar e renderizar as configurações
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Registra as configurações do Mercado Pago
     */
    public function register_settings() {
        register_setting('mercado_pago_options_group', 'mercado_pago_settings');

        add_settings_section(
            'mercado_pago_section',
            __('Configurações do Mercado Pago', 'mercado-pago-split'),
            array($this, 'settings_section_callback'),
            'mercado_pago_settings'
        );

        add_settings_field(
            'access_token',
            __('Access Token', 'mercado-pago-split'),
            array($this, 'render_access_token_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );

        add_settings_field(
            'public_key',
            __('Public Key', 'mercado-pago-split'),
            array($this, 'render_public_key_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );

        add_settings_field(
            'sandbox',
            __('Modo Sandbox', 'mercado-pago-split'),
            array($this, 'render_sandbox_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );
    }

    public function settings_section_callback() {
        echo __('Configure as opções abaixo para integrar o Mercado Pago.', 'mercado-pago-split');
    }

    public function render_access_token_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[access_token]" value="<?php echo esc_attr($options['access_token'] ?? ''); ?>" />
        <?php
    }

    public function render_public_key_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[public_key]" value="<?php echo esc_attr($options['public_key'] ?? ''); ?>" />
        <?php
    }

    public function render_sandbox_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="checkbox" name="mercado_pago_settings[sandbox]" value="1" <?php checked(1, isset($options['sandbox']) ? $options['sandbox'] : 0); ?> />
        <?php
    }
}

// Inicializa a classe
new Mercado_Pago_Settings();
