<?php
// functions/cron.php

require_once __DIR__ . '/../includes/config.php';

function renew_access_token() {
    $refresh_token = get_option('mercado_pago_refresh_token');

    $data = [
        'grant_type' => 'refresh_token',
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'refresh_token' => $refresh_token,
    ];

    $response = wp_remote_post(TOKEN_URL, [
        'body' => $data,
        'timeout' => 30,
    ]);

    if (!is_wp_error($response)) {
        $body = wp_remote_retrieve_body($response);
        $result = json_decode($body, true);

        if (!empty($result['access_token'])) {
            update_option('mercado_pago_access_token', $result['access_token']);
            update_option('mercado_pago_refresh_token', $result['refresh_token']);
        }
    }
}
