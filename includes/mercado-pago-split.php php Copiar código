// Função para processar o pagamento com split (divisão de pagamento)
function mercado_pago_process_split_payment($order_id) {
    $order = wc_get_order($order_id);
    $vendor_id = get_post_meta($order_id, '_wcfmmp_vendor_id', true);
    $amount = $order->get_total();
    $access_token = get_user_meta($vendor_id, 'mercado_pago_access_token', true);

    if (!$access_token) {
        return new WP_Error('mercado_pago_error', 'Vendedor não conectado ao Mercado Pago.');
    }

    $payment_data = array(
        'transaction_amount' => $amount,
        'payer_email' => $order->get_billing_email(),
        'items' => array( 
            // Itens do pedido
        ),
        'metadata' => array(
            'order_id' => $order_id,
        ),
        'payment_method_id' => $order->get_meta('_payment_method', true),
        'split' => array(
            'receiver_id' => $vendor_id,
            'amount' => $amount,
        ),
    );

    $url = 'https://api.mercadopago.com/v1/payments?access_token=' . $access_token;

    $response = wp_remote_post($url, array(
        'method'    => 'POST',
        'body'      => json_encode($payment_data),
        'headers'   => array('Content-Type' => 'application/json'),
    ));

    if (is_wp_error($response)) {
        return false;
    }

    $response_body = wp_remote_retrieve_body($response);
    $response_data = json_decode($response_body, true);

    if (isset($response_data['status']) && $response_data['status'] == 'approved') {
        // Processar o pagamento
        return true;
    }

    return false;
}
add_action('woocommerce_order_status_completed', 'mercado_pago_process_split_payment');
