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

    // Criar tabela para armazenar informações dos vendedores
    global $wpdb;
    $table_name = $wpdb->prefix . 'mp_vendor_payments';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendor_id bigint(20) NOT NULL,
        transaction_amount decimal(10,2) NOT NULL,
        payment_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

// Registrar a função de instalação no hook de ativação
register_activation_hook( __FILE__, 'mp_split_plugin_install' );
