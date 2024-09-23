<?php

if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

if (!class_exists('MercadoPagoLib')) {

    class MercadoPagoLib
    {
        private $access_token;

        public function __construct($access_token = '')
        {
            if (empty($access_token)) {
                $this->access_token = MP_Split_Helper::get_mp_access_token();
            } else {
                $this->access_token = $access_token;
            }
        }

        private function call_api($url, $method = 'GET', $data = array())
        {
            $headers = array(
                "Authorization: Bearer $this->access_token",
                "Content-Type: application/json",
                "Accept: application/json"
            );

            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

            if (!empty($data)) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }

            $response = curl_exec($ch);
            curl_close($ch);

            return json_decode($response, true);
        }

        public function create_payment($email, $token, $amount, $installments = 1, $application_fee = 0)
        {
            $url = 'https://api.mercadopago.com/v1/payments';

            $data = array(
                'transaction_amount' => (float)$amount,
                'token' => $token,
                'description' => 'Pedido WooCommerce',
                'installments' => (int)$installments,
                'payer' => array(
                    'email' => $email
                ),
                'application_fee' => (float)$application_fee
            );

            return $this->call_api($url, 'POST', $data);
        }
    }
}
