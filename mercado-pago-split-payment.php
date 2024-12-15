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
 * 1. Configurações administrativas para as credenciais do administrador
 */
add_action('admin_menu', 'mercado_pago_split_admin_menu');
function mercado_pago_split_admin_menu() {
    add_menu_page(
        'Configuração Mercado Pago Split',
        'Mercado Pago Split',
        'manage_options',
        'mercado-pago-split',
        'mercado_pago_split_admin_page'
    );
}

function mercado_pago_split_admin_page() {
    if (isset($_POST['mercado_pago_save'])) {
        update_option('mercado_pago_client_id', sanitize_text_field($_POST['mercado_pago_client_id']));
        update_option('mercado_pago_client_secret', sanitize_text_field($_POST['mercado_pago_client_secret']));
        update_option('mercado_pago_redirect_uri', sanitize_text_field($_POST['mercado_pago_redirect_uri']));
        echo '<div class="updated"><p>Credenciais atualizadas com sucesso.</p></div>';
    }

    $client_id = get_option('mercado_pago_client_id', '');
    $client_secret = get_option('mercado_pago_client_secret', '');
    $redirect_uri = get_option('mercado_pago_redirect_uri', '');

    echo '<h1>Configuração do Mercado Pago Split</h1>';
    echo '<form method="post">';
    echo '<label>Client ID:</label>';
    echo '<input type="text" name="mercado_pago_client_id" value="' . esc_attr($client_id) . '"><br>';
    echo '<label>Client Secret:</label>';
    echo '<input type="text" name="mercado_pago_client_secret" value="' . esc_attr($client_secret) . '"><br>';
    echo '<label>Redirect URI:</label>';
    echo '<input type="text" name="mercado_pago_redirect_uri" value="' . esc_attr($redirect_uri) . '"><br>';
    echo '<input type="submit" name="mercado_pago_save" value="Salvar">';
    echo '</form>';
}

/**
 * 2. Gerar Link de Autenticação do Mercado Pago
 */
function get_mercado_pago_auth_link() {
    $client_id = get_option('mercado_pago_client_id');
    $redirect_uri = get_option('mercado_pago_redirect_uri');

    if (!$client_id || !$redirect_uri) {
        return false;
    }

    return "https://auth.mercadopago.com.br/authorization?response_type=code&client_id={$client_id}&redirect_uri={$redirect_uri}";
}

/**
 * 3. Processar o Código de Autorização e Obter Token de Acesso
 */
add_action('init', 'process_mercado_pago_auth_code');
function process_mercado_pago_auth_code() {
    if (isset($_GET['code']) && isset($_GET['state']) && $_GET['state'] === 'mercado_pago_split') {
        $code = sanitize_text_field($_GET['code']);
        $client_id = get_option('mercado_pago_client_id');
        $client_secret = get_option('mercado_pago_client_secret');
        $redirect_uri = get_option('mercado_pago_redirect_uri');

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
            'body' => [
                'grant_type' => 'authorization_code',
                'client_id' => $client_id, // Removido espaço extra
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri
            ]
        ]);

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['access_token'])) {
                $user_id = get_current_user_id();
                update_user_meta ($user_id, '_mercado_pago_access_token', $body['access_token']);
                update_user_meta($user_id, '_mercado_pago_refresh_token', $body['refresh_token']);
                update_user_meta($user_id, '_mercado_pago_token_expires', time() + $body['expires_in']);

                wp_redirect(admin_url('admin.php?page=wc-admin&auth=success'));
                exit;
            }
        }

        wp_redirect(admin_url('admin.php?page=wc-admin&auth=failed'));
        exit;
    }
}

/**
 * 4. Adicionar Campo de Autenticação para Vendedores no WCFM
 */
