<?php
function mp_split_remove_data() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mp_split_transactions';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
