<?php
/**
 * Plugin Name: Mercado Pago WCFM Integration
 * Plugin URI: https://juntoaqui.com.br
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0.0
 * Author: Eli Silva
 * Author URI: https://juntoaqui.com.br
 * Text Domain: mercado-pago-wcfm
 */

// Incluir os arquivos de funcionalidade
require_once plugin_dir_path(__FILE__) . 'includes/oauth.php';
require_once plugin_dir_path(__FILE__) . 'includes/payment-processing.php';
require_once plugin_dir_path(__FILE__) . 'includes/webhook-listener.php';

// Adicionar o Gateway de Pagamento no WCFM Marketplace
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar Campos de Configuração da API do Mercado Pago
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';
    $client_id = isset($wcfm_withdrawal_options[$gateway_slug . '_client_id']) ? $wcfm_withdrawal_options[$gateway_slug . '_client_id'] : '';
    $secret_key = isset($wcfm_withdrawal_options[$gateway_slug . '_secret_key']) ? $wcfm_withdrawal_options[$gateway_slug . '_secret_key'] : '';
    $access_token = isset($wcfm_withdrawal_options[$gateway_slug . '_access_token']) ? $wcfm_withdrawal_options[$gateway_slug . '_access_token'] : '';
    $payment_mercado_pago_keys = array(
        "withdrawal_" . $gateway_slug . "_client_id" => array(
            'label' => __('Mercado Pago Client ID', 'wc-multivendor-marketplace'),
            'name' => 'wcfm_withdrawal_options[' . $gateway_slug . '_client_id]',
            'type' => 'text',
            'value' => $client_id
        ),
        "withdrawal_" . $gateway_slug . "_secret_key" => array(
            'label' => __('Mercado Pago Secret Key', 'wc-multivendor-marketplace'),
            'name' => 'wcfm_withdrawal_options[' . $gateway_slug . '_secret_key]',
            'type' => 'text',
            'value' => $secret_key
        ),
        "withdrawal_" . $gateway_slug . "_access_token" => array(
            'label' => __('Mercado Pago Access Token', 'wc-multivendor-marketplace'),
            'name' => 'wcfm_withdrawal_options[' . $gateway_slug . '_access_token]',
            'type' => 'text',
            'value' => $access_token
        )
    );
    $payment_keys = array_merge($payment_keys, $payment_mercado_pago_keys);
    return $payment_keys;
}, 50, 2);
