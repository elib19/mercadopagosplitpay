<?php
class MP_Split_API {

    public static function process_split_payment( $order, $vendor_access_token, $application_fee ) {
        $order_id = $order->get_id();
        $total = $order->get_total();
        $customer_email = $order->get_billing_email();
        $description = "Order #$order_id";

        $payment_data = array(
            'transaction_amount' => $total,
            'description' => $description,
            'payment_method_id' => 'pix',
            'payer' => array( 'email' => $customer_email ),
            'application_fee' => $application_fee,
            'external_reference' => $order_id,
            'notification_url' => get_site_url() . '/mercadopago-webhook/',
        );

        $response = self::send_request( $payment_data, $vendor_access_token );

        return $response;
    }

    private static function send_request( $data, $access_token ) {
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode( $data ),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json'
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
