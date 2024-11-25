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

class WC_WooMercadoPagoSplit_PreferenceTicket extends WC_WooMercadoPagoSplit_PreferenceAbstract
{
    /**
     * WC_WooMercadoPagoSplit_PreferenceTicket constructor.
     * @param $payment
     * @param $order
     * @param $ticket_checkout
     */
    public function __construct($payment, $order, $ticket_checkout)
    {
        parent::__construct($payment, $order, $ticket_checkout);
        
        $this->initializePreference($payment, $ticket_checkout);
    }

    /**
     * Initializes the preference array.
     *
     * @param $payment
     * @param $ticket_checkout
     */
    private function initializePreference($payment, $ticket_checkout)
    {
        $this->preference = $this->make_commum_preference();
        $this->preference['date_of_expiration'] = $this->get_date_of_expiration($payment);
        $this->preference['transaction_amount'] = $this->get_transaction_amount();
        $this->preference['description'] = implode(', ', $this->list_of_items);
        $this->preference['payment_method_id'] = $this->checkout['paymentMethodId'];
        $this->preference['payer']['email'] = $this->get_email();

        $this->setPayerDetails($ticket_checkout);
        $this->setAdditionalDetails($ticket_checkout);

        $this->preference['external_reference'] = $this->get_external_reference();
        $this->preference['additional_info']['items'] = $this->items;
        $this->preference['additional_info']['payer'] = $this->get_payer_custom();
        $this->preference['additional_info']['shipments'] = $this->shipments_receiver_address();

        $this->applyDiscountsIfApplicable();

        $internal_metadata = parent::get_internal_metadata();
        $this->preference['metadata'] = array_merge($internal_metadata, $this->get_internal_metadata_ticket());
    }

    /**
     * Sets payer details based on the site currency.
     *
     * @param $ticket_checkout
     */
    private function setPayerDetails($ticket_checkout)
    {
        if ($this->site_data[$this->site_id]['currency'] == 'BRL') {
            $this->preference['payer']['first_name'] = $this->checkout['firstname'];
            $this->preference['payer']['last_name'] = strlen($this->checkout['docNumber']) == 14 ? $this->checkout['lastname'] : $this->checkout['firstname'];
            $this->preference['payer']['identification']['type'] = strlen($this->checkout['docNumber']) == 14 ? 'CPF' : 'CNPJ';
            $this->preference['payer']['identification']['number'] = $this->checkout['docNumber'];
            $this->preference['payer']['address'] = [
                'street_name' => $this->checkout['address'],
                'street_number' => $this->checkout['number'],
                'neighborhood' => $this->checkout['city'],
                'city' => $this->checkout['city'],
                'federal_unit' => $this->checkout['state'],
                'zip_code' => $this->checkout['zipcode']
            ];
        } elseif ($this->site_data[$this->site_id]['currency'] == 'UYU') {
            $this->preference['payer']['identification']['type'] = $ticket_checkout['docType'];
            $this->preference['payer']['identification']['number'] = $ticket_checkout['docNumber'];
        }
    }

    /**
     * Sets additional details for the preference.
     *
     * @param $ticket_checkout
     */
    private function setAdditionalDetails($ticket_checkout)
    {
        if ($ticket_checkout['paymentMethodId'] == 'webpay') {
            $this->preference['callback_url'] = get_site_url();
            $this->preference['transaction_details']['financial_institution'] = "1234";
            $this->preference['additional_info']['ip_address'] = "127.0.0.1";
            $this->preference['payer']['identification'] ['type'] = "RUT";
            $this->preference['payer']['identification']['number'] = "0";
            $this->preference['payer']['entity_type'] = "individual";
        }
    }

    /**
     * Applies discounts to the preference if applicable.
     */
    private function applyDiscountsIfApplicable()
    {
        if (
            isset($this->checkout['discount']) && !empty($this->checkout['discount']) &&
            isset($this->checkout['coupon_code']) && !empty($this->checkout['coupon_code']) &&
            $this->checkout['discount'] > 0 && WC()->session->chosen_payment_method == 'woo-mercado-pago-split-ticket'
        ) {
            $this->preference['additional_info']['items'][] = $this->add_discounts();
            $this->preference = array_merge($this->preference, $this->add_discounts_campaign());
        }
    }

    /**
     * Get date of expiration.
     * @param WC_WooMercadoPagoSplit_TicketGateway $payment
     * @return string|null date
     */
    public function get_date_of_expiration(WC_WooMercadoPagoSplit_TicketGateway $payment = null)
    {
        $date_expiration = !is_null($payment)
            ? $payment->getOption('date_expiration')
            : $this->get_option('date_expiration', '');

        return $date_expiration ? date('Y-m-d\TH:i:s.000O', strtotime('+' . $date_expiration . ' days')) : null;
    }

    /**
     * @return array
     */
    public function get_items_build_array()
    {
        $items = parent::get_items_build_array();
        foreach ($items as $key => $item) {
            unset($items[$key]['currency_id']);
        }

        return $items;
    }

    /**
     * @return array
     */
    public function get_internal_metadata_ticket()
    {
        return [
            "checkout" => "custom",
            "checkout_type" => "ticket",
        ];
    }
}
