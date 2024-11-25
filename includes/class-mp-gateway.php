<?php

class MP_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'mercado_pago';
        $this->method_title = __('Mercado Pago', 'woocommerce');
        $this->method_description = __('Integração do Mercado Pago com pagamento dividido.', 'woocommerce');

        // Carrega configurações
        $this->init_form_fields();
        $this->init_settings();

        // Define variáveis
        $this->client_id = $this->get_option('client_id');
        $this->client_secret = $this->get_option('client_secret');

        // Ações
        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'client_id' => array(
                'title' => __('Client ID', 'woocommerce'),
                'type' => 'text',
                'description' => __('Insira seu Client ID do Mercado Pago.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
            'client_secret' => array(
                'title' => __('Client Secret', 'woocommerce'),
                'type' => 'text',
                'description' => __('Insira seu Client Secret do Mercado Pago.', 'woocommerce'),
                'default' => '',
                'desc_tip' => true,
            ),
        );

        // Adiciona um espaço para mensagens de validação
        add_action('woocommerce_admin_field_mp_validation_message', function() {
            echo '<div id="mp-validation-message" style="display:none; color:red;"></div>';
        });

        // Adiciona o campo de mensagem de validação ao formulário
        $this->form_fields['validation_message'] = array(
            'type' => 'mp_validation_message',
        );
    }

   public function process_payment($order_id) {
    $order = wc_get_order($order_id);

    // Verifica se o pedido é válido
    if (!$order) {
        return array(
            'result' => 'fail',
            'message' => __('Pedido inválido.', 'woocommerce'),
        );
    }

    // Obtém o Access Token usando a classe de OAuth
    $oauth = new MP_OAuth($this->client_id, $this->client_secret);
    $access_token = $oauth->get_access_token();

    // Verifica se o Access Token foi obtido
    if (!$access_token) {
        return array(
            'result' => 'fail',
            'message' => __('Não foi possível obter o Access Token.', 'woocommerce'),
        );
    }

    // Prepara os dados do pagamento
    $payment_data = array(
        'transaction_amount' => $order->get_total(),
        'token' => $_POST['token'], // O token do cartão de crédito, obtido do frontend
        'description' => 'Pedido #' . $order->get_id(),
        'installments' => 1, // Número de parcelas
        'payment_method_id' => $_POST['payment_method_id'], // ID do método de pagamento
        'payer' => array(
            'email' => $order->get_billing_email(),
        ),
        // Adicione outros dados necessários, como dados de cobrança e envio
    );

    // URL da API de pagamento do Mercado Pago
    $url = 'https://api.mercadopago.com/v1/payments?access_token=' . $access_token;

    // Envia a solicitação de pagamento
    $response = wp_remote_post($url, array(
        'body' => json_encode($payment_data),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
    ));

    // Verifica a resposta da API
    if (is_wp_error($response)) {
        return array(
            'result' => 'fail',
            'message' => __('Erro ao processar o pagamento. Tente novamente.', 'woocommerce'),
        );
    }

    $response_body = json_decode(wp_remote_retrieve_body($response), true);

    // Verifica se o pagamento foi aprovado
    if ($response_body['status'] == 'approved') {
        // Marca o pedido como pago
        $order->payment_complete($response_body['id']); // ID do pagamento
        $order->add_order_note(__('Pagamento aprovado.', 'woocommerce'));

        // Redireciona para a página de agradecimento
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    } else {
        // Se o pagamento não foi aprovado, retorna uma mensagem de erro
        return array(
            'result' => 'fail',
            'message' => __('Pagamento não aprovado: ' . $response_body['status_detail'], 'woocommerce'),
        );
    }
}
        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
}
