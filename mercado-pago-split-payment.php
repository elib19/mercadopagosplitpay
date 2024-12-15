<?php

/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://juntoaqui.com.br
 * Description: Configure the payment options and accept payments with cards, ticket, and money of Mercado Pago account.
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

// Adicionar Gateway de Pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar Campos de Configuração de OAuth do Mercado Pago para o Administrador
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    // Adicionar campos para configurar as credenciais do Mercado Pago
    $payment_mercado_pago_keys = array(
        "withdrawal_" . $gateway_slug . "_client_id" => array(
            'label' => __('Client ID do Mercado Pago', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => '',
        ),
        "withdrawal_" . $gateway_slug . "_client_secret" => array(
            'label' => __('Client Secret do Mercado Pago', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => '',
        ),
        "withdrawal_" . $gateway_slug . "_redirect_uri" => array(
            'label' => __('Redirect URI', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => '',
        ),
    );

    $payment_keys = array_merge($payment_keys, $payment_mercado_pago_keys);
    return $payment_keys;
}, 50, 2);

// Função para salvar o token OAuth do vendedor
function save_mercado_pago_oauth_token($user_id, $token, $public_key, $refresh_token, $user_id_pago) {
    update_user_meta($user_id, '_mercado_pago_oauth_token', $token);
    update_user_meta($user_id, '_mercado_pago_public_key', $public_key);
    update_user_meta($user_id, '_mercado_pago_refresh_token', $refresh_token);
    update_user_meta($user_id, '_mercado_pago_user_id', $user_id_pago);
}

// Função para obter o token OAuth do vendedor
function get_mercado_pago_oauth_token($user_id) {
    return get_user_meta($user_id, '_mercado_pago_oauth_token', true);
}

// Função para processar pagamentos com divisão
function processar_pagamento_split_mercado_pago($order_id) {
    $order = wc_get_order($order_id);

    if ('mercadopago' === $order->get_payment_method()) {
        $withdraw_amount = $order->get_total();
        $vendor_id = get_post_meta($order_id, '_vendor_id', true);

        if (empty($vendor_id)) {
            return false;
        }

        // Obter o token OAuth do vendedor
        $receiver_token = get_mercado_pago_oauth_token($vendor_id);

        if (empty($receiver_token)) {
            return false;
        }

        // Configuração da requisição API Mercado Pago
        $url = 'https://api.mercadopago.com/v1/payments';
        $headers = [
            'Content-Type'  => 'application/json',
            'Authorization' => 'Bearer ' . $receiver_token,
        ];

        // Definindo o split de pagamento
        $split_details = [
            [
                'recipient_id' => 'vendedor_id_mercadopago', // Substitua pelo ID do vendedor no Mercado Pago
                'amount'        => $withdraw_amount * 0.90,
                'payer_email'   => get_user_meta($vendor_id, 'billing_email', true),
            ],
            [
                'recipient_id' => 'marketplace_id_mercadopago', // Substitua pelo ID do marketplace no Mercado Pago
                'amount'        => $withdraw_amount * 0.10,
            ]
        ];

        $body = [
            'transaction_amount' => $withdraw_amount,
            'currency_id'        => get_woocommerce_currency(),
            'description'        => 'Retirada de fundos do vendedor',
            'payment_method_id'  => 'mercadopago',
            'payer'              => [
                'email' => get_user_meta($vendor_id, 'billing_email', true),
            ],
            'additional_info'    => [
                'split' => $split_details // Adicionando a divisão de pagamento
            ]
        ];

        // Executar a requisição para a API do Mercado Pago
        $response = wp_remote_post($url, [
            'headers' => $headers,
            'body'    => json_encode($body)
        ]);

        if (is_wp_error($response)) {
            return false; // Falha na requisição
        }

        $response_body = wp_remote_retrieve_body($response);
        $response_data = json_decode($response_body, true);

        if (isset($response_data['status']) && $response_data['status'] == 'approved') {
            // Registrar o pagamento
            $order->update_status('completed'); // Marca o pedido como completado
            return true;
        }

        return false; // Falha no pagamento
    }

    return false; // Caso o pagamento não seja do Mercado Pago
}

// Vincular a função ao evento de finalização de pedido no WooCommerce
add_action('woocommerce_order_status_completed', 'processar_pagamento_split_mercado_pago', 10, 1);

// Função para gerar o link de autenticação do Mercado Pago
function get_mercado_pago_auth_link() {
    $client_id = get_option('withdrawal_mercado_pago_client_id');
    $redirect_uri = get_option('withdrawal_mercado_pago_redirect_uri'); // Obter o Redirect URI configurado

    return "https://auth.mercadopago.com.br/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}";
}

// Função para lidar com o registro do vendedor e salvar o token OAuth
add_action('user_register', 'handle_mercado_pago_oauth_registration');
function handle_mercado_pago_oauth_registration($user_id) {
    // Redirecionar o vendedor para o link de autenticação do Mercado Pago
    $auth_link = get_mercado_pago_auth_link();
    wp_redirect($auth_link);
    exit;
}

// Função para processar o retorno do Mercado Pago após a autorização
add_action('init', 'handle_mercado_pago_oauth_callback');
function handle_mercado_pago_oauth_callback() {
    if (isset($_GET['code'])) {
        $authorization_code = sanitize_text_field($_GET['code']);
        $client_id = get_option('withdrawal_mercado_pago_client_id');
        $client_secret = get_option('withdrawal_mercado_pago_client_secret');
        $redirect_uri = get_option('withdrawal_mercado_pago_redirect_uri');

        // Obter as credenciais do usuário
        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'authorization_code',
                'code' => $authorization_code,
                'redirect_uri' => $redirect_uri,
            ],
        ]);

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);
            if (isset($data['access_token'], $data['public_key'], $data['refresh_token'], $data['user_id'])) {
                // Salvar os tokens e informações do vendedor
                $vendor_id = get_current_user_id();
                save_mercado_pago_oauth_token($vendor_id, $data['access_token'], $data['public_key'], $data['refresh_token'], $data['user_id']);
                // Redirecionar para uma página de sucesso ou de volta ao perfil do vendedor
                wp_redirect(home_url('/perfil-vendedor')); // Ajuste a URL conforme necessário
                exit;
            }
        }
    }
}

