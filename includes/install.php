<?php
/**
 * Script de instalação do Mercado Pago Split Payment
 */

function mp_split_install() {
    // Define opções padrão
    $default_options = array(
        'mp_split_app_id' => '',
        'mp_split_client_secret' => '',
        'mp_split_redirect_uri' => ''
    );

    if (!get_option('mp_split_settings')) {
        add_option('mp_split_settings', $default_options);
    }
}

register_activation_hook(__FILE__, 'mp_split_install');
