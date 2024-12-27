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

// Classe para processar pagamentos de retiradas via Mercado Pago
class WCFMmp_Gateway_Mercado_Pago {
    public function process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges, $transaction_mode = 'auto') {
        global $WCFMmp;

        // Obter o token OAuth do vendedor
        $this->vendor_id = $vendor_id;
        $this->withdraw_amount = $withdraw_amount;
        $this->currency = get_woocommerce_currency();
        $this->transaction_mode = $transaction_mode;
        $this->receiver_token = get_user_meta($this->vendor_id, 'payment[mercado_pago][token]', true);

        // Verificar se o token é válido
        if (!$this->receiver_token) {
            $this->message[] = __('Token OAuth do Mercado Pago não encontrado.', 'wc-multivendor-marketplace');
            return;
        }

        // Chamada à API do Mercado Pago com o token
        $payment_data = [
            'transaction_amount' => $this->withdraw_amount,
            'description' => 'Pagamento de Retirada',
            'payer' => [
                'email' => $this->receiver_token, // Utilizando o token como identificador do payer
            ]
        ];

        // Implementar a lógica de pagamento com a API do Mercado Pago usando o token OAuth
        $response = $this->call_mercado_pago_api($payment_data);

        if ($response['status'] == 'approved') {
            $this->message[] = __('Pagamento processado com sucesso via Mercado Pago.', 'wc-multivendor-marketplace');
        } else {
            $this->message[] = __('Erro ao processar pagamento via Mercado Pago.', 'wc-multivendor-marketplace');
        }
    }

    // Função para chamar a API do Mercado Pago
    private function call_mercado_pago_api($payment_data) {
        // URL da API do Mercado Pago
        $url = 'https://api.mercadopago.com/v1/payments';

        // Parâmetros da requisição
        $headers = [
            'Accept: application/json',
            'Content-Type: application/json',
            'Authorization: Bearer ' . $this->receiver_token // Usando o token OAuth do vendedor
        ];

        // Requisição cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Executar cURL
        $response = curl_exec($ch);

        // Verificar se houve erro na requisição
        if(curl_errno($ch)) {
            $this->message[] = __('Erro na requisição cURL: ' . curl_error($ch), 'wc-multivendor-marketplace');
            curl_close($ch);
            return;
        }

        curl_close($ch);

        // Converter a resposta em array e retornar
        return json_decode($response, true);
    }
}

// Inicializa o plugin
function mercado_pago_wcfm_init() {
    // Aqui você pode adicionar inicializações adicionais, se necessário
}
add_action('plugins_loaded', 'mercado_pago_wcfm_init');
