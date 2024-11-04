function get_access_token($user_id) {
    // Obtém as credenciais armazenadas no banco de dados para o vendedor
    $credentials = get_user_meta($user_id, 'mp_user_credentials', true);
    
    if (empty($credentials)) {
        return null; // Nenhuma credencial encontrada
    }

    // Monta a URL da API do Mercado Pago para obter o token de acesso
    $url = 'https://api.mercadopago.com/oauth/token';

    // Monta os dados para a requisição POST
    $data = array(
        'client_id' => esc_attr($credentials['client_id']),
        'client_secret' => esc_attr($credentials['client_secret']),
        'grant_type' => 'authorization_code',
        'code' => esc_attr($credentials['authorization_code']),
        'redirect_uri' => esc_attr($credentials['redirect_uri']),
    );

    // Executa a requisição HTTP
    $response = wp_remote_post($url, array(
        'body' => $data,
        'headers' => array(
            'Content-Type' => 'application/x-www-form-urlencoded'
        ),
    ));

    // Verifica se houve erro na requisição
    if (is_wp_error($response)) {
        error_log('Erro ao obter access token: ' . $response->get_error_message());
        return null; // Retorna nulo em caso de erro
    }

    // Decodifica a resposta da API
    $body = json_decode(wp_remote_retrieve_body($response), true);

    // Verifica se o token foi obtido com sucesso
    if (isset($body['access_token'])) {
        // Armazena o access token para uso posterior
        update_user_meta($user_id, 'mp_access_token', $body['access_token']);
        return $body['access_token']; // Retorna o token de acesso
    } else {
        error_log('Erro ao obter access token: ' . print_r($body, true));
        return null; // Retorna nulo se não houver access token
    }
}
