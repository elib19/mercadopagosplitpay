<?php
/**
 * Plugin Name: Mercado Pago Split Payment
 * Description: Integração do Mercado Pago com split de pagamento para o WCFM Marketplace.
 * Version: 1.0
 * Author: Eli Silva
 * Text Domain: mercado-pago-split-payment
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Definir as credenciais do Mercado Pago
define('MERCADO_PAGO_CLIENT_ID', 'SEU_CLIENT_ID');
define('MERCADO_PAGO_CLIENT_SECRET', 'SEU_CLIENT_SECRET');

// Função para gerar o link de autenticação OAuth do Mercado Pago
function mercado_pago_oauth_url() {
    $redirect_uri = site_url('/mercado-pago-oauth-callback');
    return "https://auth.mercadopago.com.ar/authorization?response_type=code&client_id=" . MERCADO_PAGO_CLIENT_ID . "&redirect_uri=" . $redirect_uri;
}

// Função de callback após a autenticação do vendedor
function mercado_pago_oauth_callback() {
    if (isset($_GET['code'])) {
        $code = sanitize_text_field($_GET['code']);
        $redirect_uri = site_url('/mercado-pago-oauth-callback');
        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', array(
            'method' => 'POST',
            'body' => array(
                'grant_type' => 'authorization_code',
                'client_id' => MERCADO_PAGO_CLIENT_ID,
                'client_secret' => MERCADO_PAGO_CLIENT_SECRET,
                'code' => $code,
                'redirect_uri' => $redirect_uri
            ),
        ));

        if (is_wp_error($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // Armazenar o Access Token e Refresh Token do vendedor
        update_user_meta(get_current_user_id(), 'mercado_pago_access_token', $data->access_token);
        update_user_meta(get_current_user_id(), 'mercado_pago_refresh_token', $data->refresh_token);
    }
}
add_action('init', 'mercado_pago_oauth_callback');

// Função para exibir o link de conexão do Mercado Pago no perfil do vendedor
function mercado_pago_connect_link() {
    // Verifica se o vendedor já está conectado
    $access_token = get_user_meta(get_current_user_id(), 'mercado_pago_access_token', true);
    
    if (!$access_token) {
        // Se não estiver conectado, exibe o link de autenticação
        $auth_url = mercado_pago_oauth_url();
        echo '<a href="' . esc_url($auth_url) . '" class="button">Conectar minha conta Mercado Pago</a>';
    } else {
        // Se já estiver conectado, exibe uma mensagem de sucesso
        echo '<p>Você está conectado ao Mercado Pago!</p>';
    }
}
add_action('wcfmmp_vendors_dashboard_after', 'mercado_pago_connect_link');

// Função para processar o pagamento com split (divisão de pagamento)
function mercado_pago_process_split_payment($order_id) {
    $order = wc_get_order($order_id);
    $vendor_id = get_post_meta($order_id, '_wcfmmp_vendor_id', true); // ID do vendedor
    $amount = $order->get_total();
    $access_token = get_user_meta($vendor_id, 'mercado_pago_access_token', true); // Acesso do Mercado Pago do vendedor

    if (!$access_token) {
        return new WP_Error('mercado_pago_error', 'Vendedor não conectado ao Mercado Pago.');
    }

    // Dados do pagamento
    $payment_data = array(
        'transaction_amount' => $amount,
        'payer_email' => $order->get_billing_email(),
        'items' => array(
            array(
                'id' => 'item_id', 
                'title' => 'item_title', 
                'quantity' => 1,
                'unit_price' => $amount
            )
        ),
        'metadata' => array(
            'order_id' => $order_id,
        ),
        'payment_method_id' => 'mercadopago', // Método de pagamento Mercado Pago
        'split' => array(
            array(
                'recipient_id' => $vendor_id, // ID do vendedor
                'amount' => $amount, // Valor do pagamento para o vendedor
            ),
        ),
    );

    // Enviar o pagamento via API do Mercado Pago
    $url = 'https://api.mercadopago.com/v1/payments?access_token=' . $access_token;
    $response = wp_remote_post($url, array(
        'method' => 'POST',
        'body' => json_encode($payment_data),
        'headers' => array('Content-Type' => 'application/json'),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (isset($response_data['status']) && $response_data['status'] == 'approved') {
        // Processar o pagamento
        return true;
    }

    return false;
}
add_action('woocommerce_order_status_completed', 'mercado_pago_process_split_payment');

// Função para verificar se o gateway Mercado Pago está ativado
function mercado_pago_is_gateway_enabled() {
    $available_gateways = WC()->payment_gateways()->get_available_payment_gateways();
    foreach ($available_gateways as $gateway) {
        if ('mercado_pago' === $gateway->id) {
            return true;
        }
    }
    return false;
}
