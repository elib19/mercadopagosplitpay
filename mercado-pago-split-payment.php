<?php
/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://juntoaqui.com.br
 * Description: Configure payment options and accept payments with cards, ticket, and Mercado Pago account.
 * Version: 1.2.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: woocommerce-mercadopago-split
 * WC requires at least: 3.0.0
 * WC tested up to: 4.7.0
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
// Adiciona a opção de Mercado Pago no painel do vendedor
add_filter('wcfm_marketplace_withdrawal_payment_methods', function ($methods) {
    $methods['mercadopago'] = __('Mercado Pago', 'wcfm');
    return $methods;
});

// Adiciona campos para credenciais do Mercado Pago
add_filter('wcfm_marketplace_settings_fields_withdrawal', function ($fields) {
    $fields['mercadopago_email'] = array(
        'label'       => __('E-mail do Mercado Pago', 'wcfm'),
        'type'        => 'text',
        'placeholder' => __('Insira seu e-mail do Mercado Pago', 'wcfm'),
        'class'       => 'wcfm-text',
    );
    return $fields;
});

// Processa pagamento de retirada pelo Mercado Pago
add_action('wcfm_marketplace_withdraw_request_processed', function ($withdraw_id, $vendor_id, $amount, $method) {
    if ($method === 'mercadopago') {
        $vendor_data = get_user_meta($vendor_id, 'wcfm_marketplace_settings', true);
        $mercadopago_email = isset($vendor_data['mercadopago_email']) ? $vendor_data['mercadopago_email'] : '';

        if (!$mercadopago_email) {
            return;
        }

        $access_token = 'SEU_ACCESS_TOKEN_AQUI'; // Substitua pelo seu access token do Mercado Pago
        
        $payment_data = array(
            'payer_email' => $mercadopago_email,
            'transaction_amount' => (float)$amount,
            'currency_id' => 'BRL',
            'description' => 'Pagamento de retirada WCFM Marketplace',
        );

        $response = wp_remote_post('https://api.mercadopago.com/v1/payments', array(
            'body'    => json_encode($payment_data),
            'headers' => array(
                'Content-Type'  => 'application/json',
                'Authorization' => 'Bearer ' . $access_token,
            ),
        ));

        if (is_wp_error($response)) {
            error_log('Erro ao processar pagamento Mercado Pago: ' . $response->get_error_message());
        } else {
            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['id'])) {
                update_post_meta($withdraw_id, 'mercadopago_payment_id', $body['id']);
            }
        }
    }
}, 10, 4);

// Adiciona Mercado Pago como método de pagamento no checkout
add_filter('woocommerce_payment_gateways', function ($gateways) {
    $gateways[] = 'WC_Gateway_MercadoPago_Pro';
    return $gateways;
});

// Define a classe do gateway de pagamento
add_action('plugins_loaded', function () {
    class WC_Gateway_MercadoPago_Pro extends WC_Payment_Gateway {
        public function __construct() {
            $this->id                 = 'mercadopago_pro';
            $this->icon               = ''; // Ícone do Mercado Pago
            $this->method_title       = __('Mercado Pago Checkout Pro', 'woocommerce');
            $this->method_description = __('Aceite pagamentos via Mercado Pago Checkout Pro.', 'woocommerce');
            $this->supports           = array('products');

            $this->init_form_fields();
            $this->init_settings();

            $this->title       = $this->get_option('title');
            $this->description = $this->get_option('description');
            $this->access_token = $this->get_option('access_token');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled'      => array(
                    'title'   => __('Ativar/Desativar', 'woocommerce'),
                    'type'    => 'checkbox',
                    'label'   => __('Ativar Mercado Pago Checkout Pro', 'woocommerce'),
                    'default' => 'yes',
                ),
                'title'        => array(
                    'title'       => __('Título', 'woocommerce'),
                    'type'        => 'text',
                    'default'     => __('Mercado Pago', 'woocommerce'),
                ),
                'description'  => array(
                    'title'       => __('Descrição', 'woocommerce'),
                    'type'        => 'textarea',
                    'default'     => __('Pague com Mercado Pago.', 'woocommerce'),
                ),
                'access_token' => array(
                    'title'       => __('Access Token', 'woocommerce'),
                    'type'        => 'text',
                    'description' => __('Insira seu Access Token do Mercado Pago.', 'woocommerce'),
                    'default'     => '',
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            $payment_data = array(
                'transaction_amount' => (float)$order->get_total(),
                'currency_id'        => 'BRL',
                'description'        => 'Pedido #' . $order_id,
                'payer'              => array('email' => $order->get_billing_email()),
            );

            $response = wp_remote_post('https://api.mercadopago.com/v1/payments', array(
                'body'    => json_encode($payment_data),
                'headers' => array(
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $this->access_token,
                ),
            ));

            if (is_wp_error($response)) {
                wc_add_notice(__('Erro ao processar pagamento.', 'woocommerce'), 'error');
                return;
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($body['id'])) {
                $order->payment_complete($body['id']);
                return array(
                    'result'   => 'success',
                    'redirect' => $this->get_return_url($order),
                );
            } else {
                wc_add_notice(__('Pagamento não foi autorizado.', 'woocommerce'), 'error');
                return;
            }
        }
    }
});
