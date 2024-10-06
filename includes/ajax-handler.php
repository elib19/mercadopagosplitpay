<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Classe para manipular chamadas AJAX
 */
class Mercado_Pago_Ajax_Handler {
    public static function init() {
        add_action('wp_ajax_wcfm_vendors_ajax_process_payment', array(__CLASS__, 'process_payment'));
        add_action('wp_ajax_nopriv_wcfm_vendors_ajax_process_payment', array(__CLASS__, 'process_payment'));
    }

    /**
     * Processa o pagamento
     */
    public static function process_payment() {
        // Verifica se os dados foram enviados
        if (!isset($_POST['payment_data'])) {
            wp_send_json_error('Dados de pagamento não encontrados');
            wp_die(); // encerra corretamente a execução
        }

        // Sanitiza e decodifica os dados
        $payment_data = json_decode(stripslashes($_POST['payment_data']), true);

        // Verifica se os dados estão completos
        if (empty($payment_data['amount']) || empty($payment_data['description']) || empty($payment_data['email'])) {
            wp_send_json_error('Dados incompletos');
            wp_die();
        }

        // Obtém o ID do vendedor atual
        $vendor_id = get_current_user_id();

        // Chama a função de pagamento
        $response = self::make_payment($payment_data, $vendor_id);

        // Envia a resposta em JSON
        if ($response && isset($response->status) && $response->status === 'approved') {
            wp_send_json_success($response);
        } else {
            wp_send_json_error('Erro ao processar pagamento: ' . $response->message);
        }

        wp_die(); // encerra corretamente a execução
    }

    /**
     * Realiza o pagamento via Mercado Pago
     */
    private static function make_payment($payment_data, $vendor_id) {
        // Obtém as configurações do Mercado Pago
        $settings = mercado_pago_get_settings();
        $access_token = $settings['access_token'];
        
        // Define os parâmetros de pagamento
        $valor = floatval($payment_data['amount']);
        $descricao = sanitize_text_field($payment_data['description']);
        $email_cliente = sanitize_email($payment_data['email']);

        // Cálculo das taxas
        $marketplace_fee = $valor * 0.10; // 10% de taxa do marketplace
        $valor_vendedor = $valor - $marketplace_fee;

        // Realiza a chamada para a API do Mercado Pago
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                'transaction_amount' => $valor,
                'description' => $descricao,
                'payment_method_id' => 'pix', // ou outro método
                'payer' => array('email' => $email_cliente),
                'application_fee' => $marketplace_fee,
                'external_reference' => 'reference-' . time(),
            )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}

// Inicializa o manipulador AJAX
Mercado_Pago_Ajax_Handler::init();
