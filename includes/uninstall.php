<?php
function mp_split_uninstall() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mp_split_vendors';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" );
}
