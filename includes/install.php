<?php

// Verificar se foi acessado diretamente
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

function mp_split_plugin_install() {
    // Definir valores padrão das configurações
    $default_settings = array(
        'mp_access_token'     => '',
        'mp_application_fee'  => 10,  // Taxa de comissão padrão
    );

    // Criar as opções no banco de dados se elas não existirem
    foreach ( $default_settings as $key => $value ) {
        if ( get_option( $key ) === false ) {
            update_option( $key, $value );
        }
    }
}

// Registrar a função de instalação no hook de ativação
register_activation_hook( __FILE__, 'mp_split_plugin_install' );
