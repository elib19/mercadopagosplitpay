<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Notification_Abstract
 */
abstract class WC_WooMercadoPagoSplit_Notification_Abstract
{
    public $mp;
    public $sandbox;
    public $log;
    public $payment;

    /**
     * WC_WooMercadoPagoSplit_Notification_Abstract constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        $this->payment = $payment;
        $this->mp = $payment->mp;
        $this->log = $payment->log;
        $this->sandbox = $payment->sandbox;

        add_action('woocommerce_api_' . strtolower(get_class($payment)), [$this, 'check_ipn_response']);
        add_action('valid_mercadopago_ipn_request', [$this, 'successful_request']);
        add_action('woocommerce_order_status_cancelled', [$this, 'process_cancel_order_meta_box_actions'], 10, 1);
    }

    /**
     * Maps Mercado Pago status to WooCommerce order status.
     *
     * @param string $mp_status
     * @return string
     */
    public static function get_wc_status_for_mp_status($mp_status)
    {
        $defaults = [
            'pending' => 'pending',
            'approved' => 'processing',
            'inprocess' => 'on_hold',
            'inmediation' => 'on_hold',
            'rejected' => 'failed',
            'cancelled' => 'cancelled',
            'refunded' => 'refunded',
            'chargedback' => 'refunded'
        ];
        
        return str_replace('_', '-', $defaults[$mp_status] ?? 'failed');
    }

