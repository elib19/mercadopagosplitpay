<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_WooMercadoPagoSplit_PreferenceBasic extends WC_WooMercadoPagoSplit_PreferenceAbstract
{
    /**
     * WC_WooMercadoPagoSplit_PreferenceBasic constructor.
     *
     * @param object $payment
     * @param object $order
     */
    public function __construct($payment, $order)
    {
        parent::__construct($payment, $order);
        
        // Prepare preference
        $this->preference = $this->make_commum_preference();
        $this->preference['items'] = $this->items;
        $this->preference['payer'] = $this->get_payer_basic();
        $this->preference['back_urls'] = $this->get_back_urls();
        $this->preference['shipments'] = $this->shipments_receiver_address();
        $this->preference['payment_methods'] = $this->get_payment_methods($this->ex_payments, $this->installments);
        $this->preference['auto_return'] = $this->auto_return();

        $internal_metadata = parent::get_internal_metadata();
        $this->preference['metadata'] = array_merge($internal_metadata, $this->get_internal_metadata_basic());

        // Calculate marketplace fee if applicable
        $this->calculate_marketplace_fee($order);
    }

    /**
     * Calculate marketplace fee based on vendor and order details.
     *
     * @param object $order
     */
    private function calculate_marketplace_fee($order)
    {
        $vendedor = wcmps_get_cart_vendor();
        if ((int)$vendedor > 0) { // Products from the marketplace itself do not have a commission
            $subtotal = $order->get_subtotal();
            $shipping = $order->get_shipping_total();
            $comission_options = get_option('wcfm_commission_options');
            $cfor = ($comission_options['commission_for'] != 'admin') ? 1 : 0;
            $cshi = ($comission_options['get_shipping'] == 'no') ? 1 : 0;
            $mktfee = $comission_options['commission_percent'] / 100;

            // Calculate marketplace fee
            $fee = ($subtotal * $mktfee) * (int)$cfor + $shipping * (int)$cshi;
            $this->preference['marketplace_fee'] = $fee;
        }
    }

    /**
     * Get payer information.
     *
     * @return array
     */
    public function get_payer_basic()
    {
        return [
            'name' => html_entity_decode($this->order->get_billing_first_name() ?? ''),
            'surname' => html_entity_decode($this->order->get_billing_last_name() ?? ''),
            'email' => sanitize_email($this->order->get_billing_email()),
            'phone' => [
                'number' => $this->order->get_billing_phone() ?? '',
            ],
            'address' => [
                'zip_code' => $this->order->get_billing_postcode() ?? '',
                'street_name' => html_entity_decode($this->order->get_billing_address_1() . ' / ' .
                    $this->order->get_billing_city() . ' ' .
                    $this->order->get_billing_state() . ' ' .
                    $this->order->get_billing_country() ?? '')
            ]
        ];
    }

    /**
     * Get back URLs for payment redirection.
     *
 * @return array
     */
    public function get_back_urls()
    {
        $success_url = $this->payment->getOption('success_url', '');
        $failure_url = $this->payment->getOption('failure_url', '');
        $pending_url = $this->payment->getOption('pending_url', '');

        return [
            'success' => empty($success_url) ?
                WC_WooMercadoPagoSplit_Module::fix_url_ampersand(
                    esc_url($this->get_return_url($this->order))
                ) : esc_url($success_url),
            'failure' => empty($failure_url) ?
                WC_WooMercadoPagoSplit_Module::fix_url_ampersand(
                    esc_url($this->order->get_cancel_order_url())
                ) : esc_url($failure_url),
            'pending' => empty($pending_url) ?
                WC_WooMercadoPagoSplit_Module::fix_url_ampersand(
                    esc_url($this->get_return_url($this->order))
                ) : esc_url($pending_url)
        ];
    }

    /**
     * Get payment methods for the transaction.
     *
     * @param array $ex_payments
     * @param int $installments
     * @return array
     */
    public function get_payment_methods($ex_payments, $installments)
    {
        $excluded_payment_methods = [];
        if (is_array($ex_payments) && count($ex_payments) != 0) {
            foreach ($ex_payments as $excluded) {
                $excluded_payment_methods[] = ['id' => $excluded];
            }
        }

        return [
            'installments' => (int)$installments,
            'excluded_payment_methods' => $excluded_payment_methods
        ];
    }

    /**
     * Determine the auto return setting.
     *
     * @return string|null
     */
    public function auto_return()
    {
        return get_option('auto_return', 'yes') === 'yes' ? 'approved' : null;
    }

    /**
     * Get internal metadata for the transaction.
     *
     * @return array
     */
    public function get_internal_metadata_basic()
    {
        return [
            "checkout" => "smart",
            "checkout_type" => $this->payment->getOption('method', 'redirect'),
        ];
    }
}
