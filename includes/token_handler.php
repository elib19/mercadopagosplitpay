<?php
// includes/token_handler.php

function exchange_code_for_token($code) {
    $data = [
        'grant_type' => 'authorization_code',
        'client_id' => CLIENT_ID,
        'client_secret' => CLIENT_SECRET,
        'code' => $code,
        'redirect_uri' => REDIRECT_URI,
    ];

    $response = wp_remote_post(TOKEN_URL, [
        'body' => $data,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = wp_remote_retrieve_body($response);
    $result = json_decode($body, true);

    return $result['access_token'] ?? false;
}

function save_access_token($token) {
    update_option('mercado_pago_access_token', $token);
}
