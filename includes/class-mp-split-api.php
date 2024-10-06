<?php
class MP_Split_API {

    // Função para processar o split de pagamento
    public static function process_split_payment( $order, $vendor_id ) {
        $access_token = MP_Split_Helper::get_vendor_mp_credentials( $vendor_id );
        $valor = $order->get_total(); // Valor total do pedido
        $porcentagem_comissao = 10; // Percentual de comissão

        $comissao_vendedor = $valor * ($porcentagem_comissao / 100);
        $valor_loja = floatval( number_format( $valor - $comissao_vendedor, 2, '.', '' ) );

        $data = array(
            'transaction_amount' => $valor,
            'description' => 'Pagamento de Pedido',
            'payment_method_id' => 'pix', // Pode ser alterado conforme o método de pagamento
            'payer' => array(
                'email' => 'clientemail@gmail.com', // Email do cliente
            ),
            'binary_mode' => true,
            'application_fee' => $valor_loja,
            'external_reference' => $order->get_order_number(),
            'notification_url' => 'https://lorde.dev',
            'additional_info' => array(
                'items' => array(
                    array(
                        'id' => '1',
                        'title' => 'Pagamento de Pedido',
                        'description' => 'Descrição do pedido',
                        'quantity' => 1,
                        'unit_price' => $valor,
                    ),
                ),
            ),
            'sponsor_id' => get_user_meta( $vendor_id, 'mp_sponsor_id', true ),
        );

        // Chamada para a API do Mercado Pago
        $response = wp_remote_post( 'https://api.mercadopago.com/v1/payments', array(
            'headers' => array(
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode( $data ),
        ));

        return json_decode( wp_remote_retrieve_body( $response ) );
    }
}
