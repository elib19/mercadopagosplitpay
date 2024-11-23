<?php
// includes/class-wcfm-mercado-pago-auth.php

class WCFM_Mercado_Pago_Auth {
    public function __construct() {
        // Adiciona o botão de conexão
        add_action('wcfm_marketplace_settings_fields_billing', [$this, 'add_connect_button'], 50, 2);
        // Trata a callback após a autenticação
        add_action('admin_post_mp_oauth_callback', [$this, 'handle_oauth_callback']);
    }

    // Adiciona o botão de conexão para o Mercado Pago
    public function add_connect_button($vendor_billing_fields, $vendor_id) {
        $gateway_slug = 'mercado_pago';
        $vendor_billing_fields[$gateway_slug . '_connect'] = [
            'label' => __('Conectar Mercado Pago', 'wcfmmp'),
            'type'  => 'html',
            'html'  => '<a href="' . esc_url($this->get_auth_url()) . '" class="button">' . __('Conectar', 'wcfmmp') . '</a>',
        ];
        return $vendor_billing_fields;
    }

    // Gera a URL de autenticação para o Mercado Pago
    private function get_auth_url() {
        $client_id = get_option('mercado_pago_client_id');
        return AUTH_URL . "?response_type=code&client_id=" . urlencode($client_id) . "&redirect_uri=" . urlencode(REDIRECT_URI);
    }

    // Trata a resposta da autenticação do Mercado Pago
    public function handle_oauth_callback() {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        // Verifica se o estado corresponde
        $stored_state = get_transient('mercado_pago_auth_state');
        if ($state !== $stored_state) {
            wp_die(__('Erro de autenticação: estado inválido.', 'wcfmmp'));
        }

        // Troca o código por um token de acesso
        $access_token = exchange_code_for_token($code);

        if ($access_token) {
            // Salva o token de acesso
            save_access_token($access_token);
            wp_redirect(admin_url('admin.php?page=wcfm-settings'));
            exit;
        } else {
            wp_die(__('Erro ao conectar Mercado Pago.', 'wcfmmp'));
        }
    }
}
?>
