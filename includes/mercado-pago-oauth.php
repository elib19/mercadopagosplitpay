// Função para gerar o URL de autenticação OAuth
function mercado_pago_oauth_url() {
    $redirect_uri = site_url('/mercado-pago-oauth-callback');
    return "https://auth.mercadopago.com.ar/authorization?response_type=code&client_id=" . MERCADO_PAGO_CLIENT_ID . "&redirect_uri=" . $redirect_uri;
}

// Função de callback após a autenticação do vendedor
function mercado_pago_oauth_callback() {
    if (isset($_GET['code'])) {
        $code = sanitize_text_field($_GET['code']);
        $redirect_uri = site_url('/mercado-pago-oauth-callback');

        $response = wp_remote_post('https://api.mercadopago.com/oauth/token', array(
            'method'    => 'POST',
            'body'      => array(
                'grant_type'    => 'authorization_code',
                'client_id'     => MERCADO_PAGO_CLIENT_ID,
                'client_secret' => MERCADO_PAGO_CLIENT_SECRET,
                'code'          => $code,
                'redirect_uri'  => $redirect_uri
            ),
        ));

        if (is_wp_error($response)) {
            return;
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body);

        // Armazenar o Access Token e Refresh Token do vendedor
        update_user_meta(get_current_user_id(), 'mercado_pago_access_token', $data->access_token);
        update_user_meta(get_current_user_id(), 'mercado_pago_refresh_token', $data->refresh_token);
    }
}
add_action('init', 'mercado_pago_oauth_callback');

// Função para gerar o link de conexão do Mercado Pago no perfil do vendedor
function mercado_pago_connect_link() {
    $auth_url = mercado_pago_oauth_url();
    echo '<a href="' . $auth_url . '" class="button">Conectar minha conta Mercado Pago</a>';
}
add_action('wcfmmp_vendors_dashboard_after', 'mercado_pago_connect_link');
