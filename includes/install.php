<?php
function mp_split_create_tables() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'mp_split_transactions';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        order_id bigint(20) NOT NULL,
        vendor_id bigint(20) NOT NULL,
        amount decimal(10,2) NOT NULL,
        status varchar(20) NOT NULL,
        payment_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
        PRIMARY KEY  (id)
    ) $charset_collate;";

    require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    dbDelta( $sql );
}
