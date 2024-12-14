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
 *

if (!defined('ABSPATH')) {
    exit;
}

// Adicionar métodos de retirada ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar campos de configuração no painel do administrador e vendedores
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    if (current_user_can('administrator')) {
        $admin_fields = [
            "withdrawal_{$gateway_slug}_client_id" => [
                'label' => __('Client ID', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'value' => get_option('mercado_pago_client_id', ''),
                'desc' => __('Insira seu Client ID.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway_slug}_client_secret" => [
                'label' => __('Client Secret', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'value' => get_option('mercado_pago_client_secret', ''),
                'desc' => __('Insira seu Client Secret.', 'wc-multivendor-marketplace'),
            ]
        ];

        $payment_keys = array_merge($payment_keys, $admin_fields);
    } else {
        $vendor_fields = [
            "withdrawal_{$gateway_slug}_connect" => [
                'label' => __('Conectar ao Mercado Pago', 'wc-multivendor-marketplace'),
                'type' => 'html',
                'html' => sprintf(
                    '<a href="https://auth.mercadopago.com/authorization?client_id=%s&response_type=code&platform_id=mp&state=%s&redirect_uri=%s" class="button wcfm-action-btn" target="_blank">%s</a>',
                    esc_attr(get_option('mercado_pago_client_id', '')),
                    uniqid(),
                    esc_url(home_url('/gerenciar-loja/settings/')),
                    __('Conectar ao Mercado Pago', 'wc-multivendor-marketplace')
                ),
            ],
        ];

        $payment_keys = array_merge($payment_keys, $vendor_fields);
    }

    return $payment_keys;
}, 50, 2);

// Atualizar tokens com refresh automático
function refresh_mercado_pago_token() {
    $client_id = get_option('mercado_pago_client_id');
    $client_secret = get_option('mercado_pago_client_secret');
    $refresh_token = get_option('mercado_pago_refresh_token');

    if ($client_id && $client_secret && $refresh_token) {
        $url = 'https://api.mercadopago.com/oauth/token';
        $response = wp_remote_post($url, [
            'body' => [
                'grant_type' => 'refresh_token',
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'refresh_token' => $refresh_token,
            ],
            'headers' => ['Content-Type' => 'application/x-www-form-urlencoded'],
        ]);

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($data['access_token'])) {
                update_option('mercado_pago_access_token', $data['access_token']);
                update_option('mercado_pago_refresh_token', $data['refresh_token']);
            }
        }
    }
}

// Agendar refresh token a cada 175 dias
if (!wp_next_scheduled('mercado_pago_refresh_token_event')) {
    wp_schedule_event(time(), 'daily', 'mercado_pago_refresh_token_event');
}
add_action('mercado_pago_refresh_token_event', 'refresh_mercado_pago_token');

// Implementar lógica de split de pagamento (detecção automática de plugins Mercado Pago)
add_action('woocommerce_payment_complete', function ($order_id) {
    $order = wc_get_order($order_id);

    if (!$order) {
        return;
    }

    $payment_method = $order->get_payment_method();

    if (strpos($payment_method, 'mercado_pago') !== false) {
        // Implementar lógica para split de pagamento usando os tokens dos vendedores
        $vendor_data = get_post_meta($order_id, '_wcfmmp_order_data', true);

        if (!empty($vendor_data)) {
            foreach ($vendor_data as $vendor_id => $details) {
                $vendor_token = get_user_meta($vendor_id, 'mercado_pago_token', true);

                if ($vendor_token) {
                    // Enviar pagamento dividido
                    $split_payment_data = [
                        'access_token' => $vendor_token,
                        'amount' => $details['commission_amount'],
                        'receiver' => $details['vendor_email'],
                        'metadata' => [
                            'order_id' => $order_id,
                            'vendor_id' => $vendor_id,
                        ],
                    ];

                    $response = wp_remote_post('https://api.mercadopago.com/v1/payments', [
                        'body' => json_encode($split_payment_data),
                        'headers' => [
                            'Authorization' => 'Bearer ' . $vendor_token,
                            'Content-Type' => 'application/json',
                        ],
                    ]);

                    if (is_wp_error($response)) {
                        error_log('Erro ao enviar pagamento dividido: ' . $response->get_error_message());
                    } else {
                        $response_data = json_decode(wp_remote_retrieve_body($response), true);
                        if (empty($response_data['id'])) {
                            error_log('Erro na resposta do pagamento dividido: ' . wp_remote_retrieve_body($response));
                        }
                    }
                }
            }
        }
    }
});
