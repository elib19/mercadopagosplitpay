<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evitar acesso direto
}

// Verificar se a classe já foi definida
if ( ! class_exists( 'MercadoPagoLib' ) ) {

    class MercadoPagoLib {

        // Propriedade para armazenar o Access Token
        private $access_token;

        // Construtor que configura o Access Token
        public function __construct( $access_token = '' ) {
            // Se o Access Token não foi passado, obtê-lo das opções do plugin
            if ( empty( $access_token ) ) {
                $this->access_token = MP_Split_Helper::get_mp_access_token();
            } else {
                $this->access_token = $access_token;
            }
        }

        // Função para realizar uma chamada à API do Mercado Pago
        private function call_api( $url, $method = 'GET', $data = array() ) {
            $headers = array(
                "Authorization: Bearer $this->access_token",
                "Content-Type: application/json",
                "Accept: application/json"
            );

            $ch = curl_init( $url );
            curl_setopt( $ch, CURLOPT_CUSTOMREQUEST, $method );
            curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );
            curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers );

            if ( ! empty( $data ) ) {
                curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $data ) );
            }

            $response = curl_exec( $ch );
            curl_close( $ch );

            return json_decode( $response, true );
        }

        // Função para criar um pagamento
        public function create_payment( $payer_email, $token, $transaction_amount, $installments = 1, $application_fee = 0 ) {
            $url = "https://api.mercadopago.com/v1/payments";
            $data = array(
                "payer" => array(
                    "email" => $payer_email
                ),
                "token" => $token,
                "transaction_amount" => $transaction_amount,
                "installments" => $installments,
                "application_fee" => $application_fee,
                "payment_method_id" => "master"  // Pode ser dinâmico conforme o método de pagamento
            );

            return $this->call_api( $url, 'POST', $data );
        }

        // Função para dividir o pagamento (Split)
        public function split_payment( $transaction_amount, $application_fee, $collector_id ) {
            // Exemplo de como definir o split no pagamento
            $url = "https://api.mercadopago.com/v1/payments";
            $data = array(
                "transaction_amount" => $transaction_amount,
                "application_fee" => $application_fee,
                "collector_id" => $collector_id,
                "metadata" => array(
                    "split" => true
                )
            );

            return $this->call_api( $url, 'POST', $data );
        }

        // Função para consultar um pagamento
        public function get_payment( $payment_id ) {
            $url = "https://api.mercadopago.com/v1/payments/" . $payment_id;
            return $this->call_api( $url );
        }

    }
}
