<?php

class Mercado_Pago_Settings {
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_init', array(__CLASS__, 'register_settings'));
    }

    public static function add_admin_menu() {
        add_menu_page(
            'Configurações Mercado Pago',
            'Configurações Mercado Pago',
            'manage_options',
            'mercado-pago-settings',
            array(__CLASS__, 'settings_page')
        );
    }

    public static function register_settings() {
        register_setting('mercado_pago_group', 'mercado_pago_settings');
    }

    public static function settings_page() {
        ?>
        <div class="wrap">
            <h1><?php _e('Configurações Mercado Pago', 'woocommerce'); ?></h1>
            <form method="post" action="options.php">
                <?php settings_fields('mercado_pago_group'); ?>
                <?php $options = get_option('mercado_pago_settings'); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row"><?php _e('Access Token', 'woocommerce'); ?></th>
                        <td><input type="text" name="mercado_pago_settings[access_token]" value="<?php echo esc_attr($options['access_token']); ?>" /></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }
}
