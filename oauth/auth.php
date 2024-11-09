<?php
// oauth/auth.php

require_once __DIR__ . '/../includes/config.php';

function get_mercado_pago_auth_url() {
    $state = uniqid(); // Identificador único
    return AUTH_URL . "?response_type=code&client_id=" . CLIENT_ID . "&redirect_uri=" . urlencode(REDIRECT_URI) . "&state={$state}";
}

?>
<a href="<?php echo get_mercado_pago_auth_url(); ?>">Conectar ao Mercado Pago</a>
