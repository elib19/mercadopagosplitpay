<?php

/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://juntoaqui.com.br
 * Description: Configure the payment options and accept payments with cards, ticket and money of Mercado Pago account.
 * Version: 1.0.0
 * Author: Eli Silva (hack do Mercado Pago payments for WooCommerce)
 * Author URI: https://juntoaqui.com.br
 * Text Domain: woocommerce-mercadopago-split
 * WC requires at least: 3.0.0
 * WC tested up to: 4.7.0
 * @package MercadoPago
 * @category Core
 * @author Eli Silva (hack do Mercado Pago payments for WooCommerce)
 */

// Impedir acesso direto
if (!defined('ABSPATH')) {
    exit;
}

// Função para gerar o link de autenticação do Mercado Pago
function get_mercado_pago_auth_link() {
    $client_id = get_option('mercado_pago_client_id');
    $redirect_uri = get_option('mercado_pago_redirect_uri');

    if (!$client_id || !$redirect_uri) {
        return false;
    }

    return "https://auth.mercadopago.com.br/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}";
}

// Adicionar Gateway de Pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar campos de configuração do Mercado Pago no WCFM
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    $payment_mercado_pago_keys = [
        "withdrawal_{$gateway_slug}_connect" => [
            'label' => __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace'),
            'type' => 'html',
            'class' => "wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_{$gateway_slug}",
            'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_{$gateway_slug}",
            'html' => sprintf(
                '<a href="%s" class="button wcfm-action-btn" target="_blank">%s</a>',
                get_mercado_pago_auth_link(),
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace')
            ),
        ],
    ];

    if (current_user_can('administrator')) {
        $admin_mercado_pago_keys = [
            "withdrawal_{$gateway_slug}_client_id" => [
                'label' => __('Client ID', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_client_id', ''), 
                'desc' => __('Adicione seu Client ID aqui.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway_slug}_client_secret" => [
                'label' => __('Client Secret', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_client_secret', ''), 
                'desc' => __('Adicione seu Client Secret aqui.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway_slug}_refresh_token" => [
                'label' => __('Refresh Token', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_refresh_token', ''), 
                'desc' => __('Adicione seu Refresh Token aqui.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway _pago}_redirect_uri" => [
                'label' => __('Redirect URI', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_redirect_uri', ''), 
                'desc' => __('Adicione sua Redirect URI aqui.', 'wc-multivendor-marketplace'),
            ],
        ];

        $payment_keys = array_merge($payment_keys, $admin_mercado_pago_keys);
    }

    return array_merge($payment_keys, $payment_mercado_pago_keys);
});

// Classe principal do gateway de pagamento
class WCFMmp_Gateway_Mercado_Pago {
    public function process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges, $transaction_mode = 'auto') {
        global $WCFMmp;

        // Obter o token OAuth do vendedor
        $this->vendor_id = $vendor_id;
        $this->withdraw_amount = $withdraw_amount;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;
        $this->receiver_token = get_user_meta($this->vendor_id, 'wcfmmp_profile_settings', true)['payment']['mercado_pago']['token'];

        if (empty($this->receiver_token)) {
            return false; // Se o token não foi configurado, não pode processar o pagamento
        }

        // Configuração da requisição API Mercado Pago
        $url = 'https://api.mercadopago.com/v1/payments';
        $headers = [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->receiver_token
        ];

        // Definindo a lógica de split
        $split_data = [
            [
                'recipient_id' => get_user_meta($this->vendor_id, '_mercado_pago_account_id', true), // ID da conta do vendedor
                'amount' => $this->withdraw_amount * 0.90 // 90% para o vendedor
            ],
            [
                'recipient_id' => 'MARKETPLACE_ACCOUNT_ID', // ID da conta do marketplace
                'amount' => $this->withdraw_amount * 0.10 // 10% para o marketplace
            ]
        ];

        $body = [
            'transaction_amount' => $this->withdraw_amount,
            'currency_id' => $this->currency,
            'description' => 'Retirada de fundos do vendedor',
            'payment_method_id' => 'mercadopago',
            'payer' => [
                'email' => get_user_meta($this->vendor_id, 'billing_email', true)
            ],
            'additional_info' => [
                'split' => $split_data
            ]
        ];

        // Executar a requisição
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body' => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return false; // Falha na requisição
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if (isset($response_data['status']) && $response_data['status'] == 'approved') {
            // Registrar o pagamento
            $WCFMmp->withdrawal->add_withdrawal_payment_success($withdrawal_id);
            return true;
        }

        return false; // Falha no pagamento
    }
}

// Ativar o plugin
add_action('plugins_loaded', function() {
    // Código para inicializar o plugin
});
