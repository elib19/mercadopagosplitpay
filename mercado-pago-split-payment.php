<?php

/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://juntoaqui.com.br
 * Description: Configure the payment options and accept payments with cards, ticket, and money of Mercado Pago account.
 * Version: 1.0.0
 * Author: Eli Silva (hack do Mercado Pago payments for WooCommerce)
 * Author URI: https://juntoaqui.com.br
 * Text Domain: woocommerce-mercadopago-split
 * Domain Path: /i18n/languages/
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

/**
 * Adicionar Gateway de Pagamento ao WCFM
 */
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

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
                'https://auth.mercadopago.com/authorization?client_id=YOUR_CLIENT_ID&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=https://juntoaqui.com.br/gerenciar-loja/settings/',
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
            "withdrawal_{$gateway_slug}_access_token" => [
                'label' => __('Access Token', 'wc-multivendor-marketplace'),
                'type' ```php
                => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_access_token', ''), 
                'desc' => __('Adicione seu Access Token aqui.', 'wc-multivendor-marketplace'),
            ],
        ];

        $payment_keys = array_merge($payment_keys, $admin_mercado_pago_keys);
    }

    return array_merge($payment_keys, $payment_mercado_pago_keys);
});

/**
 * Função para obter um novo Access Token usando o Refresh Token
 */
function get_new_access_token($refresh_token) {
    $client_id = get_option('mercado_pago_client_id');
    $client_secret = get_option('mercado_pago_client_secret');

    $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
        'body' => [
            'grant_type' => 'refresh_token',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'refresh_token' => $refresh_token,
        ],
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($body['access_token'])) {
        update_option('mercado_pago_access_token', $body['access_token']);
        return $body['access_token'];
    }

    return false;
}

/**
 * Exemplo de uso do Refresh Token
 */
function process_payment($order_id) {
    $order = wc_get_order($order_id);
    $access_token = get_option('mercado_pago_access_token');
    $refresh_token = get_option('mercado_pago_refresh_token');

    // Verifica se o Access Token está expirado e tenta renová-lo
    if (is_token_expired($access_token)) {
        $new_access_token = get_new_access_token($refresh_token);
        if ($new_access_token) {
            $access_token = $new_access_token;
        } else {
            // Lidar com erro de renovação do token
            return;
        }
    }

    // Processar o pagamento com o Access Token válido
    // Aqui você implementaria a lógica para processar o pagamento usando a API do Mercado Pago
}

/**
 * Função para verificar se o Access Token está expirado
 */
function is_token_expired($access_token) {
    // Implementar lógica para verificar se o token está expirado
    // Isso pode incluir verificar a data de expiração armazenada ou fazer uma chamada à API
    return false; // Placeholder
}
`` ```php
}
