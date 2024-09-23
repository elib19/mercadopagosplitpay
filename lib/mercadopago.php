<?php
/**
 * Classe de integração com a API do Mercado Pago
 */
class MercadoPago {
    private $app_id;
    private $client_secret;
    private $access_token;

    public function __construct($app_id, $client_secret) {
        $this->app_id = $app_id;
        $this->client_secret = $client_secret;
        $this->authenticate();
    }

    /**
     * Autentica e obtém o token de acesso.
     */
    private function authenticate() {
        $url = 'https://api.mercadopago.com/oauth/token';
        $data = array(
            'client_id' => $this->app_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        );

        $response = wp_remote_post($url, array(
            'body' => $data,
            'headers' => array('Accept' => 'application/json')
        ));

        if (is_wp_error($response)) {
            $this->log_error('Erro ao autenticar: ' . $response->get_error_message());
            return;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            $this->access_token = $body['access_token'];
        } else {
            $this->log_error('Erro ao obter token: ' . print_r($body, true));
        }
    }

    /**
     * Cria um pagamento.
     *
     * @param array $payment_data Dados do pagamento.
     * @return array|false Resposta da API ou falso em caso de erro.
     */
    public function createPayment($payment_data) {
        $url = 'https://api.mercadopago.com/v1/payments';

        $response = wp_remote_post($url, array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type' => 'application/json'
            ),
            'body' => json_encode($payment_data)
        ));

        if (is_wp_error($response)) {
            $this->log_error('Erro ao criar pagamento: ' . $response->get_error_message());
            return false;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    /**
     * Registra um erro no log do WordPress.
     *
     * @param string $message Mensagem de erro a ser registrada.
     */
    private function log_error($message) {
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[MercadoPago Error] ' . $message);
        }
    }
}
