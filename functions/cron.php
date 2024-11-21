<?php
// functions/cron.php

require_once __DIR__ . '/../includes/config.php';

function renew_access_token() {
    $refresh_token = get_option('mercado_pago_refresh_token');

    if (empty($refresh_token)) {
        error_log('Erro: Refresh token não encontrado.');
        return;
    }

    $data = [
        'grant_type'    => 'refresh_token',
        'client_id'     => get_option('mercado_pago_client_id'),
        'client_secret' => get_option('mercado_pago_secret_key'),
        'refresh_token' => $refresh_token,
    ];

    $response = wp_remote_post(TOKEN_URL, [
        'body'    => $data,
        'timeout' => 30,
    ]);

    if (!is_wp_error($response)) {
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!empty($body['access_token'])) {
            update_option('mercado_pago_access_token', $body['access_token']);
            update_option('mercado_pago_refresh_token', $body['refresh_token']);
        }
    } else {
        error_log('Erro ao renovar token: ' . $response->get_error_message());
    }
}
