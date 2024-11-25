<?php

class MP_OAuth {
    private $client_id;
    private $client_secret;

    public function __construct($client_id, $client_secret) {
        $this->client_id = $client_id;
        $this->client_secret = $client_secret;
    }

    public function get_access_token($refresh_token) {
        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', array(
            'body' => array(
                'grant_type' => 'refresh_token',
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'refresh_token' => $refresh_token,
            ),
        ));

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        return isset($body['access_token']) ? $body['access_token'] : false;
    }

    public function revoke_tokens($vendor_id) {
        // Lógica para revogar tokens
        delete_user_meta($vendor_id, 'mercado_pago_access_token');
        delete_user_meta($vendor_id, 'mercado_pago_refresh_token');
        delete_user_meta($vendor_id, 'mercado_pago_token_expiration');
    }
}
