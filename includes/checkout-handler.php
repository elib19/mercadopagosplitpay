add_action('woocommerce_thankyou', 'mp_process_checkout');

function mp_process_checkout($order_id) {
    $order = wc_get_order($order_id);
    $items = array();

    foreach ($order->get_items() as $item_id => $item) {
        $product = $item->get_product();
        $items[] = array(
            "id" => $product->get_id(),
            "title" => $product->get_name(),
            "quantity" => $item->get_quantity(),
            "unit_price" => (float) $product->get_price(),
            "currency_id" => "BRL"
        );
    }

    $payload = array(
        "items" => $items,
        "marketplace_fee" => calculate_marketplace_fee($order->get_total()),
        "payer" => array(
            "email" => $order->get_billing_email(),
        ),
        "back_urls" => array(
            "success" => $order->get_checkout_order_received_url(),
            "failure" => $order->get_checkout_payment_url()
        ),
        "auto_return" => "approved"
    );

    // Requisição para o Mercado Pago
    $response = wp_remote_post('https://api.mercadopago.com/checkout/preferences', array(
        'body' => json_encode($payload),
        'headers' => array(
            'Authorization' => 'Bearer ' . esc_attr(get_option('mp_settings')['access_token']),
            'Content-Type' => 'application/json',
        ),
    ));

    $body = json_decode(wp_remote_retrieve_body($response));

    if (!empty($body->init_point)) {
        wp_redirect($body->init_point);
        exit;
    } else {
        // Log erro
        error_log('Erro ao criar preferência de pagamento: ' . print_r($body, true));
    }
}

function calculate_marketplace_fee($total) {
    // Lógica para calcular a comissão do marketplace, por exemplo, 10%
    return $total * 0.1; // Exemplo de 10%
}
