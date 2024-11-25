<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Hook_Ticket
 */
class WC_WooMercadoPagoSplit_Hook_Ticket extends WC_WooMercadoPagoSplit_Hook_Abstract
{
    /**
     * WC_WooMercadoPagoSplit_Hook_Ticket constructor.
     *
     * @param WC_WooMercadoPagoSplit $payment
     */
    public function __construct(WC_WooMercadoPagoSplit $payment)
    {
        parent::__construct($payment);
    }

    /**
     * Load Hooks
     */
    public function loadHooks(): void
    {
        parent::loadHooks();

        if (isset($this->payment->settings['enabled']) && $this->payment->settings['enabled'] === 'yes') {
            add_action('wp_enqueue_scripts', [$this, 'add_checkout_scripts_ticket']);
            add_action('woocommerce_after_checkout_form', [$this, 'add_mp_settings_script_ticket']);
            add_action('woocommerce_thankyou_' . $this->payment->id, [$this, 'update_mp_settings_script_ticket']);
        }
    }

    /**
     * Add Discount
     */
    public function add_discount(): void
    {
        if (!isset($_POST['mercadopago_ticket'])) {
            return;
        }

        if ((is_admin() && !defined('DOING_AJAX')) || is_cart()) {
            return;
        }

        $ticket_checkout = $_POST['mercadopago_ticket'];
        parent::add_discount_abst($ticket_checkout);
    }

    /**
     * @return bool
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public function custom_process_admin_options(): bool
    {
        return parent::custom_process_admin_options();
    }

    /**
     * Add Checkout Scripts
     */
    public function add_checkout_scripts_ticket(): void
    {
        if (is_checkout() && $this->payment->is_available() && !get_query_var('order-received')) {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script(
                'woocommerce-mercadopago-split-ticket-checkout',
                plugins_url('../../assets/js/ticket' . $suffix . '.js', plugin_dir_path(__FILE__)),
                ['jquery'],
                WC_WooMercadoPagoSplit_Constants::VERSION,
                true
            );

            wp_localize_script(
                'woocommerce-mercadopago-split-ticket-checkout',
                'wc_mercadopago_ticket_params',
                [
                    'site_id'               => $this->payment->getOption('_site_id_v1'),
                    'coupon_mode'           => $this->payment->logged_user_email ?? 'no',
                    'discount_action_url'   => $this->payment->discount_action_url,
                    'payer_email'           => esc_js($this->payment->logged_user_email),
                    'apply'                 => __('Apply', 'woocommerce-mercadopago-split'),
                    'remove'                => __('Remove', 'woocommerce-mercadopago-split'),
                    'coupon_empty'          => __('Please, inform your coupon code', 'woocommerce-mercadopago-split'),
                    'choose'                => __('To choose', 'woocommerce-mercadopago-split'),
                    'other_bank'            => __('Other bank', 'woocommerce-mercadopago-split'),
                    'discount_info1'        => __('You will save', 'woocommerce-mercadopago-split'),
                    'discount_info2'        => __('with discount of', 'woocommerce-mercadopago-split'),
                    'discount_info3'        => __('Total of your purchase:', 'woocommerce-mercadopago-split'),
                    'discount_info4'        => __('Total of your purchase with discount:', 'woocommerce-mercadopago-split'),
                    'discount_info5'        => __('*After payment approval', 'woocommerce-mercadopago-split'),
                    'discount_info6'        => __('Terms and conditions of use', 'woocommerce-mercadopago -split'),
                    'loading'               => plugins_url('../../assets/images/', plugin_dir_path(__FILE__)) . 'loading.gif',
                    'check'                 => plugins_url('../../assets/images/', plugin_dir_path(__FILE__)) . 'check.png',
                    'error'                 => plugins_url('../../assets/images/', plugin_dir_path(__FILE__)) . 'error.png'
                ]
            );
        }
    }

    /**
     * MP Settings Ticket
     */
    public function add_mp_settings_script_ticket(): void
    {
        parent::add_mp_settings_script();
    }

    /**
     * @param int $order_id
     */
    public function update_mp_settings_script_ticket(int $order_id): void
    {
        parent::update_mp_settings_script($order_id);
        $order = wc_get_order($order_id);
        $transaction_details = method_exists($order, 'get_meta') ? $order->get_meta('_transaction_details_ticket') : get_post_meta($order->get_id(), '_transaction_details_ticket', true);

        if (empty($transaction_details)) {
            return;
        }

        $html = '<p>' .
            __('Great, we processed your purchase order. Complete the payment with ticket so that we finish approving it.', 'woocommerce-mercadopago-split') .
            '</p>' .
            '<p><iframe src="' . esc_url($transaction_details) . '" style="width:100%; height:1000px;"></iframe></p>' .
            '<a id="submit-payment" target="_blank" href="' . esc_url($transaction_details) . '" class="button alt"' .
            ' style="font-size:1.25rem; width:75%; height:48px; line-height:24px; text-align:center;">' .
            __('Print ticket', 'woocommerce-mercadopago-split') .
            '</a>';
        echo '<p>' . $html . '</p>';
    }
}
