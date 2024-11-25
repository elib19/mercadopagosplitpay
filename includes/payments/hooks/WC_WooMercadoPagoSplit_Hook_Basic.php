<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Hook_Basic
 */
class WC_WooMercadoPagoSplit_Hook_Basic extends WC_WooMercadoPagoSplit_Hook_Abstract
{
    /**
     * WC_WooMercadoPagoSplit_Hook_Basic constructor.
     * @param $payment
     */
    public function __construct($payment)
    {
        parent::__construct($payment);
    }

    /**
     * Carrega os hooks necessários
     * 
     * @param bool $is_instance
     */
    public function loadHooks(bool $is_instance = false): void
    {
        parent::loadHooks();

        if (!empty($this->payment->settings['enabled']) && $this->payment->settings['enabled'] === 'yes') {
            add_action('woocommerce_after_checkout_form', [$this, 'add_mp_settings_script_basic']);
            add_action('woocommerce_thankyou', [$this, 'update_mp_settings_script_basic']);
        }

        add_action('woocommerce_receipt_' . $this->payment->id, function ($order) {
            echo $this->render_order_form($order);
        });

        add_action('wp_head', function () {
            $page_id = defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=') ? wc_get_page_id('checkout') : woocommerce_get_page_id('checkout');
            if (is_page($page_id)) {
                echo '<style type="text/css">#MP-Checkout-dialog { z-index: 9999 !important; }</style>' . PHP_EOL;
            }
        });
    }

    /**
     * Renderiza o formulário de pedido
     * 
     * @param int $order_id
     * @return string
     */
    public function render_order_form(int $order_id): string
    {
        $order = wc_get_order($order_id);
        $url = $this->payment->create_preference($order);

        if ('modal' === $this->payment->method && $url) {
            $this->payment->log->write_log(__FUNCTION__, 'rendering Mercado Pago lightbox (modal window).');
            $html = '<style type="text/css">
            #MP-Checkout-dialog #MP-Checkout-IFrame { bottom: 0px !important; top:50%!important; margin-top: -280px !important; height: 590px !important; }
            </style>';
            $html .= '<script type="text/javascript" src="https://secure.mlstatic.com/mptools/render.js"></script>
                    <script type="text/javascript">
                        (function() { $MPC.openCheckout({ url: "' . esc_url($url) . '", mode: "modal" }); })();
                    </script>';
            $html .= '<a id="submit-payment" href="' . esc_url($url) . '" name="MP-Checkout" class="button alt" mp-mode="modal">' .
                esc_html(__('Pay with Mercado Pago', 'woocommerce-mercadopago-split')) .
                '</a> <a class="button cancel" href="' . esc_url($order->get_cancel_order_url()) . '">' .
                esc_html(__('Cancel &amp; Clear Cart', 'woocommerce-mercadopago-split')) .
                '</a>';
            return $html;
        } else {
            $this->payment->log->write_log(__FUNCTION__, 'unable to build Mercado Pago checkout URL.');
            return '<p>' .
                esc_html(__('There was an error processing your payment. Please try again or contact us for Assistance.', 'woocommerce-mercadopago-split')) .
                '</p>' .
                '<a class="button" href="' . esc_url($order->get_checkout_payment_url()) . '">' .
                esc_html(__('Click to try again', 'woocommerce-mercadopago-split')) .
                '</a>';
        }
    }

    /**
     * Processa as opções administrativas personalizadas
     * 
     * @return bool
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public function custom_process_admin_options(): bool
    {
        return parent::custom_process_admin_options();
    }

    /**
     * Scripts para configuração básica
     */
    public function add_mp_settings_script_basic(): void
    {
        parent::add_mp_settings_script();
    }

    /**
     * Atualiza o script de configuração
     * 
     * @param int $order_id
     */
    public function update_mp_settings_script_basic(int $order_id): void
    {
        parent::update_mp_settings_script($order_id);
    }

    /**
     * Não aplica desconto
     */
    public function add_discount(): void
    {
        return;
    }
}
