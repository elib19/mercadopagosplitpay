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

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// Função para recuperar as credenciais do Mercado Pago
function get_mercado_pago_credentials($is_admin = false) {
    if ($is_admin) {
        // Recupera as credenciais do administrador (configuração manual)
        $public_key = get_option('mercado_pago_public_key'); // Chave pública do administrador
        $access_token = get_option('mercado_pago_access_token'); // Token de acesso do administrador
    } else {
        // Recupera as credenciais do vendedor
        $public_key = get_user_meta(get_current_user_id(), 'mercado_pago_public_key', true);
        $access_token = get_user_meta(get_current_user_id(), 'mercado_pago_access_token', true);
    }

    return [
        'public_key' => $public_key,
        'access_token' => $access_token
    ];
}

// Adicionar Gateway de Pagamento ao WCFM
add_filter('wcfm_marketplace_withdrwal_payment_methods', function ($payment_methods) {
    $payment_methods['mercado_pago'] = 'Mercado Pago';
    return $payment_methods;
});

// Adicionar Campos de Configuração de OAuth do Mercado Pago para o Administrador
add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', function ($payment_keys, $wcfm_withdrawal_options) {
    $gateway_slug = 'mercado_pago';

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
            "withdrawal_{$gateway_slug}_access_token" => [
                'label' => __('Access Token', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => get_option('mercado_pago_access_token', ''), 
                'desc' => __('Adicione seu Access Token aqui.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway_slug}_redirect_url" => [
                'label' => __('URL de Redirecionamento', 'wc-multivendor-marketplace'),
                'type' => 'text',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'value' => 'https://juntoaqui.com.br/gerenciar-loja/settings/', 
                'desc' => __('Esta é a URL de redirecionamento para o Mercado Pago.', 'wc-multivendor-marketplace'),
            ],
        ];

        $payment_keys = array_merge ```php
($payment_keys, $admin_mercado_pago_keys);
    }

    return $payment_keys;
}, 50, 2);

// Configurações de Mercado Pago
$credentials_admin = get_mercado_pago_credentials(true); // Para o administrador
$access_token_admin = $credentials_admin['access_token']; // Token do administrador

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
    'marketplace_fee' => 10, // Comissão do Marketplace (exemplo de 10%),
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
                'https://auth.mercadopago.com/authorization?client_id=6591097965975471&response_type=code&platform_id=mp&state=' . uniqid() . '&redirect_uri =https://juntoaqui.com.br/gerenciar-loja/settings/', 
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

        // Calcular a comissão do Marketplace (supondo 10% de comissão)
        $marketplace_fee = $this->withdraw_amount * 0.10;
        $vendor_amount = $this->withdraw_amount - $marketplace_fee;

        // Chamada à API do Mercado Pago com o token
        $payment_data = [
            'transaction_amount' => $vendor_amount,
            'description' => 'Pagamento de Retirada',
            'payer' => [
                'email' => $this->receiver_token, // Utilizando o token como identificador do payer
            ]
        ];

        // Implementar a lógica de pagamento com a API do Mercado Pago usando o token OAuth
        $response = $this->call_mercado_pago_api($payment_data);

        if ($response['status'] == 'approved') {
            // Registrar a comissão do marketplace
            $this->message[] = __('Pagamento processado com sucesso via Mercado Pago.', 'wc-multivendor-marketplace');
            $this->process_marketplace_fee($marketplace_fee, $withdrawal_id);  // Processar a comissão do marketplace
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

    // Função para processar a comissão do marketplace
    private function process_marketplace_fee($marketplace_fee, $withdrawal_id) {
        // Lógica para processar a comissão do marketplace, por exemplo:
        // Registrar no banco de dados ou realizar outra ação
        // Este exemplo apenas loga o valor da comissão
        error_log("Comissão do marketplace: " . $marketplace_fee);
        
        // Aqui você pode adicionar um código para registrar a comissão ou realizar uma transferência interna.
    }
}

// Exemplo de uso para processar pagamento
$mercado_pago_gateway = new WCFMmp_Gateway_Mercado_Pago();
$mercado_pago_gateway->process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges);
?>
