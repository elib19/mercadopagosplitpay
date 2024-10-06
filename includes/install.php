<?php
function mp_split_install() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mp_split_vendors';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        vendor_id bigint(20) NOT NULL,
        access_token varchar(255) NOT NULL,
        sponsor_id varchar(255),
        PRIMARY KEY (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
