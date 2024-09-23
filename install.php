<?php
/**
 * Funções de instalação do plugin
 */

function mp_split_install() {
    // Aqui você pode adicionar opções padrão ao ativar o plugin, se necessário
    add_option('mp_split_settings', array(
        'mp_split_app_id' => '',
        'mp_split_client_secret' => '',
        'mp_split_redirect_uri' => ''
    ));
}
register_activation_hook(__FILE__, 'mp_split_install');