// Adicionar Campos de Token OAuth para o Vendedor
add_filter('wcfm_marketplace_settings_fields_billing', function ($vendor_billing_fields, $vendor_id) {
    $gateway_slug = 'mercado_pago';
    $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
    if (!$vendor_data) $vendor_data = array();
    $mercado_pago_token = isset($vendor_data['payment'][$gateway_slug]['token']) ? esc ```php
    $mercado_pago_token = isset($vendor_data['payment'][$gateway_slug]['token']) ? esc_attr($vendor_data['payment'][$gateway_slug]['token']) : '';

    // Adicionar link para o vendedor conectar ao Mercado Pago
    $vendor_mercado_pago_billing_fields = array(
        $gateway_slug => array(
            'label' => __('Mercado Pago Token', 'wc-frontend-manager'),
            'name' => 'payment[' . $gateway_slug . '][token]',
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele paymode_field paymode_' . $gateway_slug,
            'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_' . $gateway_slug,
            'value' => $mercado_pago_token,
            'custom_attributes' => array('readonly' => 'readonly'), // Token gerado via OAuth, não editável pelo vendedor
            'desc' => sprintf('<a href="%s" target="_blank">%s</a>', 
                get_mercado_pago_auth_link(), 
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace'))
        )
    );

    $vendor_billing_fields = array_merge($vendor_billing_fields, $vendor_mercado_pago_billing_fields);
    return $vendor_billing_fields;
}, 50, 2);
