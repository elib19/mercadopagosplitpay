<?php

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit; // Impede acesso direto
}

global $wpdb;
$table_name = $wpdb->prefix . 'mercado_pago_transactions';
$wpdb->query("DROP TABLE IF EXISTS $table_name");
