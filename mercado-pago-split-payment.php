<?php

/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://juntoaqui.com.br
 * Description: Configure payment options and accept payments with cards, ticket, and Mercado Pago account.
 * Version: 1.1.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: woocommerce-mercadopago-split
 * WC requires at least: 3.0.0
 * WC tested up to: 4.7.0
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Add Mercado Pago as a withdrawal option in WCFM.
 */
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

/**
 * Add settings fields for Mercado Pago credentials in WCFM.
 */
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    $payment_keys = array_merge($payment_keys, [
        "withdrawal_{$gateway_slug}_client_id" => [
            'label' => __('Client ID', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => $wcfm_withdrawal_options["withdrawal_{$gateway_slug}_client_id"] ?? '',
        ],
        "withdrawal_{$gateway_slug}_client_secret" => [
            'label' => __('Client Secret', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => $wcfm_withdrawal_options["withdrawal_{$gateway_slug}_client_secret"] ?? '',
        ],
        "withdrawal_{$gateway_slug}_redirect_uri" => [
            'label' => __('Redirect URI', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele',
            'label_class' => 'wcfm_title wcfm_ele',
            'value' => $wcfm_withdrawal_options["withdrawal_{$gateway_slug}_redirect_uri"] ?? '',
        ],
    ]);

    return $payment_keys;
}, 50, 2);

/**
 * Save OAuth token for a vendor.
 */
function save_mercado_pago_oauth_token($vendor_id, $token, $public_key, $refresh_token, $user_id_pago) {
    update_user_meta($vendor_id, '_mercado_pago_oauth_token', $token);
    update_user_meta($vendor_id, '_mercado_pago_public_key', $public_key);
    update_user_meta($vendor_id, '_mercado_pago_refresh_token', $refresh_token);
    update_user_meta($vendor_id, '_mercado_pago_user_id', $user_id_pago);
}

/**
 * Get OAuth token for a vendor.
 */
function get_mercado_pago_oauth_token($vendor_id) {
    return get_user_meta($vendor_id, '_mercado_pago_oauth_token', true);
}

/**
 * Process split payment with Mercado Pago.
 */
function process_split_payment($order_id) {
    $order = wc_get_order($order_id);
    
    if ('mercadopago' !== $order->get_payment_method()) {
        return false;
    }

    $vendor_id = get_post_meta($order_id, '_vendor_id', true);
    if (empty($vendor_id)) {
        return false;
    }

    $receiver_token = get_mercado_pago_oauth_token($vendor_id);
    if (empty($receiver_token)) {
        return false;
    }

    $amount = $order->get_total();

    $url = 'https://api.mercadopago.com/v1/payments';
    $headers = [
        'Content-Type' => 'application/json',
        'Authorization' => 'Bearer ' . $receiver_token,
    ];

    $split = [
        [
            'recipient_id' => get_user_meta($vendor_id, '_mercado_pago_user_id', true),
            'amount' => $amount * 0.90,
        ],
        [
            'recipient_id' => 'MARKETPLACE_ID', // Replace with your marketplace account ID
            'amount' => $amount * 0.10,
        ],
    ];

    $body = [
        'transaction_amount' => $amount,
        'currency_id' => get_woocommerce_currency(),
        'description' => 'Order #' . $order->get_id(),
        'payment_method_id' => 'mercadopago',
        'payer' => [
            'email' => $order->get_billing_email(),
        ],
        'additional_info' => ['split' => $split],
    ];

    $response = wp_remote_post($url, [
        'headers' => $headers,
        'body' => json_encode($body),
    ]);

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    if (isset($response_body['status']) && $response_body['status'] === 'approved') {
        $order->update_status('completed');
        return true;
    }

    return false;
}

add_action('woocommerce_order_status_processing', 'process_split_payment');

/**
 * Generate Mercado Pago authentication link.
 */
function get_mercado_pago_auth_link() {
    $client_id = get_option('withdrawal_mercado_pago_client_id');
    $redirect_uri = get_option('withdrawal_mercado_pago_redirect_uri');

    return "https://auth.mercadopago.com.br/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}";
}

/**
 * Handle Mercado Pago OAuth callback.
 */
add_action('init', function () {
    if (isset($_GET['code'])) {
        $code = sanitize_text_field($_GET['code']);
        $client_id = get_option('withdrawal_mercado_pago_client_id');
        $client_secret = get_option('withdrawal_mercado_pago_client_secret');
        $redirect_uri = get_option('withdrawal_mercado_pago_redirect_uri');

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
            'body' => [
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => $redirect_uri,
            ],
        ]);

        if (!is_wp_error($response)) {
            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data['access_token'])) {
                $vendor_id = get_current_user_id();
                save_mercado_pago_oauth_token(
                    $vendor_id,
                    $data['access_token'],
                    $data['public_key'],
                    $data['refresh_token'],
                    $data['user_id']
                );
                wp_redirect(home_url('/dashboard')); // Adjust the URL as needed
                exit;
            }
        }
    }
});
