<?php
/**
 * Classe de administração do Mercado Pago Split Payment
 */
class MP_Split_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Mercado Pago Split', 'mp-split'),
            __('MP Split', 'mp-split'),
            'manage_options',
            'mp-split-settings',
            array($this, 'mp_split_options_page'),
            'dashicons-admin-generic'
        );
    }

    public function mp_split_options_page() {
        include plugin_dir_path(__FILE__) . 'views/admin-settings-page.php';
    }

    public function register_settings() {
        register_setting('mpSplit', 'mp_split_settings');

        add_settings_section(
            'mp_split_section',
            __('Configurações Gerais', 'mp-split'),
            null,
            'mpSplit'
        );

        add_settings_field(
            'mp_split_app_id',
            __('App ID', 'mp-split'),
            array($this, 'app_id_field_render'),
            'mpSplit',
            'mp_split_section'
        );

        add_settings_field(
            'mp_split_client_secret',
            __('Client Secret', 'mp-split'),
            array($this, 'client_secret_field_render'),
            'mpSplit',
            'mp_split_section'
        );

        add_settings_field(
            'mp_split_redirect_uri',
            __('Redirect URI', 'mp-split'),
            array($this, 'redirect_uri_field_render'),
            'mpSplit',
            'mp_split_section'
        );
    }

    public function app_id_field_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type="text" name="mp_split_settings[mp_split_app_id]" value="<?php echo isset($options['mp_split_app_id']) ? esc_attr($options['mp_split_app_id']) : ''; ?>" />
        <?php
    }

    public function client_secret_field_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type="password" name="mp_split_settings[mp_split_client_secret]" value="<?php echo isset($options['mp_split_client_secret']) ? esc_attr($options['mp_split_client_secret']) : ''; ?>" />
        <?php
    }

    public function redirect_uri_field_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type="text" name="mp_split_settings[mp_split_redirect_uri]" value="<?php echo isset($options['mp_split_redirect_uri']) ? esc_attr($options['mp_split_redirect_uri']) : ''; ?>" />
        <?php
    }

    public function enqueue_admin_assets() {
        wp_enqueue_style('mp-split-admin-css', plugin_dir_url(__FILE__) . '../assets/css/admin-style.css', array(), '1.0');
        wp_enqueue_script('mp-split-admin-js', plugin_dir_url(__FILE__) . '../assets/js/admin-script.js', array('jquery'), '1.0', true);
    }
}
