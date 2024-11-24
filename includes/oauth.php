<?php

function mercado_pago_oauth_connection($client_id, $secret_key) {
    $url = 'https://api.mercadopago.com/v1/oauth/token';
    $data = array(
        'grant_type' => 'client_credentials',
        'client_id' => $client_id,
        'client_secret' => $secret_key
    );

    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => http_build_query($data),
        'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (isset($response_data['access_token'])) {
        return $response_data['access_token'];
    }

    return false;
}
