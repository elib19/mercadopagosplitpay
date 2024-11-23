<?php
// oauth/auth.php

require_once __DIR__ . '/../includes/config.php';

function get_mercado_pago_auth_url() {
    $state = uniqid(); // Identificador único
    set_transient('mercado_pago_auth_state', $state, 600); // Salva o estado temporariamente

    $client_id = get_option('mercado_pago_client_id');
    return AUTH_URL . "?response_type=code&client_id=" . urlencode($client_id) . "&redirect_uri=" . urlencode(REDIRECT_URI) . "&state={$state}";
}

$auth_url = get_mercado_pago_auth_url();
if ($auth_url) {
    echo '<a href="' . esc_url($auth_url) . '" class="button">' . __('Conectar ao Mercado Pago', 'wcfmmp') . '</a>';
} else {
    echo '<p style="color: red;">Erro ao gerar URL de autenticação. Verifique as configurações.</p>';
}
?>
