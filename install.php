<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Função para instalar o plugin
 */
function mercado_pago_install() {
    global $wpdb;

    // Define o nome da tabela
    $table_name = $wpdb->prefix . 'mercado_pago_transactions';

    // Verifica se a tabela já existe
    if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {
        // SQL para criar a tabela
        $charset_collate = $wpdb->get_charset_collate();
        $sql = "CREATE TABLE $table_name (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            transaction_id varchar(255) NOT NULL,
            vendor_id mediumint(9) NOT NULL,
            amount float NOT NULL,
            description text NOT NULL,
            status varchar(50) NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
            PRIMARY KEY (id)
        ) $charset_collate;";

        // Executa a criação da tabela
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    // Adiciona opções padrão
    add_option('mercado_pago_settings', array(
        'access_token' => '',
        'public_key' => '',
        'sandbox' => true,
    ));
}

// Aciona a função de instalação ao ativar o plugin
register_activation_hook(__FILE__, 'mercado_pago_install');
