<?php
// oauth/callback.php

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/token_handler.php';

if (isset($_GET['code'])) {
    $code = $_GET['code'];
    $state = $_GET['state'];

    $access_token = exchange_code_for_token($code);
    if ($access_token) {
        save_access_token($access_token);
        echo "Conexão com Mercado Pago realizada com sucesso!";
    } else {
        echo "Erro ao conectar com Mercado Pago.";
    }
} else {
    echo "Nenhum código de autorização recebido.";
}