add_filter('wcfm_marketplace_settings_fields_payment', 'add_mercado_pago_auth_field');
function add_mercado_pago_auth_field($fields) {
    $auth_link = get_mercado_pago_auth_link();
    $fields['mercado_pago_auth'] = [
        'label' => 'Autenticação Mercado Pago',
        'type' => 'html',
        'value' => '<a href="' . esc_url($auth_link) . '" target="_blank">Conectar ao Mercado Pago</a>'
    ];
    
    // Adicionar campos para o Access Token e Account ID
    $fields['mercado_pago_access_token'] = [
        'label' => 'Access Token',
        'type' => 'text',
        'value' => get_user_meta(get_current_user_id(), '_mercado_pago_access_token', true)
    ];
    
    $fields['mercado_pago_account_id'] = [
        'label' => 'Account ID',
        'type' => 'text',
        'value' => get_user_meta(get_current_user_id(), '_mercado_pago_account_id', true)
    ];
    
    return $fields;
}

/**
 * 5. Registro do método de pagamento "Split Mercado Pago"
 */
add_filter('woocommerce_payment_gateways', 'add_split_mercado_pago_gateway');
function add_split_mercado_pago_gateway($gateways) {
    $gateways[] = 'WC_Gateway_Split_Mercado_Pago';
    return $gateways;
}

