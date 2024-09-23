<?php
/**
 * Classe de integração com a API do Mercado Pago
 */
class MercadoPagoAPI {

    private $client_id;
    private $client_secret;
    private $access_token;

    public function __construct() {
        $options = get_option('mp_split_settings');
        $this->client_id = isset($options['mp_split_app_id']) ? $options['mp_split_app_id'] : '';
        $this->client_secret = isset($options['mp_split_client_secret']) ? $options['mp_split_client_secret'] : '';
        $this->access_token = isset($options['mp_split_access_token']) ? $options['mp_split_access_token'] : '';
    }

    public function get_access_token($authorization_code, $redirect_uri) {
        $url = 'https://api.mercadopago.com/oauth/token';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'redirect_uri' => $redirect_uri
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            update_option('mp_split_access_token', $body['access_token']);
            return $body;
        } else {
            return new WP_Error('oauth_error', __('Falha ao obter o token de acesso do Mercado Pago', 'mp-split'));
        }
    }

    public function create_payment($payment_data) {
        $url = 'https://api.mercadopago.com/v1/payments';
        $response = wp_remote_post($url, array(
            'method'    => 'POST',
            'headers'   => array(
                'Authorization' => 'Bearer ' . $this->access_token,
                'Content-Type'  => 'application/json',
            ),
            'body'      => json_encode($payment_data),
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }

    public function refresh_access_token($refresh_token) {
        $url = 'https://api.mercadopago.com/oauth/token';
        $response = wp_remote_post($url, array(
            'method' => 'POST',
            'headers' => array('Content-Type' => 'application/x-www-form-urlencoded'),
            'body' => array(
                'client_id' => $this->client_id,
                'client_secret' => $this->client_secret,
                'grant_type' => 'refresh_token',
                'refresh_token' => $refresh_token
            )
        ));

        if (is_wp_error($response)) {
            return $response;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (isset($body['access_token'])) {
            update_option('mp_split_access_token', $body['access_token']);
            return $body;
        } else {
            return new WP_Error('refresh_error', __('Falha ao atualizar o token de acesso do Mercado Pago', 'mp-split'));
        }
    }
}
