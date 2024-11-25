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

/**
 * Class WC_WooMercadoPagoSplit_ReviewNotice
 */
class WC_WooMercadoPagoSplit_ReviewNotice
{
    private static $instance = null;

    private function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'loadAdminNoticeCss']);
        add_action('admin_enqueue_scripts', [$this, 'loadAdminNoticeJs']);
        add_action('wp_ajax_mercadopago_review_dismiss', [$this, 'reviewDismiss']);
    }

    /**
     * Singleton instance
     *
     * @return WC_WooMercadoPagoSplit_ReviewNotice|null
     */
    public static function initMercadopagoReviewNotice()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get suffix for static files
     *
     * @return string
     */
    public function getSufix()
    {
        return defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
    }

    /**
     * Load admin notices CSS
     */
    public function loadAdminNoticeCss()
    {
        if (is_admin()) {
            $suffix = $this->getSufix();
            wp_enqueue_style(
                'woocommerce-mercadopago-split-admin-notice',
                plugins_url('../../assets/css/admin_notice_mercadopago' . $suffix . '.css', __FILE__)
            );
        }
    }

    /**
     * Load admin notices JS
     */
    public function loadAdminNoticeJs()
    {
        if (is_admin()) {
            $suffix = $this->getSufix();
            wp_enqueue_script(
                'woocommerce-mercadopago-split-admin-notice-review',
                plugins_url('../../assets/js/review' . $suffix . '.js', __FILE__),
                [],
                WC_WooMercadoPagoSplit_Constants::VERSION,
                true // Enqueue in footer
            );
        }
    }

    /**
     * Get the plugin review banner HTML
     *
     * @return string
     */
    public static function getPluginReviewBanner()
    {
        if (!class_exists('WC_WooMercadoPagoSplit_Module') || !WC_WooMercadoPagoSplit_Module::isWcNewVersion() || !isset($_GET['page']) || $_GET['page'] !== "wc-settings") {
            return '';
        }

        $user_login = esc_html(wp_get_current_user()->user_login);
        $notice = sprintf(
            '<div id="message" class="notice is-dismissible mp-rating-notice inline">
                <div class="mp-rating-frame">
                    <div class="mp-left-rating">
                        <div>
                            <img src="%s">
                        </div>
                        <div class="mp-left-rating-text">
                            <p class="mp-rating-title">%s</p>
                            <p class="mp-rating-subtitle">%s</p>
                        </div>
                    </div>
                    <div class="mp-right-rating">
                        <a class="mp-rating-link" href="https://wordpress.org/support/plugin/woocommerce-mercadopago-split/reviews/?filter=5#new-post" target="_blank">%s</a>
                    </div>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">%s</span>
                    </button>
                </div>
            </div>',
            esc_url(plugins_url('../../assets/images/minilogo.png', __FILE__)),
            sprintf(__('Hello %s, do you have a minute to share your experience with our plugin?', 'woocommerce-mercadopago-split'), $user_login),
            __('Your opinion is very important so that we can offer you the best possible payment solution and continue to improve.', 'woocommerce-mercadopago-split'),
 __('Rate the plugin', 'woocommerce-mercadopago-split'),
            __('Discard', 'woocommerce-mercadopago-split')
        );

        if (class_exists('WC_WooMercadoPagoSplit_Module')) {
            WC_WooMercadoPagoSplit_Module::$notices[] = $notice;
        }

        return $notice;
    }

    /**
     * Dismiss the review admin notice
     */
    public function reviewDismiss()
    {
        check_ajax_referer('mercadopago_review_dismiss', 'security'); // Security check

        $dismissedReview = (int) get_option('_mp_dismiss_review', 0);
        if ($dismissedReview === 0) {
            update_option('_mp_dismiss_review', 1, true);
        }

        wp_send_json_success();
    }
}
