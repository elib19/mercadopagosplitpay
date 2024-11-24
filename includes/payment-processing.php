<?php

function mercado_pago_process_payment($amount, $vendor_email) {
    $client_id = get_option('wcfm_withdrawal_options[mercado_pago_client_id]');
    $secret_key = get_option('wcfm_withdrawal_options[mercado_pago_secret_key]');

    // Obter o Access Token via OAuth
    $access_token = mercado_pago_oauth_connection($client_id, $secret_key);

    if (!$access_token) {
        return false;
    }

    // Realizar pagamento utilizando o Access Token
    $url = 'https://api.mercadopago.com/v1/payments';
    $data = array(
        'transaction_amount' => $amount,
        'payer_email' => $vendor_email,
        'token' => $access_token
    );

    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => json_encode($data),
        'headers' => array('Content-Type' => 'application/json', 'Authorization' => 'Bearer ' . $access_token),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (isset($response_data['status']) && $response_data['status'] == 'approved') {
        return true;
    }

    return false;
}
