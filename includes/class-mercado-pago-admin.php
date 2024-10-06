<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Classe para gerenciar a área administrativa do plugin
 */
class Mercado_Pago_Admin {
    public static function init() {
        // Adiciona o menu do plugin no painel de administração
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        
        // Adiciona scripts e estilos para as páginas do admin
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_scripts'));
        
        // Adiciona as opções de configuração
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    /**
     * Adiciona o menu de administração
     */
    public static function add_admin_menu() {
        add_menu_page(
            'Configurações Mercado Pago',
            'Configurações Mercado Pago',
            'manage_options',
            'mercado_pago_settings',
            array(__CLASS__, 'settings_page'),
            'dashicons-money',
            100
        );
    }

    /**
     * Renderiza a página de configurações
     */
    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1>Configurações Mercado Pago</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields('mercado_pago_options_group');
                do_settings_sections('mercado_pago_settings');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Registra as configurações do plugin
     */
    public static function register_settings() {
        register_setting('mercado_pago_options_group', 'mercado_pago_settings');

        add_settings_section(
            'mercado_pago_section',
            'Configurações do Mercado Pago',
            array(__CLASS__, 'settings_section_callback'),
            'mercado_pago_settings'
        );

        add_settings_field(
            'access_token',
            'Access Token',
            array(__CLASS__, 'render_access_token_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );

        add_settings_field(
            'public_key',
            'Public Key',
            array(__CLASS__, 'render_public_key_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );

        add_settings_field(
            'sandbox',
            'Modo Sandbox',
            array(__CLASS__, 'render_sandbox_field'),
            'mercado_pago_settings',
            'mercado_pago_section'
        );
    }

    public static function render_access_token_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[access_token]" value="<?php echo esc_attr($options['access_token'] ?? ''); ?>" />
        <?php
    }

    public static function render_public_key_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[public_key]" value="<?php echo esc_attr($options['public_key'] ?? ''); ?>" />
        <?php
    }

    public static function render_sandbox_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="checkbox" name="mercado_pago_settings[sandbox]" value="1" <?php checked(1, isset($options['sandbox']) ? $options['sandbox'] : 0); ?> />
        <?php
    }

    public static function settings_section_callback() {
        echo 'Configure as opções abaixo para integrar o Mercado Pago.';
    }

    public static function enqueue_admin_scripts() {
        wp_enqueue_style('mp-split-style', plugin_dir_url(__FILE__) . '../assets/css/mp-split-style.css');
        wp_enqueue_script('mp-split-scripts', plugin_dir_url(__FILE__) . '../assets/js/mp-split-scripts.js', array('jquery'), null, true);
    }
}

// Inicializa a classe no admin
Mercado_Pago_Admin::init();
