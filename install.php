<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Instalação do plugin Mercado Pago
 */
function mercado_pago_install() {
    global $wpdb;

    // Criação de uma tabela personalizada, se necessário
    $table_name = $wpdb->prefix . 'mercado_pago_custom_table'; // Defina o nome da tabela

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendor_id bigint(20) NOT NULL,
        payment_data text NOT NULL,
        created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Definindo opções padrão
    if (!get_option('mercado_pago_settings')) {
        add_option('mercado_pago_settings', array(
            'access_token' => '',
            'public_key' => '',
            'sandbox' => '0',
        ));
    }
}

// Hook para ativação do plugin
register_activation_hook(__FILE__, 'mercado_pago_install');
