<?php

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
        $redirect_uri = admin_url('admin-post.php?action=mp_oauth_callback');
        return "https://auth.mercadopago.com/authorization?client_id={$client_id}&response_type=code&redirect_uri={$redirect_uri}";
    }

    public function handle_oauth_callback() {
        $code = $_GET['code'] ?? '';
        $client_id = get_option('mercado_pago_client_id');
        $client_secret = get_option('mercado_pago_secret_key');

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
            'body' => [
                'grant_type'    => 'authorization_code',
                'client_id'     => $client_id,
                'client_secret' => $client_secret,
                'code'          => $code,
                'redirect_uri'  => admin_url('admin-post.php?action=mp_oauth_callback'),
            ],
        ]);

        if (is_wp_error($response)) {
            wp_die(__('Erro ao conectar Mercado Pago.', 'wcfmmp'));
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        update_user_meta(get_current_user_id(), 'mercado_pago_access_token', $body['access_token']);
        wp_redirect(admin_url('admin.php?page=wcfm-settings'));
        exit;
    }
}
