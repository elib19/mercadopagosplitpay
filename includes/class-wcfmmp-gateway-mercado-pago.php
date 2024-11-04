<?php

class WCFMmp_Gateway_Mercado_Pago {
    public $id;
    public $message = array();
    public $gateway_title;
    public $payment_gateway;
    public $withdrawal_id;
    public $vendor_id;
    public $withdraw_amount = 0;
    public $currency;
    public $transaction_mode;
    private $receiver_email;
    public $test_mode = false;
    public $client_id;
    public $client_secret;

    public function __construct() {
        $this->id = 'mercado_pago';
        $this->gateway_title = __('Mercado Pago', 'wc-multivendor-marketplace');
        $this->payment_gateway = $this->id;
    }

    public function gateway_logo() {
        global $WCFMmp;
        return $WCFMmp->plugin_url . 'assets/images/' . $this->id . '.png';
    }

    public function process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges, $transaction_mode = 'auto') {
        global $WCFM, $WCFMmp;
        $this->withdrawal_id = $withdrawal_id;
        $this->vendor_id = $vendor_id;
        $this->withdraw_amount = $withdraw_amount;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;
        $this->receiver_email = $WCFMmp->wcfmmp_vendor->get_vendor_payment_account($this->vendor_id, $this->id);
        $withdrawal_test_mode = isset($WCFMmp->wcfmmp_withdrawal_options['test_mode']) ? 'yes' : 'no';
        $this->client_id = isset($WCFMmp->wcfmmp_withdrawal_options[$this->id . '_client_id']) ? $WCFMmp->wcfmmp_withdrawal_options[$this->id . '_client_id'] : '';
        $this->client_secret = isset($WCFMmp->wcfmmp_withdrawal_options[$this->id . '_secret_key']) ? $WCFMmp->wcfmmp_withdrawal_options[$this->id . '_secret_key'] : '';

        if ($withdrawal_test_mode == 'yes') {
            $this->test_mode = true;
            $this->client_id = isset($WCFMmp->wcfmmp_withdrawal_options[$this->id . '_test_client_id']) ? $WCFMmp->wcfmmp_withdrawal_options[$this->id . '_test_client_id'] : '';
            $this->client_secret = isset($WCFMmp->wcfmmp_withdrawal_options[$this->id . '_test_secret_key']) ? $WCFMmp->wcfmmp_withdrawal_options[$this->id . '_test_secret_key'] : '';
        }

        if ($this->validate_request()) {
            // Implementar a lógica de pagamento com a API do Mercado Pago
            $payment_data = [
                'transaction_amount' => $this->withdraw_amount,
                'description' => 'Pagamento de Retirada',
                'payment_method_id' => 'visa', // Exemplo: cartão Visa
                'payer' => [
                    'email' => $this->receiver_email,
                ],
                'disbursements' => [
                    [
                        'amount' => $this->withdraw_amount * 0.9, // 90% do pagamento para o vendedor
                        'external_reference' => 'Pedido #' . $this->withdrawal_id,
                        'collector_id' => $this->vendor_id // ID do vendedor
                    ],
                    [
                        'amount' => $this->withdraw_amount * 0.1, // 10% do pagamento para o administrador
                        'external_reference' => 'Pedido #' . $this->withdrawal_id,
                        'collector_id' => 261910744 // ID do administrador
                    ]
                ]
            ];

            $response = wp_remote_post('https://api.mercadopago.com/v1/payments', [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->client_secret,
                ],
                'body' => json_encode($payment_data),
            ]);

            if (is_wp_error($response)) {
                $error_message = $response->get_error_message();
                error_log("Erro no pagamento: $error_message");
                return array('status' => false, 'message' => __('Erro no processamento do pagamento', 'wc-multivendor-marketplace'));
            } else {
                $response_body = wp_remote_retrieve_body($response);
                $payment_response = json_decode($response_body, true);

                if (isset($payment_response['id'])) {
                    $WCFMmp->wcfmmp_withdraw->wcfmmp_update_withdrawal_meta($this->withdrawal_id, 'transaction_id', $payment_response['id']);
                    return array('status' => true, 'message' => __('Novo pagamento iniciado', 'wc-multivendor-marketplace'));
                } else {
                    return array('status' => false, 'message' => __('Pagamento não processado pelo Mercado Pago', 'wc-multivendor-marketplace'));
                }
            }
        } else {
            return $this->message;
        }
    }

    public function validate_request() {
        return true; // Adicione validações necessárias
    }
}
