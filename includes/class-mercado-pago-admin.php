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
            'Configurações Mercado Pago', // Título da página
            'Configurações Mercado Pago', // Nome do menu
            'manage_options', // Capacidade
            'mercado_pago_settings', // Slug
            array(__CLASS__, 'settings_page'), // Função que renderiza a página
            'dashicons-money', // Ícone do menu
            100 // Posição
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
        // Registra a configuração
        register_setting('mercado_pago_options_group', 'mercado_pago_settings');

        // Adiciona uma seção
        add_settings_section(
            'mercado_pago_section', // ID da seção
            'Configurações do Mercado Pago', // Título
            array(__CLASS__, 'settings_section_callback'), // Callback
            'mercado_pago_settings' // Página
        );

        // Adiciona campos
        add_settings_field(
            'access_token', // ID
            'Access Token', // Título
            array(__CLASS__, 'render_access_token_field'), // Callback
            'mercado_pago_settings', // Página
            'mercado_pago_section' // Seção
        );

        add_settings_field(
            'public_key', // ID
            'Public Key', // Título
            array(__CLASS__, 'render_public_key_field'), // Callback
            'mercado_pago_settings', // Página
            'mercado_pago_section' // Seção
        );
        
        add_settings_field(
            'sandbox', // ID
            'Modo Sandbox', // Título
            array(__CLASS__, 'render_sandbox_field'), // Callback
            'mercado_pago_settings', // Página
            'mercado_pago_section' // Seção
        );
    }

    /**
     * Renderiza o campo Access Token
     */
    public static function render_access_token_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[access_token]" value="<?php echo esc_attr($options['access_token'] ?? ''); ?>" />
        <?php
    }

    /**
     * Renderiza o campo Public Key
     */
    public static function render_public_key_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="text" name="mercado_pago_settings[public_key]" value="<?php echo esc_attr($options['public_key'] ?? ''); ?>" />
        <?php
    }
    
    /**
     * Renderiza o campo Modo Sandbox
     */
    public static function render_sandbox_field() {
        $options = get_option('mercado_pago_settings');
        ?>
        <input type="checkbox" name="mercado_pago_settings[sandbox]" value="1" <?php checked(1, isset($options['sandbox']) ? $options['sandbox'] : 0); ?> />
        <?php
    }

    /**
     * Callback da seção de configurações
     */
    public static function settings_section_callback() {
        echo 'Configure as opções abaixo para integrar o Mercado Pago.';
    }

    /**
     * Enqueue scripts e estilos
     */
    public static function enqueue_admin_scripts() {
        // Adicione seus scripts e estilos aqui
    }
}

// Inicializa a classe no admin
Mercado_Pago_Admin::init();
