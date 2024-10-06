<?php
// O arquivo de desinstalação é chamado quando o plugin é removido
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Proteção básica
}

function mp_split_remove_data() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mp_split_transactions';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );

    // Remover dados de configurações do vendedor
    $vendors = get_users( array( 'role' => 'vendor' ) );
    foreach ( $vendors as $vendor ) {
        delete_user_meta( $vendor->ID, 'mp_access_token' );
        delete_user_meta( $vendor->ID, 'mp_sponsor_id' );
    }
}
