<?php

// Se o plugin foi acessado diretamente, interromper execução
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    die;
}

function mp_split_plugin_uninstall() {
    // Remover as opções armazenadas no banco de dados
    delete_option( 'mp_access_token' );
    delete_option( 'mp_application_fee' );
}

// Executar a função de desinstalação
mp_split_plugin_uninstall();
