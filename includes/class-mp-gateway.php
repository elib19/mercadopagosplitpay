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
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);
        // Lógica de pagamento e divisão
        // Aqui você pode usar a classe de OAuth para obter o Access Token e processar o pagamento
        // ...

        return array(
            'result' => 'success',
            'redirect' => $this->get_return_url($order),
        );
    }
}
