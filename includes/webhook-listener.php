<?php

function mercado_pago_webhook_listener() {
    $input_data = json_decode(file_get_contents('php://input'), true);

    // Verifique se o evento é de pagamento aprovado
    if ($input_data['type'] === 'payment') {
        // Processar o pagamento
        // ... Adicione a lógica para marcar o pagamento como concluído no seu sistema
    }

    // Responder para Mercado Pago
    http_response_code(200);
}

// Adicionar Webhook Listener para Eventos de Pagamento
add_action('wp_ajax_mercado_pago_webhook', 'mercado_pago_webhook_listener');
add_action('wp_ajax_nopriv_mercado_pago_webhook', 'mercado_pago_webhook_listener');
