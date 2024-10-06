<?php

class Mercado_Pago_Vendor {
    public static function init() {
        add_action('wcfm_vendors_dashboard', array(__CLASS__, 'add_vendor_dashboard'));
        add_action('wp_ajax_wcfm_vendors_ajax_process_payment', array(__CLASS__, 'process_payment'));
    }

    public static function add_vendor_dashboard() {
        require_once plugin_dir_path(__FILE__) . '../views/vendor-dashboard.php';
    }

    public static function process_payment() {
        if (isset($_POST['payment_data'])) {
            $payment_data = json_decode(stripslashes($_POST['payment_data']), true);
            $vendor_id = get_current_user_id(); // ID do vendedor
            
            // Processar o pagamento
            $response = self::make_payment($payment_data, $vendor_id);
            echo json_encode($response);
            wp_die(); // encerra corretamente a execução
        }
    }

    private static function make_payment($payment_data, $vendor_id) {
        $valor = $payment_data['amount'];
        $descricao = $payment_data['description'];
        $access_token = Mercado_Pago_Settings::get_settings()['access_token'];
        
        // Cálculo das taxas
        $marketplace_fee = $valor * 0.10; // 10% de taxa do marketplace
        $valor_vendedor = $valor - $marketplace_fee;

        // Realiza a chamada para a API do Mercado Pago
        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode(array(
                'transaction_amount' => $valor,
                'description' => $descricao,
                'payment_method_id' => 'pix', // ou outro método
                'payer' => array('email' => $payment_data['email']),
                'application_fee' => $marketplace_fee,
                'external_reference' => 'reference-' . time(),
            )),
            CURLOPT_HTTPHEADER => array(
                'Authorization: Bearer ' . $access_token,
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);
        curl_close($curl);

        return json_decode($response);
    }
}