add_action('plugins_loaded', 'init_split_mercado_pago_gateway');
function init_split_mercado_pago_gateway() {
    class WC_Gateway_Split_Mercado_Pago extends WC_Payment_Gateway {

        public function __construct() {
            $this->id = 'split_mercado_pago';
            $this->method_title = 'Split Mercado Pago';
            $this->method_description = 'Divisão de pagamentos com Mercado Pago';
            $this->has_fields = false;

            $this->init_form_fields();
            $this->init_settings();

            $this->title = $this->get_option('title');
            $this->description = $this->get_option('description');

            add_action('woocommerce_update_options_payment_gateways_' . $this->id, [$this, 'process_admin_options']);
        }

        public function init_form_fields() {
            $this->form_fields = [
                'enabled' => [
                    'title' => 'Ativar/Desativar',
                    'type' => 'checkbox',
                    'label' => 'Ativar Split Mercado Pago',
                    'default' => 'yes'
                ],
                'title' => [
                    'title' => 'Título',
                    'type' => 'text',
                    'default' => 'Split Mercado Pago'
                ],
                'description' => [
                    'title' => 'Descrição',
                    'type' => 'textarea',
                    'default' => 'Pague com divisão de valores pelo Mercado Pago.'
                ]
            ];
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Adicionar lógica de integração com o Mercado Pago
            $this->process_split_payment($order);

            $order->payment_complete();
            wc_reduce_stock_levels($order_id);

            return [
                'result' => 'success',
                'redirect' => $this->get_return_url($order)
            ];
        }

        private function process_split_payment($order) {
            $total = $order->get_total();
            $vendor_id = get_post_meta($order->get_id(), '_vendor_id', true);
            $access_token = get_user_meta($vendor_id, '_mercado_pago_access_token', true);

            $split_data = [
                [
                    'recipient_id' => get_user_meta($vendor_id, '_mercado_pago_account_id', true),
                    'amount' => $total * 0.90
                ],
                [
                    'recipient_id' => 'MARKETPLACE_ACCOUNT_ID',
                    'amount' => $total * 0.10
                ]
            ];

            $body = [
                'transaction_amount' => $total,
                'currency_id' => $ order->get_currency(),
                'payer' => [
                    'email' => $order->get_billing_email()
                ],
                'transaction_details' => [
                    'products' => $order->get_items()
                ],
                'split_payment' => $split_data
            ];

            $response = wp_remote_post('https://api.mercadopago.com/v1/payments', [
                'body' => json_encode($body),
                'headers' => [
                    'Authorization' => 'Bearer ' . $access_token,
                    'Content-Type' => 'application/json'
                ]
            ]);

            return $response;
        }
    }
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
                get_mercado_pago_auth_link(),
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
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode _admin withdrawal_mode_{$gateway_slug}",
                'value' => 'https://juntoaqui.com.br/gerenciar-loja/settings/', 
                'desc' => __('Esta é a URL de redirecionamento para o Mercado Pago.', 'wc-multivendor-marketplace'),
            ],
            "withdrawal_{$gateway_slug}_test_button" => [
                'label' => __('Teste de Conexão', 'wc-multivendor-marketplace'),
                'type' => 'html',
                'class' => "wcfm_ele withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'label_class' => "wcfm_title withdrawal_mode withdrawal_mode_admin withdrawal_mode_{$gateway_slug}",
                'html' => '<button class="button wcfm-action-btn" id="mercado_pago_test_button">' . __('Testar Conexão', 'wc-multivendor-marketplace') . '</button>
                           <div id="test_result" style="display:none; margin-top: 10px;"></div>',
            ],
        ];

        $payment_keys = array_merge($payment_keys, $admin_mercado_pago_keys);
    }

    $payment_keys = array_merge($payment_keys, $payment_mercado_pago_keys);

    $payment_keys[] = [
        'type' => 'html',
        'html' => '<script>
                    jQuery(document).ready(function($) {
                        $("#mercado_pago_test_button").on("click", function() {
                            var client_id = $("#withdrawal_mercado_pago_client_id").val();
                            var client_secret = $("#withdrawal_mercado_pago_client_secret").val();
                            var refresh_token = $("#withdrawal_mercado_pago_refresh_token").val();
                            
                            $.ajax({
                                url: "https://api.mercadopago.com/v1/oauth/token",
                                method: "POST",
                                data: {
                                    grant_type: "authorization_code",
                                    client_id: client_id,
                                    client_secret: client_secret,
                                    refresh_token: refresh_token,
                                    redirect_uri: "https://juntoaqui.com.br/gerenciar-loja/settings/"
                                },
                                success: function(response) {
                                    $("#test_result").html("<div class=\'success\'>Conexão bem-sucedida!</div>").show();
                                },
                                error: function() {
                                    $("#test_result").html("<div class=\'error\'>Falha na conexão, verifique as credenciais.</div>").show();
                                }
                            });
                        });
                    });
                  </script>',
    ];

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
                get_mercado_pago_auth_link(), 
                __('Clique aqui para conectar ao Mercado Pago', 'wc-multivendor-marketplace'))
        )
    );

    $vendor_billing_fields = array_merge($vendor_billing_fields, $vendor_mercado_pago_billing_fields);
    return $vendor_billing_fields;
}, 50,  2);

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

    // Função para gerar PKCE (code_verifier e code_challenge)
    public function generate_pkce() {
        $code_verifier = $this->generate_code_verifier();
        $code_challenge = $this->generate_code_challenge($code_verifier);
        return [$code_verifier, $code_challenge];
    }

    private function generate_code_verifier() {
        $length = rand(43, 128);
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz-._~';
        $code_verifier = '';
        for ($i = 0; $i < $length; $i++) {
            $code_verifier .= $characters[rand(0, strlen($characters) - 1)];
        }
        return $code_verifier;
    }

    private function generate_code_challenge($code_verifier) {
        return rtrim(strtr(base64_encode(hash('sha256', $code_verifier, true)), '+/', '-_'), '=');
    }

    // Função para obter o Access Token usando o código de autorização
    public function get_access_token($code, $client_id, $client_secret, $redirect_uri, $code_verifier ) {
        $url = 'https://api.mercadopago.com/oauth/token';

        $headers = [
            'Accept: application/json',
            'Content-Type: application/x-www-form-urlencoded'
        ];

        $post_fields = http_build_query([
            'grant_type' => 'authorization_code',
            'client_id' => $client_id,
            'client_secret' => $client_secret,
            'code' => $code,
            'redirect_uri' => $redirect_uri,
            'code_verifier' => $code_verifier
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        // Executar cURL
        $response = curl_exec($ch);

        // Verificar se houve erro na requisição
        if(curl_errno($ch)) {
            error_log('Erro na requisição cURL: ' . curl_error($ch));
            curl_close($ch);
            return;
        }

        curl_close($ch);

        return json_decode($response, true);
    }
}

// Exemplo de uso para processar pagamento
$mercado_pago_gateway = new WCFMmp_Gateway_Mercado_Pago();
$mercado_pago_gateway->process_payment($withdrawal_id, $vendor_id, $withdraw_amount, $withdraw_charges);
?>
