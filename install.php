<?php

function mercado_pago_create_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mercado_pago_transactions';

    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE IF NOT EXISTS $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendor_id mediumint(9) NOT NULL,
        data_venda datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
        preco_produto decimal(10,2) NOT NULL,
        taxa_marketplace decimal(10,2) NOT NULL,
        lucro_vendedor decimal(10,2) NOT NULL,
        lucro_total decimal(10,2) NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}

mercado_pago_create_table();
