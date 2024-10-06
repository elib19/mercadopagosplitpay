<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Funções auxiliares para o plugin Mercado Pago
 */

/**
 * Obtém as configurações do Mercado Pago.
 *
 * @return array
 */
function mercado_pago_get_settings() {
    return get_option('mercado_pago_settings');
}

/**
 * Faz o log de erros para debug.
 *
 * @param string $message
 */
function mercado_pago_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log($message);
    }
}

/**
 * Valida se as configurações do Mercado Pago estão completas.
 *
 * @return bool
 */
function mercado_pago_validate_settings() {
    $settings = mercado_pago_get_settings();
    return !empty($settings['access_token']) && !empty($settings['public_key']);
}

/**
 * Converte um valor monetário para o formato correto do Mercado Pago.
 *
 * @param float $amount
 * @return float
 */
function mercado_pago_format_amount($amount) {
    return number_format($amount, 2, '.', '');
}

/**
 * Realiza uma chamada à API do Mercado Pago.
 *
 * @param string $endpoint
 * @param array $data
 * @param string $method
 * @return mixed
 */
function mercado_pago_api_request($endpoint, $data = [], $method = 'POST') {
    $access_token = mercado_pago_get_settings()['access_token'];

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => $endpoint,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_POSTFIELDS => json_encode($data),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $access_token,
            'Content-Type: application/json',
        ],
    ]);

    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    curl_close($curl);

    if ($http_code !== 200) {
        mercado_pago_log_error('Erro na API do Mercado Pago: ' . $response);
        return false;
    }

    return json_decode($response, true);
}

/**
 * Gera uma referência externa única para os pagamentos.
 *
 * @return string
 */
function mercado_pago_generate_reference() {
    return 'ref-' . time() . '-' . uniqid();
}

/**
 * Calcula as taxas de marketplace.
 *
 * @param float $amount
 * @return float
 */
function mercado_pago_calculate_marketplace_fee($amount) {
    $fee_percentage = 0.10; // 10%
    return $amount * $fee_percentage;
}

/**
 * Adiciona um registro de pagamento ao banco de dados.
 *
 * @param int $vendor_id
 * @param array $payment_data
 */
function mercado_pago_log_payment($vendor_id, $payment_data) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mercado_pago_custom_table'; // Substitua pelo nome da tabela que você criou

    $wpdb->insert($table_name, [
        'vendor_id' => $vendor_id,
        'payment_data' => json_encode($payment_data),
        'created_at' => current_time('mysql'),
    ]);
}
