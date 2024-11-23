<?php
// includes/class-wcfm-mercado-pago-auth.php

class WCFM_Mercado_Pago_Auth {
    public function __construct() {
        add_action('wcfm_marketplace_settings_fields_billing', [$this, 'add_connect_button'], 50, 2);
        add_action('admin_post_mp_oauth_callback', [$this, 'handle_oauth_callback']);
    }

    public function add_connect_button($vendor_billing_fields, $vendor_id) {
        $gateway_slug = 'mercado_pago';
        $vendor_billing_fields[$gateway_slug . '_connect'] = [
            'label' => __('Conectar Mercado Pago', 'wcfmmp'),
            'type'  => 'html',
            'html'  => '<a href="' . $this->get_auth_url() . '" class="button">' . __('Conectar', 'wcfmmp') . '</a>',
        ];
        return $vendor_billing_fields;
    }

    private function get_auth_url() {
        $client_id = get_option('mercado_pago_client_id');
        return "https://auth.mercadopago.com/authorization?client_id={$client_id}&response_type=code&redirect_uri=" . urlencode(REDIRECT_URI);
    }

    public function handle_oauth_callback() {
        $code = $_GET['code'] ?? '';
        $state = $_GET['state'] ?? '';

        $stored_state = get_transient('mercado_pago_auth_state');
        if ($state !== $stored_state) {
            wp_die(__('Erro de autenticação: estado inválido.', 'wcfmmp'));
        }

        $access_token = exchange_code_for_token($code);

        if ($access_token) {
            save_access_token($access_token);
            wp_redirect(admin_url('admin.php?page=wcfm-settings'));
            exit;
        } else {
            wp_die(__('Erro ao conectar Mercado Pago.', 'wcfmmp'));
        }
    }
}
?>
