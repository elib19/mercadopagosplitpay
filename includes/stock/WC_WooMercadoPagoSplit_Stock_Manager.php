<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_WooMercadoPagoSplit_Stock_Manager
 */
class WC_WooMercadoPagoSplit_Stock_Manager
{
    /**
     * WC_WooMercadoPagoSplit_Stock_Manager constructor.
     */
    public function __construct()
    {
        // MP status pending logic
        add_action('woocommerce_order_status_pending_to_cancelled', [$this, 'restore_stock_item'], 10, 1);
        add_action('woocommerce_order_status_pending_to_failed', [$this, 'restore_stock_item'], 10, 1);

        // MP status approved logic
        add_action('woocommerce_order_status_processing_to_refunded', [$this, 'restore_stock_item'], 10, 1);
        add_action('woocommerce_order_status_on-hold_to_refunded', [$this, 'restore_stock_item'], 10, 1);
    }

    /**
     * Restore stock for the items in the order.
     *
     * @param int $order_id
     */
    public function restore_stock_item($order_id)
    {
        $order = wc_get_order($order_id);

        // Check if order exists and stock management is enabled
        if (!$order || 'yes' !== get_option('woocommerce_manage_stock') || !apply_filters('woocommerce_can_reduce_order_stock', true, $order)) {
            return;
        }

        // Check if the payment method is 'woo-mercado-pago-split-ticket'
        if ($order->get_payment_method() !== 'woo-mercado-pago-split-ticket') {
            return;
        }

        // Get Mercado Pago ticket settings
        $mp_ticket_settings = get_option('woocommerce_woo-mercado-pago-split-ticket_settings', []);
        if (empty($mp_ticket_settings) || !isset($mp_ticket_settings['stock_reduce_mode']) || $mp_ticket_settings['stock_reduce_mode'] === 'no') {
            return;
        }

        // Restore stock for each item in the order
        foreach ($order->get_items() as $item) {
            $product_id = $item->get_product_id();
            if ($product_id > 0) {
                $_product = wc_get_product($product_id);
                if ($_product && $_product->exists() && $_product->managing_stock()) {
                    $qty = apply_filters('woocommerce_order_item_quantity', $item->get_quantity(), $order, $item);
                    wc_update_product_stock($_product, $qty, 'increase');
                    do_action('woocommerce_auto_stock_restored', $_product, $item);
                }
            }
        }
    }
}

// Instantiate the stock manager
new WC_WooMercadoPagoSplit_Stock_Manager();
