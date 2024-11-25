<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Notification_Webhook
 */
class WC_WooMercadoPagoSplit_Notification_Webhook extends WC_WooMercadoPagoSplit_Notification_Abstract
{
    const HTTP_OK = 200;
    const HTTP_UNPROCESSABLE_ENTITY = 422;

    /**
     * WC_WooMercadoPagoSplit_Notification_Webhook constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        parent::__construct($payment);
    }

    /**
     * Notification Custom
     */
    public function check_ipn_response()
    {
        parent::check_ipn_response();
        $data = $_GET;

        if (isset($data['coupon_id']) && !empty($data['coupon_id'])) {
            if (isset($data['payer']) && !empty($data['payer'])) {
                $response = $this->mp->check_discount_campaigns($data['amount'], $data['payer'], $data['coupon_id']);
                wp_send_json($response, self::HTTP_OK);
            } else {
                $this->send_error_response(__('Please enter your email address at the billing address to use this service', 'woocommerce-mercadopago-split'), 'payer_not_found');
            }
            exit(0);
        } 

        if (empty($data['data_id']) || empty($data['type'])) {
            $this->log->write_log(__FUNCTION__, 'data_id or type not set: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            if (empty($data['id']) || empty($data['topic'])) {
                $this->log->write_log(__FUNCTION__, 'Mercado Pago Request failure: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                $this->setResponse(self::HTTP_UNPROCESSABLE_ENTITY, null, "Mercado Pago Request failure");
            }
        } else {
            if ($data['type'] == 'payment') {
                $this->handle_payment_notification($data);
            }
        }
        $this->setResponse(self::HTTP_UNPROCESSABLE_ENTITY, null, "Mercado Pago Invalid Requisition");
    }

    private function handle_payment_notification($data)
    {
        $access_token = $this->mp->get_access_token();
        $payment_info = $this->mp->get('/v1/payments/' . $data['data_id'], ['Authorization' => 'Bearer ' . $access_token], false);

        if (!is_wp_error($payment_info) && in_array($payment_info['status'], [200, 201])) {
            if ($payment_info['response']) {
                do_action('valid_mercadopago_ipn_request', $payment_info['response']);
                $this->setResponse(self::HTTP_OK, "OK", "Webhook Notification Successful");
            }
        } else {
            $this->log->write_log(__FUNCTION__, 'error when processing received data: ' . json_encode($payment_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    private function send_error_response($message, $error_code)
    {
        $response = [
            'message' => $message,
            'error' => $error_code,
            'status' => self::HTTP_UNPROCESSABLE_ENTITY,
            'cause' => []
        ];
        wp_send_json($response, self::HTTP_OK);
    }

    /**
     * @param $data
     */
    public function successful_request($data)
    {
        try {
            $order = parent::successful_request($data);
            $status = $this->process_status_mp_business($data, $order);
            $this->log->write_log(__FUNCTION__, 'Changing order status to: ' . parent::get_wc_status_for_mp_status(str_replace('_', '', $status)));
            $this->proccessStatus($status, $data, $order);
        } catch (Exception $e) {
            $this-> log->write_log(__FUNCTION__, $e->getMessage());
        }
    }

    /**
     * @param $checkout_info
     */
    public function check_and_save_customer_card($checkout_info)
    {
        $this->log->write_log(__FUNCTION__, 'checking info to create card: ' . json_encode($checkout_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $custId = $checkout_info['payer']['id'] ?? null;
        $token = $checkout_info['metadata']['token'] ?? null;
        $issuer_id = isset($checkout_info['issuer_id']) ? (int)$checkout_info['issuer_id'] : null;
        $payment_method_id = $checkout_info['payment_method_id'] ?? null;

        if ($custId && $token) {
            try {
                $this->mp->create_card_in_customer($custId, $token, $payment_method_id, $issuer_id);
            } catch (WC_WooMercadoPagoSplit_Exception $ex) {
                $this->log->write_log(__FUNCTION__, 'card creation failed: ' . json_encode($ex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * @param $data
     * @param $order
     * @return string
     */
    public function process_status_mp_business($data, $order)
    {
        $status = $data['status'] ?? 'pending';
        $total_paid = $data['transaction_details']['total_paid_amount'] ?? 0.00;
        $total_refund = $data['transaction_amount_refunded'] ?? 0.00;

        if (method_exists($order, 'update_meta_data')) {
            $order->update_meta_data('_used_gateway', get_class($this));
            if (!empty($data['payer']['email'])) {
                $order->update_meta_data(__('Buyer email', 'woocommerce-mercadopago-split'), $data['payer']['email']);
            }
            if (!empty($data['payment_type_id'])) {
                $order->update_meta_data(__('Payment method', 'woocommerce-mercadopago-split'), $data['payment_type_id']);
            }
            $order->update_meta_data(
                'Mercado Pago Split - Payment ' . $data['id'],
                '[Date ' . date('Y-m-d H:i:s', strtotime($data['date_created'])) .
                ']/[Amount ' . $data['transaction_amount'] .
                ']/[Paid ' . $total_paid .
                ']/[Refund ' . $total_refund . ']'
            );
            $order->update_meta_data('_Mercado_Pago_Payment_IDs', $data['id']);
            $order->save();
        } else {
            update_post_meta($order->id, '_used_gateway', get_class($this));
            if (!empty($data['payer']['email'])) {
                update_post_meta($order->id, __('Buyer email', 'woocommerce-mercadopago-split'), $data['payer']['email']);
            }
            if (!empty($data['payment_type_id'])) {
                update_post_meta($order->id, __('Payment method', 'woocommerce-mercadopago-split'), $data['payment_type_id']);
            }
            update_post_meta(
                $order->id,
                'Mercado Pago Split - Payment ' . $data['id'],
                '[Date ' . date('Y-m-d H:i:s', strtotime($data['date_created'])) .
                ']/[Amount ' . $data['transaction_amount'] .
                ']/[Paid ' . $total_paid .
                ']/[Refund ' . $total_refund . ']'
            );
            update_post_meta($order->id, '_Mercado_Pago_Payment_IDs', $data['id']);
        }
        return $status;
    }
}
