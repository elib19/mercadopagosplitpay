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
    if ($_POST['mercado_pago_save']) {
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
                'client_id' => $client_id,
                'client_secret' => $client_secret,
                'code' => $code,
                'redirect_uri' => $redirect_uri
            ]
        ]);

        if (!is_wp_error($response)) {
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['access_token'])) {
                $user_id = get_current_user_id();
                update_user_meta($user_id, '_mercado_pago_access_token', $body['access_token']);
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
                'currency_id' => $order->get_currency(),
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
?>
