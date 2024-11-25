<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Notification_IPN
 */
class WC_WooMercadoPagoSplit_Notification_IPN extends WC_WooMercadoPagoSplit_Notification_Abstract
{
    const ERROR_NO_ID_OR_TOPIC = 'No ID or TOPIC param in Request IPN';
    const ERROR_INVALID_TOPIC = 'Type of topic IPN invalid, need to be merchant_order';
    const ERROR_MERCHANT_ORDER_NOT_FOUND = 'IPN merchant_order not found';
    const ERROR_NO_PAYMENTS_FOUND = 'Not found Payments into Merchant_Order';
    const SUCCESS_MESSAGE = 'Notification IPN Successful';

    /**
     * WC_WooMercadoPagoSplit_Notification_IPN constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        parent::__construct($payment);
    }

    /**
     *  IPN
     */
    public function check_ipn_response()
    {
        parent::check_ipn_response();
        $data = $_GET;

        if (!isset($data['data_id'], $data['type'])) {
            $this->log->write_log(__FUNCTION__, self::ERROR_NO_ID_OR_TOPIC);
            $this->setResponse(422, null, __(self::ERROR_NO_ID_OR_TOPIC, 'woocommerce-mercadopago-split'));
            return;
        }

        if ($data['topic'] !== 'merchant_order') {
            $this->log->write_log(__FUNCTION__, self::ERROR_INVALID_TOPIC);
            $this->setResponse(422, null, __(self::ERROR_INVALID_TOPIC, 'woocommerce-mercadopago-split'));
            return;
        }

        $access_token = $this->mp->get_access_token();
        $ipn_info = $this->mp->get('/merchant_orders/' . $data['id'], ['Authorization' => 'Bearer ' . $access_token], false);

        if (is_wp_error($ipn_info) || !in_array($ipn_info['status'], [200, 201], true)) {
            $this->log->write_log(__FUNCTION__, self::ERROR_MERCHANT_ORDER_NOT_FOUND . ' ' . json_encode($ipn_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->setResponse(422, null, __(self::ERROR_MERCHANT_ORDER_NOT_FOUND, 'woocommerce-mercadopago-split'));
            return;
        }

        $payments = $ipn_info['response']['payments'];
        if (empty($payments)) {
            $this->log->write_log(__FUNCTION__, self::ERROR_NO_PAYMENTS_FOUND);
            $this->setResponse(422, null, __(self::ERROR_NO_PAYMENTS_FOUND, 'woocommerce-mercadopago-split'));
            return;
        }

        $ipn_info['response']['ipn_type'] = 'merchant_order';
        do_action('valid_mercadopago_ipn_request', $ipn_info['response']);
        $this->setResponse(200, "OK", __(self::SUCCESS_MESSAGE, 'woocommerce-mercadopago-split'));
    }

    /**
     * @param $data
     * @return bool|void|WC_Order|WC_Order_Refund
     * @throws WC_Data_Exception
     */
    public function successful_request($data)
    {
        try {
            $order = parent::successful_request($data);
            $processed_status = $this->process_status_mp_business($data, $order);
            $this->log->write_log(__FUNCTION__, 'Changing order status to: ' . parent::get_wc_status_for_mp_status(str_replace('_', '', $processed_status)));
            $this->proccessStatus($processed_status, $data, $order);
        } catch (Exception $e) {
            $this->setResponse(422, null, $e->getMessage());
            $this->log->write_log(__FUNCTION__, $e->getMessage());
        }
    }

    /**
     * @param $data
     * @param $order
     * @return string
     */
    public function process_status_mp_business($data, $order)
    {
        $status = 'pending';
        $ payments = $data['payments'];
        if (count($payments) === 1) {
            // If we have only one payment, just set status as its status
            $status = $payments[0]['status'];
        } elseif (count($payments) > 1) {
            // If we have multiple payments, determine overall status
            $total_paid = 0.00;
            $total_refund = 0.00;
            $total = $data['shipping_cost'] + $data['total_amount'];

            foreach ($payments as $payment) {
                if ($payment['status'] === 'approved') {
                    $total_paid += (float)$payment['total_paid_amount'];
                } elseif ($payment['status'] === 'refunded') {
                    $total_refund += (float)$payment['amount_refunded'];
                }
            }

            if ($total_paid >= $total) {
                $status = 'approved';
            } elseif ($total_refund >= $total) {
                $status = 'refunded';
            } else {
                $status = 'pending';
            }
        }

        // WooCommerce 3.0 or later.
        if (method_exists($order, 'update_meta_data')) {
            $this->update_order_meta($order, $data);
        } else {
            $this->update_order_meta_legacy($order, $data);
        }

        return $status;
    }

    /**
     * Update order meta for WooCommerce 3.0 or later
     * @param WC_Order $order
     * @param array $data
     */
    private function update_order_meta($order, $data)
    {
        $order->update_meta_data('_used_gateway', 'WC_WooMercadoPagoSplit_BasicGateway');
        if (!empty($data['payer']['email'])) {
            $order->update_meta_data(__('Buyer email', 'woocommerce-mercadopago-split'), $data['payer']['email']);
        }
        if (!empty($data['payment_type_id'])) {
            $order->update_meta_data(__('Payment method', 'woocommerce-mercadopago-split'), $data['payment_type_id']);
        }
        if (!empty($data['payments'])) {
            $this->update_payment_meta($order, $data['payments']);
        }
        $order->save();
    }

    /**
     * Update order meta for legacy WooCommerce versions
     * @param WC_Order $order
     * @param array $data
     */
    private function update_order_meta_legacy($order, $data)
    {
        update_post_meta($order->id, '_used_gateway', 'WC_WooMercadoPagoSplit_BasicGateway');
        if (!empty($data['payer']['email'])) {
            update_post_meta($order->id, __('Buyer email', 'woocommerce-mercadopago-split'), $data['payer']['email']);
        }
        if (!empty($data['payment_type_id'])) {
            update_post_meta($order->id, __('Payment method', 'woocommerce-mercadopago-split'), $data['payment_type_id']);
        }
        if (!empty($data['payments'])) {
            $this->update_payment_meta($order, $data['payments']);
        }
    }

    /**
     * Update payment metadata for the order
     * @param WC_Order $order
     * @param array $payments
     */
    private function update_payment_meta($order, $payments)
    {
        $payment_ids = [];
        foreach ($payments as $payment) {
            $payment_ids[] = $payment['id'];
            $meta_value = sprintf(
                '[Date %s]/[Amount %s]/[Paid %s]/[Refund %s]',
                date('Y-m-d H:i:s', strtotime($payment['date_created'])),
                $payment['transaction_amount'],
                $payment['total_paid_amount'],
                $payment['amount_refunded']
            );
            $order->update_meta_data('Mercado Pago Split - Payment ' . $payment['id'], $meta_value);
        }
        if (!empty($payment_ids)) {
            $order->update_meta_data('_Mercado_Pago_Payment_IDs', implode(', ', $payment_ids));
        }
 }
    }
}
