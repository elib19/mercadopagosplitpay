<?php
// oauth/auth.php

require_once __DIR__ . '/../includes/config.php';

function get_mercado_pago_auth_url() {
    $state = uniqid(); // Identificador único
    set_transient('mercado_pago_auth_state', $state, 600); // Salva o estado temporariamente

    $client_id = get_option('mercado_pago_client_id');
    $redirect_uri = REDIRECT_URI;

    return AUTH_URL . "?response_type=code&client_id=" . urlencode($client_id) . 
           "&redirect_uri=" . urlencode($redirect_uri) . "&state={$state}";
}

$auth_url = get_mercado_pago_auth_url();
if ($auth_url) {
    echo '<a href="' . esc_url($auth_url) . '" class="button">Conectar ao Mercado Pago</a>';
} else {
    echo '<p style="color: red;">Erro ao gerar URL de autenticação. Verifique as configurações.</p>';
}
?>
