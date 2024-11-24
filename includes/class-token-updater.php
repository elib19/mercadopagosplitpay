<?php

class MP_Token_Updater {

    public static function update_tokens($vendor_id) {
        $refresh_token = get_user_meta($vendor_id, '_mp_refresh_token', true);

        if (!$refresh_token) {
            return false;
        }

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => 'SUA_CLIENT_ID',
                'client_secret' => 'SUA_CLIENT_SECRET',
                'refresh_token' => $refresh_token,
            ],
        ]);

        if (is_wp_error($response)) {
            return false;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($body['access_token'])) {
            update_user_meta($vendor_id, '_mp_access_token', $body['access_token']);
            update_user_meta($vendor_id, '_mp_refresh_token', $body['refresh_token']);
            return true;
        }

        return false;
    }
}
