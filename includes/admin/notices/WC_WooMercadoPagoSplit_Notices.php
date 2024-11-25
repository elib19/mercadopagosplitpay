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
 * Class WC_WooMercadoPagoSplit_Notices
 */
class WC_WooMercadoPagoSplit_Notices
{
    private static $instance = null;

    private function __construct()
    {
        add_action('admin_enqueue_scripts', [$this, 'loadAdminNoticeCss']);
    }

    /**
     * Initialize the singleton instance
     *
     * @return WC_WooMercadoPagoSplit_Notices|null
     */
    public static function initMercadopagoNotice()
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
     * Generate alert frame HTML
     *
     * @param string $message
     * @param string $type
     * @return string
     */
    public static function getAlertFrame($message, $type)
    {
        $inline = null;
        if (class_exists('WC_WooMercadoPagoSplit_Module') && WC_WooMercadoPagoSplit_Module::isWcNewVersion() && isset($_GET['page']) && $_GET['page'] === "wc-settings") {
            $inline = "inline";
        }

        $notice = sprintf(
            '<div id="message" class="notice %s is-dismissible %s">
                <div class="mp-alert-frame">
                    <div class="mp-left-alert">
                        <img src="%s">
                    </div>
                    <div class="mp-right-alert">
                        <p>%s</p>
                    </div>
                </div>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">%s</span>
                </button>
            </div>',
            esc_attr($type),
            esc_attr($inline),
            esc_url(plugins_url('../../assets/images/minilogo.png', __FILE__)),
            esc_html($message),
            esc_html__('Discard', 'woocommerce-mercadopago-split')
        );

        if (class_exists('WC_WooMercadoPagoSplit_Module')) {
            WC_WooMercadoPagoSplit_Module::$notices[] = $notice;
        }

        return $notice;
    }

    /**
     * Generate alert for missing WooCommerce
     *
     * @param string $message
     * @param string $type
     * @return string
     */
    public static function getAlertWocommerceMiss($message, $type)
    {
        $is_installed = false;

        if (function_exists('get_plugins')) {
            $all_plugins = get_plugins();
            $is_installed = !empty($all_plugins['woocommerce/woocommerce.php']);
        }

        if ($is_installed && current_user_can('install_plugins')) {
            $buttonUrl = sprintf(
                '<a href="%s" class="button button-primary">%s</a>',
                esc_url(wp_nonce_url(self_admin_url('plugins.php?action=activate&plugin=woocommerce/woocommerce.php&plugin_status=active'), 'activate-plugin_woocommerce/woocommerce.php')),
                esc_html__('Activate WooCommerce', 'woocommerce-mercadopago-split')
            );
        } else {
            if (current_user_can('install_plugins')) {
                $buttonUrl = sprintf(
                    '<a href="%s" class="button button-primary">%s</a>',
                    esc_url(wp_nonce_url(self_admin_url('update.php?action=install-plugin&plugin=woocommerce'), 'install-plugin_woocommerce')),
                    esc_html__('Install WooCommerce', 'woocommerce-mercadopago-split')
                );
            } else {
                $buttonUrl = sprintf(
                    '<a href="http://wordpress.org/plugins/woocommerce/" class="button button-primary">%s</a>',
                    esc_html__('See WooCommerce', 'woocommerce-mercadopago-split')
                );
            }
        }

        $inline = null;
        if (class_exists('WC_WooMercadoPagoSplit_Module') && WC_WooMercadoPagoSplit_Module::isWcNewVersion() && isset($_GET['page']) && $_GET['page'] === "wc-settings") {
            $inline = "inline";
        }

        $notice = sprintf(
            '<div id="message" class="notice %s is-dismissible %s">
                <div class="mp-alert-frame">
                    <div class="mp-left-alert">
                        <img src="%s">
                    </div>
                    <div class="mp-right-alert">
                        <p>%s</p>
                        <p>%s</p>
                    </div>
                </div>
                <button type="button" class="notice-dismiss">
                    <span class="screen-reader-text">%s</span>
                </button>
            </div>',
            esc_attr($type),
            esc_attr($inline),
            esc_url(plugins_url('../../assets/images/minilogo.png', __FILE__)),
            esc_html($message),
            $buttonUrl,
            esc_html__('Discard', 'woocommerce-mercadopago-split')
        );

        if (class_exists('WC_WooMercadoPagoSplit_Module')) {
            WC_WooMercadoPagoSplit_Module::$notices[] = $notice;
        }

        return $notice;
    }
}