    /**
     * Handles incoming IPN responses.
     */
    public function check_ipn_response()
    {
        @ob_clean();
        $this->log->write_log(__FUNCTION__, 'Received _GET content: ' . json_encode($_GET, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Processes successful IPN requests.
     *
     * @param array $data
     * @return bool|WC_Order|WC_Order_Refund
     */
    public function successful_request($data)
    {
        $this->log->write_log(__FUNCTION__, 'Starting to process update...');
        $order_key = $data['external_reference'] ?? null;

        if (empty($order_key)) {
            $this->log->write_log(__FUNCTION__, 'External Reference not found');
            return $this->setResponse(422, null, "External Reference not found");
        }

        $invoice_prefix = get_option('_mp_store_identificator', 'WC-');
        $id = (int)str_replace($invoice_prefix, '', $order_key);
        $order = wc_get_order($id);

        if (!$order) {
            $this->log->write_log(__FUNCTION__, 'Order is invalid');
            return $this->setResponse(422, null, "Order is invalid");
        }

        if ($order->get_id() !== $id) {
            $this->log->write_log(__FUNCTION__, 'Order error');
            return $this->setResponse(422, null, "Order error");
        }

        $this->log->write_log(__FUNCTION__, 'Updating metadata and status with data: ' . json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $order;
    }

    /**
     * Processes the order status based on the payment status.
     *
     * @param string $processed_status
     * @param array $data
     * @param WC_Order $order
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public function processStatus($processed_status, $data, $order)
    {
        $used_gateway = get_class($this->payment);

        switch ($processed_status) {
 case 'approved':
                $this->mp_rule_approved($data, $order, $used_gateway);
                break;
            case 'pending':
                $this->mp_rule_pending($data, $order, $used_gateway);
                break;
            case 'in_process':
                $this->mp_rule_in_process($data, $order);
                break;
            case 'rejected':
                $this->mp_rule_rejected($data, $order);
                break;
            case 'refunded':
                $this->mp_rule_refunded($order);
                break;
            case 'cancelled':
                $this->mp_rule_cancelled($data, $order);
                break;
            case 'in_mediation':
                $this->mp_rule_in_mediation($order);
                break;
            case 'charged_back':
                $this->mp_rule_charged_back($order);
                break;
            default:
                throw new WC_WooMercadoPagoSplit_Exception('Process Status - Invalid Status: ' . $processed_status);
        }
    }

    /**
     * Handles approved payment status.
     *
     * @param array $data
     * @param WC_Order $order
     * @param string $used_gateway
     */
    public function mp_rule_approved($data, $order, $used_gateway)
    {
        $order->add_order_note('Mercado Pago: ' . __('Payment approved.', 'woocommerce-mercadopago-split'));

        $payment_completed_status = apply_filters(
            'woocommerce_payment_complete_order_status',
            $order->needs_processing() ? 'processing' : 'completed',
            $order->get_id(),
            $order
        );

        if ($order->get_status() !== 'completed') {
            switch ($used_gateway) {
                case 'WC_WooMercadoPagoSplit_CustomGateway':
                case 'WC_WooMercadoPagoSplit_BasicGateway':
                    $order->payment_complete();
                    if ($payment_completed_status !== 'completed') {
                        $order->update_status(self::get_wc_status_for_mp_status('approved'));
                    }
                    break;
                case 'WC_WooMercadoPagoSplit_TicketGateway':
                    if (get_option('stock_reduce_mode', 'no') == 'no') {
                        $order->payment_complete();
                        if ($payment_completed_status !== 'completed') {
                            $order->update_status(self::get_wc_status_for_mp_status('approved'));
                        }
                    }
                    break;
            }
        }
    }

    /**
     * Handles pending payment status.
     *
     * @param array $data
     * @param WC_Order $order
     * @param string $used_gateway
     */
    public function mp_rule_pending($data, $order, $used_gateway)
    {
        if ($this->canUpdateOrderStatus($order)) {
            $order->update_status(self::get_wc_status_for_mp_status('pending'));
            if ($used_gateway === 'WC_WooMercadoPagoSplit_TicketGateway') {
                $notes = $order->get_customer_order_notes();
                if (count($notes) < 2) {
                    $order->add_order_note(
                        'Mercado Pago: ' . __('Waiting for the ticket payment.', 'woocommerce-mercadopago-split')
                    );
                }
            } else {
                $order->add_order_note(
                    'Mercado Pago: ' . __('The customer has not made the payment yet.', 'woocommerce-mercadopago-split')
                );
            }
        } else {
            $this->validateOrderNoteType($data, $order, 'pending');
        }
    }

    /**
     * Handles in-process payment status.
     *
     * @param array $data
     * @param WC_Order $order
     */
    public function mp_rule_in_process($data, $order)
    {
        if ($this->canUpdateOrderStatus($order)) {
            $order->update_status(
                self::get_wc_status_for_mp_status('inprocess'),
                'Mercado Pago: ' . __('Payment is pending review.', 'woocommerce-mercadopago-split')
            );
        } else {
            $this->validateOrderNoteType($data, $order, 'in_process');
        }
    }

    /**
     * Handles rejected payment status.
     *
     * @param array $data
     * @param WC_Order $order
     */
    public function mp_rule_rejected($data, $order)
    {
        if ($this->canUpdateOrderStatus($order)) {
            $order->update_status(
                self::get_wc_status_for_mp_status('rejected'),
                'Mercado Pago: ' . __('Payment was declined. The customer can try again.', 'woocommerce-mercadopago-split')
            );
        } else {
            $this->validateOrderNote Type($data, $order, 'rejected');
        }
    }

    /**
     * Handles refunded payment status.
     *
     * @param WC_Order $order
     */
    public function mp_rule_refunded($order)
    {
        $order->update_status(
            self::get_wc_status_for_mp_status('refunded'),
            'Mercado Pago: ' . __('Payment was returned to the customer.', 'woocommerce-mercadopago-split')
        );
    }

    /**
     * Handles cancelled payment status.
     *
     * @param array $data
     * @param WC_Order $order
     */
    public function mp_rule_cancelled($data, $order)
    {
        if ($this->canUpdateOrderStatus($order)) {
            $order->update_status(
                self::get_wc_status_for_mp_status('cancelled'),
                'Mercado Pago: ' . __('Payment was canceled.', 'woocommerce-mercadopago-split')
            );
        } else {
            $this->validateOrderNoteType($data, $order, 'cancelled');
        }
    }

    /**
     * Handles in-mediation payment status.
     *
     * @param WC_Order $order
     */
    public function mp_rule_in_mediation($order)
    {
        $order->update_status(self::get_wc_status_for_mp_status('inmediation'));
        $order->add_order_note(
            'Mercado Pago: ' . __('The payment is in mediation or the purchase was unknown by the customer.', 'woocommerce-mercadopago-split')
        );
    }

    /**
     * Handles charged-back payment status.
     *
     * @param WC_Order $order
     */
    public function mp_rule_charged_back($order)
    {
        $order->update_status(self::get_wc_status_for_mp_status('chargedback'));
        $order->add_order_note(
            'Mercado Pago: ' . __('The payment is in mediation or the purchase was unknown by the customer.', 'woocommerce-mercadopago-split')
        );
    }

    /**
     * Processes actions when an order is cancelled.
     *
     * @param WC_Order $order
     */
    public function process_cancel_order_meta_box_actions($order)
    {
        $order_payment = wc_get_order($order);
        $used_gateway = $order_payment->get_meta('_used_gateway', true);
        $payments = $order_payment->get_meta('_Mercado_Pago_Payment_IDs', true);

        if ($used_gateway === 'WC_WooMercadoPagoSplit_CustomGateway') {
            return;
        }

        $this->log->write_log(__FUNCTION__, 'Cancelling payments for ' . $payments);
        if ($this->mp !== null && !empty($payments)) {
            $payment_ids = explode(', ', $payments);
            foreach ($payment_ids as $p_id) {
                $response = $this->mp->cancel_payment($p_id);
                $status = $response['status'];
                $this->log->write_log(__FUNCTION__, 'Cancel payment of id ' . $p_id . ' => ' . ($status >= 200 && $status < 300 ? 'SUCCESS' : 'FAIL - ' . $response['response']['message']));
            }
        } else {
            $this->log->write_log(__FUNCTION__, 'No payments or credentials invalid');
        }
    }

    /**
     * Checks and saves customer card information.
     *
     * @param array $checkout_info
     */
    public function check_and_save_customer_card($checkout_info)
    {
        $this->log->write_log(__FUNCTION__, 'Checking info to create card: ' . json_encode($checkout_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        $custId = $checkout_info['payer']['id'] ?? null;
        $token = $checkout_info['metadata']['token'] ?? null;
        $issuer_id = $checkout_info['issuer_id'] ?? null;
        $payment_method_id = $checkout_info['payment_method_id'] ?? null;

        if ($custId && $token) {
            try {
                $this->mp->create_card_in_customer($custId, $token, $payment_method_id, $issuer_id);
            } catch (WC_WooMercadoPagoSplit_Exception $ex) {
                $this->log->write_log(__FUNCTION__, 'Card creation failed: ' . json_encode($ex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            }
        }
    }

    /**
     * Checks if the order status can be updated.
     *
     * @param WC_Order $order
     * @return bool
     */
    protected function canUpdateOrderStatus($order)
    {
        return method_exists($order, 'get_status') && ! in $order->get_status() !== 'completed' && $order->get_status() !== 'processing'; }

    /**
     * Validates the order note type based on the payment data.
     *
     * @param array $data
     * @param WC_Order $order
     * @param string $status
     */
    protected function validateOrderNoteType($data, $order, $status)
    {
        $paymentId = $data['id'] ?? null;

        if (isset($data['ipn_type']) && $data['ipn_type'] === 'merchant_order') {
            $payments = array_column($data['payments'], 'id');
            $paymentId = implode(',', $payments);
        }

        $order->add_order_note(
            sprintf(
                __('Mercado Pago: The payment %s was notified by Mercado Pago with status %s.', 'woocommerce-mercadopago-split'),
                $paymentId,
                $status
            )
        );
    }

    /**
     * Sets the HTTP response.
     *
     * @param int $code
     * @param string|null $code_message
     * @param string $body
     */
    public function setResponse($code, $code_message = null, $body)
    {
        status_header($code, $code_message);
        die($body);
    }
}
