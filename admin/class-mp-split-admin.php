<?php
/**
 * Classe de administração do Mercado Pago Split Payment
 */
class MP_Split_Admin {

    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'settings_init'));
    }

    public function add_admin_menu() {
        add_menu_page('Configurações Mercado Pago', 'MP Split', 'manage_options', 'mp_split', array($this, 'settings_page'));
    }

    public function settings_init() {
        register_setting('mpSplit', 'mp_split_settings');
        add_settings_section('mp_split_section', __('Configurações do Mercado Pago', 'mp-split'), null, 'mpSplit');

        add_settings_field('mp_split_app_id', __('APP ID', 'mp-split'), array($this, 'app_id_render'), 'mpSplit', 'mp_split_section');
        add_settings_field('mp_split_client_secret', __('Client Secret', 'mp-split'), array($this, 'client_secret_render'), 'mpSplit', 'mp_split_section');
        add_settings_field('mp_split_redirect_uri', __('Redirect URI', 'mp-split'), array($this, 'redirect_uri_render'), 'mpSplit', 'mp_split_section');
    }

    public function app_id_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type='text' name='mp_split_settings[mp_split_app_id]' value='<?php echo esc_attr($options['mp_split_app_id']); ?>'>
        <?php
    }

    public function client_secret_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type='text' name='mp_split_settings[mp_split_client_secret]' value='<?php echo esc_attr($options['mp_split_client_secret']); ?>'>
        <?php
    }

    public function redirect_uri_render() {
        $options = get_option('mp_split_settings');
        ?>
        <input type='text' name='mp_split_settings[mp_split_redirect_uri]' value='<?php echo esc_attr($options['mp_split_redirect_uri']); ?>'>
        <?php
    }

    public function settings_page() {
        ?>
        <form action='options.php' method='post'>
            <h2><?php _e('Configurações do Mercado Pago', 'mp-split'); ?></h2>
            <?php
            settings_fields('mpSplit');
            do_settings_sections('mpSplit');
            submit_button();
            ?>
        </form>
        <?php
    }
}
