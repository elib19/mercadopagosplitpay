<?php
// includes/token_handler.php

require_once __DIR__ . '/config.php';

function exchange_code_for_token($code) {
    $data = [
        'grant_type'    => 'authorization_code',
        'client_id'     => get_option('mercado_pago_client_id'),
        'client_secret' => get_option('mercado_pago_secret_key'),
        'code'          => $code,
        'redirect_uri'  => REDIRECT_URI,
    ];

    $response = wp_remote_post(TOKEN_URL, [
        'body'    => $data,
        'timeout' => 30,
    ]);

    if (is_wp_error($response)) {
        error_log('Erro ao trocar o código por token: ' . $response->get_error_message());
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    return $body['access_token'] ?? false;
}

function save_access_token($token) {
    update_option('mercado_pago_access_token', $token);
}
?>
