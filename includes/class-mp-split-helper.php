<?php
class MP_Split_Helper {

    public static function get_vendor_access_token( $vendor_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_split_vendors';
        return $wpdb->get_var( $wpdb->prepare( "SELECT access_token FROM $table_name WHERE vendor_id = %d", $vendor_id ) );
    }

    public static function save_vendor_access_token( $vendor_id, $access_token, $sponsor_id ) {
        global $wpdb;
        $table_name = $wpdb->prefix . 'mp_split_vendors';

        $wpdb->replace(
            $table_name,
            array(
                'vendor_id'    => $vendor_id,
                'access_token' => $access_token,
                'sponsor_id'   => $sponsor_id
            )
        );
    }
}
