<?php
class MP_Split_Helper {

    // Função para obter credenciais do Mercado Pago do vendedor
    public static function get_vendor_mp_credentials( $vendor_id ) {
        $access_token = get_user_meta( $vendor_id, 'mp_access_token', true );
        return $access_token;
    }

    // Função para registrar configurações do vendedor
    public static function save_vendor_settings( $vendor_id, $settings ) {
        update_user_meta( $vendor_id, 'mp_access_token', sanitize_text_field( $settings['access_token'] ) );
        update_user_meta( $vendor_id, 'mp_sponsor_id', sanitize_text_field( $settings['sponsor_id'] ) );
    }
}
