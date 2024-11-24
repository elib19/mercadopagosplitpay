<?php

class MP_WCFM_OAuth {

    public static function init() {
        add_action('admin_post_mp_connect', [__CLASS__, 'handle_mp_connect']);
    }

    public static function handle_mp_connect() {
        // Dados do cliente Mercado Pago
        $client_id = 'SUA_CLIENT_ID';
        $client_secret = 'SUA_CLIENT_SECRET';
        $redirect_uri = admin_url('admin-post.php?action=mp_connect');

        // Verificar se há um código de autorização
        if (isset($_GET['code'])) {
            $code = sanitize_text_field($_GET['code']);
            $response = wp_remote_post('https://api.mercadopago.com/oauth/token', [
                'body' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $client_id,
                    'client_secret' => $client_secret,
                    'code' => $code,
                    'redirect_uri' => $redirect_uri,
                ],
            ]);

            if (is_wp_error($response)) {
                wp_die('Erro ao conectar ao Mercado Pago.');
            }

            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['access_token'])) {
                // Salvar tokens no meta do vendedor
                $vendor_id = get_current_user_id();
                update_user_meta($vendor_id, '_mp_access_token', $body['access_token']);
                update_user_meta($vendor_id, '_mp_refresh_token', $body['refresh_token']);

                wp_redirect(admin_url('admin.php?page=wcfm-marketplace-settings'));
                exit;
            }
        }

        // Redirecionar em caso de erro
        wp_redirect(admin_url('admin.php?page=wcfm-marketplace-settings'));
        exit;
    }
}
