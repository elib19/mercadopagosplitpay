<?php

if (!defined('WP_UNINSTALL_PLUGIN')) {
    die;
}

function mp_split_plugin_install()
{
    $default_settings = array(
        'mp_access_token'     => '',
        'mp_application_fee'  => 10,  // Taxa de comissão padrão
    );

    foreach ($default_settings as $key => $value) {
        if (get_option($key) === false) {
            update_option($key, $value);
        }
    }
}

register_activation_hook(__FILE__, 'mp_split_plugin_install');
