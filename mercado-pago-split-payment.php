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

// Configurações de Mercado Pago
$access_token_admin = ''; // Token de acesso do Marketplace (Administrador)
$access_token_vendedor = 'ACCESS_TOKEN_VENDEDOR'; // Token de acesso do vendedor (obtido via OAuth)

// Dados do pedido
$pedido = [
    'items' => [
        [
            'id' => 'item-ID-1234',
            'title' => 'Meu produto',
            'currency_id' => 'BRL',
            'quantity' => 1,
            'unit_price' => 75.76
        ]
    ],
    'marketplace_fee' => 10, // Comissão do Marketplace (exemplo de 10%)
];

// Dados do comprador
$payer_email = 'comprador@example.com'; // E-mail do comprador

// Endpoint de criação de preferência para Checkout Pro ou Transparente
$url = 'https://api.mercadopago.com/checkout/preferences?access_token=' . $access_token_admin;

// Definindo os parâmetros do pagamento com Split de Pagamento
$data = [
    'items' => $pedido['items'],
    'marketplace_fee' => $pedido['marketplace_fee'],
    'payer_email' => $payer_email,
    'back_urls' => [
        'success' => 'URL_DE_SUCESSO', // URL de sucesso após pagamento
        'failure' => 'URL_DE_FALHA', // URL de falha
        'pending' => 'URL_DE_PENDING' // URL para quando o pagamento estiver pendente
    ],
    'split' => [
        [
            'recipient_id' => 'RECIPIENT_ID_VENDEDOR', // ID do vendedor obtido via OAuth
            'amount' => 68.18, // Valor que o vendedor vai receber após a comissão
            'application_fee' => 7.58 // Comissão do marketplace sobre o valor
        ],
        [
            'recipient_id' => 'RECIPIENT_ID_ADMIN', // ID do marketplace
            'amount' => 7.58, // Comissão do marketplace (exemplo: 10%)
            'application_fee' => 0 // Comissão adicional do marketplace
        ]
    ],
];

// Convertendo os dados para JSON
$json_data = json_encode($data);

// Enviando a solicitação para o Mercado Pago
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $json_data);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $access_token_admin
]);

$response = curl_exec($ch);
curl_close($ch);

// Decodificando a resposta do Mercado Pago
$response_data = json_decode($response, true);

// Exibindo o link de pagamento
if (isset($response_data['init_point'])) {
    echo 'Acesse o link de pagamento: ' . $response_data['init_point'];
} else {
    echo 'Erro ao gerar o link de pagamento';
}

// Adicionar Gateway de Pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar Campos de Configuração de OAuth do Mercado Pago para o Administrador e Vendedor
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

    // Adicionar link para configurar OAuth
    $payment_mercado_pago_keys = array(
        "withdrawal_" . $gateway_slug . "_connect" => array(
            'label' => __('Conectar ao Mercado Pago', 'wc-multivendor-marketplace'),
            'type' => 'html',
            'class' => 'wcfm_ele withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug,
            'label_class' => 'wcfm_title withdrawal_mode withdrawal_mode_live withdrawal_mode_' . $gateway_slug,
            'html' => sprintf(
                '<a href="%s" target="_blank">%s</a>',
                'https://auth.mercadopago.com/authorization?client_id=6591097965975471&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=https://juntoaqui.com.br/gerenciar-loja/settings/',
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace')
            )
        )
    );
    $payment_keys = array_merge($payment_keys, $payment_mercado_pago_keys);
    return $payment_keys;
}, 50, 2);

// Adicionar Campo de Token OAuth para o Vendedor
add_filter('wcfm_marketplace_settings_fields_billing', function ($vendor_billing_fields, $vendor_id) {
    $gateway_slug = 'mercado_pago';
    $vendor_data = get_user_meta($vendor_id, 'wcfmmp_profile_settings', true);
    if (!$vendor_data) $vendor_data = array();
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
                'https://auth.mercadopago.com/authorization?client_id=6591097965975471&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri=https://juntoaqui.com.br/gerenciar-loja/settings/', 
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
