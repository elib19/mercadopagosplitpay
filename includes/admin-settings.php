add_action('admin_menu', 'mp_add_admin_menu');
add_action('admin_init', 'mp_settings_init');

function mp_add_admin_menu() {
    add_submenu_page('wcfm-menu', 'Mercado Pago Settings', 'Mercado Pago', 'manage_options', 'mercado_pago_settings', 'mp_options_page');
}

function mp_settings_init() {
    register_setting('mp_options', 'mp_settings');

    add_settings_section(
        'mp_plugin_section',
        __('Configurações do Mercado Pago', 'mp'),
        'mp_settings_section_callback',
        'mp_options'
    );

    add_settings_field(
        'public_key',
        __('Public Key', 'mp'),
        'mp_public_key_render',
        'mp_options',
        'mp_plugin_section'
    );

    add_settings_field(
        'access_token',
        __('Access Token', 'mp'),
        'mp_access_token_render',
        'mp_options',
        'mp_plugin_section'
    );
}

function mp_public_key_render() {
    $options = get_option('mp_settings');
    ?>
    <input type='text' name='mp_settings[public_key]' value='<?php echo esc_attr($options['public_key']); ?>'>
    <?php
}

function mp_access_token_render() {
    $options = get_option('mp_settings');
    ?>
    <input type='text' name='mp_settings[access_token]' value='<?php echo esc_attr($options['access_token']); ?>'>
    <?php
}

function mp_settings_section_callback() {
    echo __('Insira as credenciais do Mercado Pago para integração.', 'mp');
}

function mp_options_page() {
    ?>
    <form action='options.php' method='post'>
        <h2>Mercado Pago Settings</h2>
        <?php
        settings_fields('mp_options');
        do_settings_sections('mp_options');
        submit_button();
        ?>
    </form>
    <?php
}
