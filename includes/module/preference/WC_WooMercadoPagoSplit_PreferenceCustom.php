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

class WC_WooMercadoPagoSplit_PreferenceCustom extends WC_WooMercadoPagoSplit_PreferenceAbstract
{
    /**
     * WC_WooMercadoPagoSplit_PreferenceCustom constructor.
     *
     * @param mixed $payment
     * @param WC_Order $order
     * @param array $custom_checkout
     */
    public function __construct($payment, WC_Order $order, array $custom_checkout)
    {
        parent::__construct($payment, $order, $custom_checkout);
        
        $this->preference = $this->make_commum_preference();
        $this->preference['transaction_amount'] = $this->get_transaction_amount();
        $this->preference['token'] = $this->checkout['token'];
        $this->preference['description'] = implode(', ', $this->list_of_items);
        $this->preference['installments'] = (int)$this->checkout['installments'];
        $this->preference['payment_method_id'] = $this->checkout['paymentMethodId'];
        $this->preference['payer']['email'] = $this->get_email();

        if (!empty($this->checkout['token'])) {
            $this->preference['metadata']['token'] = $this->checkout['token'];
            if (!empty($this->checkout['CustomerId'])) {
                $this->preference['payer']['id'] = $this->checkout['CustomerId'];
            }
            if (!empty($this->checkout['issuer'])) {
                $this->preference['issuer_id'] = (int)$this->checkout['issuer'];
            }
        }

        $this->preference['additional_info']['items'] = $this->items;
        $this->preference['additional_info']['payer'] = $this->get_payer_custom();
        $this->preference['additional_info']['shipments'] = $this->shipments_receiver_address();

        if ($this->is_discount_valid()) {
            $this->preference['additional_info']['items'][] = $this->add_discounts();
            $this->preference = array_merge($this->preference, $this->add_discounts_campaign());
        }

        $internal_metadata = parent::get_internal_metadata();
        $merge_array = array_merge($internal_metadata, $this->get_internal_metadata_custom());
        $this->preference['metadata'] = $merge_array;
    }

    /**
     * Check if discount is valid.
     *
     * @return bool
     */
    private function is_discount_valid(): bool
    {
        return isset($this->checkout['discount'], $this->checkout['coupon_code']) &&
               !empty($this->checkout['discount']) &&
               !empty($this->checkout['coupon_code']) &&
               $this->checkout['discount'] > 0 &&
               WC()->session->chosen_payment_method === 'woo-mercado-pago-split-custom';
    }

    /**
     * Get shipping cost item.
     *
     * @return array
     */
    public function ship_cost_item(): array
    {
        $item = parent::ship_cost_item();
        unset($item['currency_id']);
        return $item;
    }

    /**
     * Get items build array.
     *
     * @return array
     */
    public function get_items_build_array(): array
    {
        $items = parent::get_items_build_array();
        foreach ($items as $key => $item) {
            unset($items[$key]['currency_id']);
        }
        return $items;
    }

    /**
     * Get internal metadata for custom checkout.
     *
     * @return array
     */
    public function get_internal_metadata_custom(): array
    {
        return [
            "checkout" => "custom",
            "checkout_type" => "credit_card",
        ];
    }
}
