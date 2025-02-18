<?php
/**
 * Plugin Name: Mercado Pago Split (WooCommerce + WCFM)
 * Plugin URI: https://github.com/elib19/mercadopagosplitpay/blob/main/mercado-pago-split-payment.php
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
<?php

// Constantes para evitar erros de digitação
define('MERCADO_PAGO_CLIENT_ID_OPTION', 'mercado_pago_client_id');
define('MERCADO_PAGO_CLIENT_SECRET_OPTION', 'mercado_pago_client_secret');
define('MERCADO_PAGO_REDIRECT_URI_OPTION', 'mercado_pago_redirect_uri');
define('MERCADO_PAGO_ACCESS_TOKEN_OPTION', 'mercado_pago_access_token'); // Para o Marketplace

// Adicionar Gateway de Pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar Campos de Configuração de OAuth do Mercado Pago para o Administrador
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    // Adicionar campos para configuração do Mercado Pago
    $payment_mercado_pago_keys = array(
        "withdrawal_{$gateway_slug}_client_id" => array(
            'label' => __('Client ID', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'value' => get_option(MERCADO_PAGO_CLIENT_ID_OPTION, ''),
        ),
        "withdrawal_{$gateway_slug}_client_secret" => array(
            'label' => __('Client Secret', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'value' => get_option(MERCADO_PAGO_CLIENT_SECRET_OPTION, ''),
        ),
        "withdrawal_{$gateway_slug}_redirect_uri" => array(
            'label' => __('Redirect URI', 'wc-multivendor-marketplace'),
            'type' => 'text',
            'class' => 'wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug . ' quebra_linha',
            'value' => get_option(MERCADO_PAGO_REDIRECT_URI_OPTION, 'https://juntoaqui.com.br/gerenciar-loja/settings/'), // URL padrão
        ),
    );

    return array_merge($payment_keys, $payment_mercado_pago_keys);
}, 50, 2);

// Salvando as Configurações do Mercado Pago (Admin)
add_action('wcfm_marketplace_settings_save_withdrawal_payment_keys', function ($wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    if (!empty($wcfm_withdrawal_options)) {
        update_option(MERCADO_PAGO_CLIENT_ID_OPTION, sanitize_text_field($wcfm_withdrawal_options["withdrawal_{$gateway_slug}_client_id"]));
        update_option(MERCADO_PAGO_CLIENT_SECRET_OPTION, sanitize_text_field($wcfm_withdrawal_options["withdrawal_{$gateway_slug}_client_secret"]));
        update_option(MERCADO_PAGO_REDIRECT_URI_OPTION, sanitize_text_field($wcfm_withdrawal_options["withdrawal_{$gateway_slug}_redirect_uri"]));
    }
}, 50);

// Adicionar Campo de Conexão para o Vendedor
add_filter('wcfm_marketplace_settings_fields_billing', function ($vendor_billing_fields, $vendor_id) {
    $gateway_slug = 'mercado_pago';
    $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
    if (!$vendor_data) $vendor_data = array();
    $mercado_pago_token = isset($vendor_data['payment'][$gateway_slug]['token']) ? esc_attr($vendor_data['payment'][$gateway_slug]['token']) : '';

    // Obter a URL de redirecionamento
    $redirect_uri = get_option(MERCADO_PAGO_REDIRECT_URI_OPTION, 'https://juntoaqui.com.br/gerenciar-loja/settings/');

    // Adicionar link para o vendedor conectar ao Mercado Pago
    $connect_text = sprintf('<a href="%s" target="_blank">%s</a>',
        'https://auth.mercadopago.com/authorization?client_id=' . get_option(MERCADO_PAGO_CLIENT_ID_OPTION) . '&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=' . urlencode($redirect_uri),
        __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace'));

    // Adicionar os campos de conexão
    $vendor_mercado_pago_billing_fields = array(
        $gateway_slug => array(
            'label' => __('Mercado Pago Token', 'wc-frontend-manager'),
            'name' => 'payment[' . $gateway_slug . '][token]',
            'type' => 'text',
            'class' => 'wcfm-text wcfm_ele paymode_field paymode_' . $gateway_slug,
            'label_class' => 'wcfm_title wcfm_ele paymode_field paymode_' . $gateway_slug,
            'value' => $mercado_pago_token,
            'custom_attributes' => array('readonly' => 'readonly'), // Token gerado via OAuth, não editável pelo vendedor
            'desc' => $connect_text
        )
    );

    // Adicionar aviso de conexão
    if ($mercado_pago_token) {
        $vendor_mercado_pago_billing_fields[$gateway_slug]['desc'] .= '<br><span style="color: green;">' . __('Você está conectado ao Mercado Pago.', 'wc-frontend-manager') . '</span>';
        // Adicionar botão de desconexão
        $vendor_mercado_pago_billing_fields[$gateway_slug]['desc'] .= '<br><a href="' . esc_url(admin_url('admin-ajax.php?action=disconnect_mercado_pago&vendor_id=' . $vendor_id)) . '" class="button" style="color: red;">' . __('Desconectar do Mercado Pago', 'wc-frontend-manager') . '</a>';
    }

    $vendor_billing_fields = array_merge($vendor_billing_fields, $vendor_mercado_pago_billing_fields);
    return $vendor_billing_fields;
}, 50, 2);

// Troca do Código pelo Token
add_action('wcfm_vendor_settings_save_billing', function ($vendor_id, $wcfm_vendor_options) {
    if (isset($_GET['code']) && isset($_GET['state'])) {
        $code = $_GET['code'];
        $redirect_uri = get_option(MERCADO_PAGO_REDIRECT_URI_OPTION);
        $client_id = get_option(MERCADO_PAGO_CLIENT_ID_OPTION);
        $client_secret = get_option(MERCADO_PAGO_CLIENT_SECRET_OPTION);

        $url = 'https://api.mercadopago.com/oauth/token';
        $data = [
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'client_id' => $client_id,
            'client_secret' => $client_secret,
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);

        $response = curl_exec($ch);
        if (curl_errno($ch)) {
            error_log("Erro cURL na troca do token: " . curl_error($ch));
        }
        curl_close($ch);

        $decoded_response = json_decode($response, true);
        if (isset($decoded_response['access_token'])) {
            $access_token = $decoded_response['access_token'];
            $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
            if (!is_array($vendor_data)) $vendor_data = array();
            $vendor_data['payment']['mercado_pago']['token'] = sanitize_text_field($access_token);
            update_user_meta($vendor_id, 'wcfmmp_profile_settings', $vendor_data);
        } else {
            error_log("Erro na troca do código pelo token: " . print_r($decoded_response, true));
        }
    }
}, 50, 2);

// Salvando o Token do Vendedor
add_action('wcfm_vendor_settings_save_billing', function ($vendor_id, $wcfm_vendor_options) {
    if (isset($wcfm_vendor_options['payment']['mercado_pago']['token'])) {
        $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
        if (!is_array($vendor_data)) $vendor_data = array();
        
        // Salvar corretamente dentro da estrutura de pagamento do WCFM
        $vendor_data['payment']['mercado_pago']['token'] = sanitize_text_field($wcfm_vendor_options['payment']['mercado_pago']['token']);
        
        update_user_meta($vendor_id, 'wcfmmp_profile_settings', $vendor_data);
    }
}, 50, 2);

// Classe para processar pagamentos de retiradas via Mercado Pago
class WCFMmp_Gateway_Mercado_Pago {
    public function process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges, $transaction_mode = 'auto') {
        global $WCFMmp;

        // Obter as configurações do Mercado Pago (Marketplace)
        $client_id = get_option(MERCADO_PAGO_CLIENT_ID_OPTION);
        $client_secret = get_option(MERCADO_PAGO_CLIENT_SECRET_OPTION);
        $access_token = get_option(MERCADO_PAGO_ACCESS_TOKEN_OPTION); // Use access token if available
        $marketplace_email = get_option('mercado_pago_marketplace_email');

        // Obter dados do vendedor
        $this->vendor_id = $vendor_id;
        $this->withdraw_amount = $withdraw_amount;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;
        $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
        $this->receiver_token = isset($vendor_data['payment']['mercado_pago']['token']) ?
        esc_attr($vendor_data['payment']['mercado_pago']['token']) : '';
        $vendor_user = get_userdata($vendor_id); // Get vendor user object
        $vendor_email = $vendor_user->user_email; // Get vendor email

        // Verificar se dados essenciais estão presentes
        if (!$this->receiver_token || !$vendor_email || !$marketplace_email || !$access_token) {
            $this->message[] = __('Dados insuficientes para processar o pagamento.', 'wc-multivendor-marketplace');
            return;
        }

        // Definir os valores para divisão
        $marketplace_fee = $withdraw_charges;
        $vendor_amount = $this->withdraw_amount - $marketplace_fee;

        // Chamada à API do Mercado Pago
        $payment_data = [
            'transaction_amount' => $this->withdraw_amount,
            'description' => 'Pagamento de Retirada',
            'payer' => [
                'email' => $marketplace_email, // E-mail do marketplace (pagador)
            ],
            'split' => [
                'receivers' => [
                    [
                        'email' => $marketplace_email,
                        'amount' => $marketplace_fee,
                    ],
                    [
                        'email' => $vendor_email, // Email do vendedor
                        'amount' => $vendor_amount,
                        'identification' => [
                            'type' => 'token',
                            'id' => $this->receiver_token, // Token OAuth do vendedor
                        ],
                    ],
                ],
            ],
        ];

        $response = $this->call_mercado_pago_api($payment_data, $access_token); // Pass marketplace access token

        if (isset($response['status']) && $response['status'] == 'approved') {
            $this->message[] = __('Pagamento processado com sucesso via Mercado Pago.', 'wc-multivendor-marketplace');
            // Update withdrawal status in your system.
        } else {
            $error_message = isset($response['message']) ? $response['message'] : __('Erro ao processar pagamento via Mercado Pago.', 'wc-multivendor-marketplace');
            $this->message[] = $error_message;
        }
    }

    // Função para chamar a API do Mercado Pago
    private function call_mercado_pago_api($payment_data, $access_token) {
        $url = 'https://api.mercadopago.com/v1/payments';

        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $access_token, // Use marketplace access token
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        $response = curl_exec($ch);

        if (curl_errno($ch)) {
            $this->message[] = __('Erro na requisição cURL: ' . curl_error($ch), 'wc-multivendor-marketplace');
            curl_close($ch);
            return null; // Return null on error
        }

        curl_close($ch);

        $decoded_response = json_decode($response, true);

        // Log the full response for debugging
        error_log("Mercado Pago API Response: " . print_r($decoded_response, true)); // Log the response

        return $decoded_response;
    }
}

?>
