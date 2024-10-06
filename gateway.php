<?php

if (!defined('ABSPATH')) {
    exit; // Impede acesso direto
}

class WC_Mercado_Pago_Gateway extends WC_Payment_Gateway {
    public function __construct() {
        $this->id = 'mercado_pago';
        $this->icon = ''; // URL do ícone do gateway
        $this->has_fields = false;
        $this->method_title = __('Mercado Pago', 'mercado-pago-split');
        $this->method_description = __('Aceite pagamentos via Mercado Pago.', 'mercado-pago-split');

        // Carrega as configurações
        $this->init_form_fields();
        $this->init_settings();

        // Ações
        add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
        add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_response'));
    }

    public function init_form_fields() {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Habilitar/Desabilitar', 'mercado-pago-split'),
                'type' => 'checkbox',
                'label' => __('Habilitar Mercado Pago', 'mercado-pago-split'),
                'default' => 'yes'
            ),
            'title' => array(
                'title' => __('Título', 'mercado-pago-split'),
                'type' => 'text',
                'description' => __('Título que o usuário vê durante o checkout.', 'mercado-pago-split'),
                'default' => __('Mercado Pago', 'mercado-pago-split')
            ),
            'description' => array(
                'title' => __('Descrição', 'mercado-pago-split'),
                'type' => 'textarea',
                'description' => __('Descrição do método de pagamento.', 'mercado-pago-split'),
                'default' => __('Pague usando o Mercado Pago.', 'mercado-pago-split')
            ),
        );
    }

    public function process_payment($order_id) {
        $order = wc_get_order($order_id);

        // Lógica para processar o pagamento usando o Mercado Pago
        $payment_data = array(
            // Dados necessários para o pagamento, incluindo split
        );

        // Envie os dados de pagamento para o Mercado Pago

        // Retorne o status do pagamento
        return array(
            'result' => 'success',
            'redirect' => $order->get_checkout_order_received_url()
        );
    }

    public function receipt_page($order) {
        // Página de recebimento do pagamento
    }

    public function check_response() {
        // Verifique a resposta do Mercado Pago
    }
}
