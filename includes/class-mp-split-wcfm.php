<?php

if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

class MP_Split_WCFM
{
    public function __construct()
    {
        if (is_admin()) {
            $this->load_admin_classes();
        }

        // Inicializar funções de split de pagamento
        add_action('woocommerce_payment_complete', array($this, 'process_split_payment'));
        add_action('woocommerce_order_status_completed', array($this, 'schedule_payment_transfer'));
    }

    private function load_admin_classes()
    {
        require_once plugin_dir_path(__FILE__) . '../admin/class-mp-split-admin.php';
    }

    public function process_split_payment($order_id)
    {
        $order = wc_get_order($order_id);

        $transaction_amount = $order->get_total();
        $application_fee = MP_Split_Helper::get_application_fee();
        $payer_email = $order->get_billing_email();
        $card_token = get_post_meta($order_id, '_mp_card_token', true);
        
        $mp = new MercadoPagoLib();

        $response = $mp->create_payment($payer_email, $card_token, $transaction_amount, 1, $application_fee);

        if (isset($response['status']) && $response['status'] == 'approved') {
            $order->add_order_note(__('Pagamento aprovado via Mercado Pago Split.', 'mp-split'));
        } else {
            $order->add_order_note(__('Erro ao processar pagamento via Mercado Pago.', 'mp-split'));
        }
    }

    public function schedule_payment_transfer($order_id)
    {
        // Lógica para agendar a transferência do pagamento com intervalo de 7, 15 ou 30 dias
        $order = wc_get_order($order_id);
        $vendor_id = get_post_meta($order_id, '_vendor_id', true);
        $payment_interval = get_post_meta($vendor_id, '_mp_payment_interval', true);
        
        // Exemplo de lógica para definir o intervalo de pagamento e agendar transferência
        if ($payment_interval) {
            wp_schedule_single_event(time() + $payment_interval * DAY_IN_SECONDS, 'mp_split_transfer_payment', array($order_id));
        }
    }
}

new MP_Split_WCFM();
