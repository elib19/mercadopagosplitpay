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
// Evita acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Adiciona o gateway de pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adiciona campos de configuração de OAuth do Mercado Pago
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    $payment_mercado_pago_keys = array(
        "withdrawal_" . $gateway_slug . "_connect" => array(
            'label' => __('Conectar ao Mercado Pago', 'wc-multivendor-marketplace'),
            'type' => 'html',
            'class' => 'wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug,
            'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug,
            'html' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://auth.mercadopago.com/authorization?client_id=6591097965975471&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=' . esc_url( get_site_url() . '/gerenciar-loja/settings/' ),
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace')
            )
        )
    );
    $payment_keys = array_merge($payment_keys, $payment_mercado_pago_keys);
    return $payment_keys;
}, 50, 2);

// Adiciona campo de token OAuth para o vendedor
add_filter('wcfm_marketplace_settings_fields_billing', function ($vendor_billing_fields, $vendor_id) {
    $gateway_slug = 'mercado_pago';
    $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
    if (!$vendor_data) $vendor_data = array();
    $mercado_pago_token = isset($vendor_data['payment'][$gateway_slug]['token']) ? esc_attr($vendor_data['payment'][$gateway_slug]['token']) : '';

    $vendor_mercado_pago_billing_fields = array(
        $gateway_slug => array(
            'label' => __('Mercado Pago Token', 'wc-frontend-manager'),
            'name' => 'payment[' . $gateway_slug . '][token]',
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele paymode_field paymode_' . $gateway_slug,
            'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_' . $gateway_slug,
            'value' => $mercado_pago_token,
            'custom_attributes' => array('readonly' => 'readonly'),
            'desc' => sprintf('<a href="%s" target="_blank">%s</a>', 
                'https://auth.mercadopago.com/authorization?client_id=6591097965975471&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=' . esc_url( get_site_url() . '/gerenciar-loja/settings/'), 
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace'))
        )
    );

    $vendor_billing_fields = array_merge($vendor_billing_fields, $vendor_mercado_pago_billing_fields);
    return $vendor_billing_fields;
}, 50, 2);

// Função para criar a preferência de pagamento com divisão automática
function mercado_pago_create_preference($pedido, $payer_email, $recipient_id_vendedor, $recipient_id_admin) {
    $access_token_admin = ''; // Token de acesso do Marketplace (Administrador)

    // Dados do pedido
    $data = [
        'items' => $pedido['items'],
        'payer' => [
            'email' => $payer_email,
        ],
        'back_urls' => [
            'success' => 'URL_DE_SUCESSO',
            'failure' => 'URL_DE_FALHA',
            'pending' => 'URL_DE_PENDING'
        ],
        'split' => [
            [
                'recipient_id' => $recipient_id_vendedor,
                'amount' => $pedido['valor_vendedor'], // Valor que o vendedor vai receber
                'application_fee' => $pedido['comissao_marketplace'] // Comissão do marketplace
            ],
            [
                'recipient_id' => $recipient_id_admin,
                'amount' => $pedido['comissao_marketplace'], // Comissão do marketplace
                'application_fee' => 0 // Comissão adicional do marketplace
            ]
        ],
    ];

    // Chamada à API do Mercado Pago
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "https://api.mercadopago.com/checkout/preferences?access_token=" . $access_token_admin);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response);
}

// Exemplo de uso da função
add_action('woocommerce_thankyou', function($order_id) {
    $order = wc_get_order($order_id);
    $pedido = [
        'items' => [
            // Adicione os itens do pedido aqui
        ],
        'valor_vendedor' => $order->get_total() * 0.9, // Exemplo de cálculo
        'comissao_marketplace' => $order->get_total() * 0.1 // Exemplo de cálculo
    ];
    $payer_email = $order->get_billing_email();
    $recipient_id_vendedor = 'RECIPIENT_ID_VENDEDOR'; // ID do vendedor
    $recipient_id_admin = 'RECIPIENT_ID_ADMIN'; // ID do administrador

    $preference = mercado_pago_create_preference($pedido, $payer_email, $recipient_id_vendedor, $recipient_id_admin);
    // Aqui você pode redirecionar o usuário para a URL de pagamento
});

// Fim do código do plugin
