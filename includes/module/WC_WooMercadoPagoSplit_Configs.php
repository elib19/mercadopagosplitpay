<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_WooMercadoPagoSplit_Configs
 */
class WC_WooMercadoPagoSplit_Configs
{
    const OPTION_MP_PUBLIC_KEY = '_mp_public_key';
    const OPTION_MP_ACCESS_TOKEN = '_mp_access_token';
    const OPTION_MP_PUBLIC_KEY_PROD = '_mp_public_key_prod';
    const OPTION_MP_ACCESS_TOKEN_PROD = '_mp_access_token_prod';
    const OPTION_MP_CLIENT_ID = '_mp_client_id';
    const OPTION_MP_CLIENT_SECRET = '_mp_client_secret';

    /**
     * WC_WooMercadoPagoSplit_Configs constructor.
     */
    public function __construct()
    {
        $this->updateTokenNewVersion();
        $this->showNotices();
    }

    /**
     * Show Notices in ADMIN
     */
    private function showNotices()
    {
        add_action('admin_notices', array($this, 'plugin_review'));

        // Check if credentials are missing
        if (empty(get_option(self::OPTION_MP_PUBLIC_KEY_PROD)) && empty(get_option(self::OPTION_MP_ACCESS_TOKEN_PROD))) {
            if (!empty(get_option(self::OPTION_MP_CLIENT_ID)) && !empty(get_option(self::OPTION_MP_CLIENT_SECRET))) {
                add_action('admin_notices', array($this, 'noticeUpdateAccessToken'));
            }
        }

        // Check if HTTPS is enabled
        if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
            add_action('admin_notices', array($this, 'noticeHttps'));
        }
    }

    /**
     * Update tokens to the new version if necessary
     */
    private function updateTokenNewVersion()
    {
        // Check for production tokens
        if (empty(get_option(self::OPTION_MP_PUBLIC_KEY_PROD)) || empty(get_option(self::OPTION_MP_ACCESS_TOKEN_PROD))) {
            if (!empty(get_option(self::OPTION_MP_PUBLIC_KEY)) && !empty(get_option(self::OPTION_MP_ACCESS_TOKEN))) {
                $this->updateToken();
            }
        }

        // Validate credentials for version 1
        if (empty(get_option('_site_id_v1')) || empty(get_option('_collector_id_v1'))) {
            WC_WooMercadoPagoSplit_Credentials::validate_credentials_v1();
        }

        // Update ticket methods if necessary
        $ticketMethods = get_option('_all_payment_methods_ticket', '');
        if (empty($ticketMethods) || !is_array($ticketMethods)) {
            $this->updateTicketMethods();
        }

        // Update payment methods if necessary
        $allPayments = get_option('_checkout_payments_methods', '');
        if (empty($allPayments)) {
            $this->updatePayments();
            return;
        }

        foreach ($allPayments as $payment) {
            if (!isset($payment['name'])) {
                $this->updatePayments();
                break;
            }
        }
    }

    /**
     * Update payment methods
     */
    private function updatePayments()
    {
        $mpInstance = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton();
        if ($mpInstance) {
            WC_WooMercadoPagoSplit_Credentials::updatePaymentMethods($mpInstance, $mpInstance->get_access_token());
        }
    }

    /**
     * Update ticket methods
     */
    private function updateTicketMethods()
    {
        $mpInstance = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton();
        if ($mpInstance) {
            WC_WooMercadoPagoSplit_Credentials::updateTicketMethod($mpInstance, $mpInstance->get_access_token());
        }
    }

    /**
     * Notice for missing Access Token
     */
    public function noticeUpdateAccessToken()
    {
        $message = __('Update your credentials with the Access Token and Public Key, you need them to continue receiving payments!', 'woocommerce-mercadopago-split');
        echo WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, 'error');
    }

    /**
     * Notice for missing HTTPS
     */
    public function noticeHttps()
    {
        $message = __('The store should have HTTPS in order to activate both Checkout Personalizado and Ticket Checkout.', 'woocommerce-mercadopago-split');
        echo WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, 'notice-warning');
    }

    public function plugin_review()
    {
        $pagesToShow = array('dashboard', 'plugins', 'woocommerce_page_wc-settings');
        $dismissedReview = (int) get_option('_mp_dismiss_review', 0);

        if (!in_array(get_current_screen()->id, $pagesToShow, true) || $dismissedReview != 0) {
            return false;
        }

        echo WC_WooMercadoPagoSplit_ReviewNotice::getPluginReviewBanner();
    }

    /**
     * Update tokens
     */
    private function updateToken()
    {
        $mpInstance = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton();

        if ($mpInstance) {
            if (
                WC_WooMercadoPagoSplit_Credentials::validateCredentialsTest($mpInstance, null, get_option(self::OPTION_MP_PUBLIC_KEY)) &&
                WC_WooMercadoPagoSplit_Credentials::validateCredentialsTest($mpInstance, get_option(self::OPTION_MP_ACCESS_TOKEN))
            ) {
                update_option('_mp_public_key_test', get_option(self::OPTION_MP_PUBLIC_KEY), true);
                update_option('_mp_access_token_test', get_option(self::OPTION_MP_ACCESS_TOKEN), true);
                update_option('checkout_credential_prod', 'no', true);
            }

            if (
                WC_WooMercadoPagoSplit_Credentials::validateCredentialsProd($mpInstance, null, get_option(self::OPTION_MP_PUBLIC_KEY)) &&
                WC_WooMercadoPagoSplit_Credentials::validateCredentialsProd($mpInstance, get_option(self::OPTION_MP_ACCESS_TOKEN))
            ) {
                update_option(self::OPTION_MP_PUBLIC_KEY_PROD, get_option(self::OPTION_MP_PUBLIC_KEY), true);
                update_option(self::OPTION_MP_ACCESS_TOKEN_PROD, get_option(self::OPTION_MP_ACCESS_TOKEN), true);
                if (!empty(get_option(self::OPTION_MP_PUBLIC_KEY_PROD)) && !empty(get_option(self::OPTION_MP_ACCESS_TOKEN_PROD))) {
                    update_option(self::OPTION_MP_PUBLIC_KEY, '');
                    update_option(self::OPTION_MP_ACCESS_TOKEN, '');
                }
                update_option('checkout_credential_prod', 'yes', true);
            }

            update_option('_mp_appid', get_option(self::OPTION_MP_PUBLIC_KEY), true);
            update_option('_mp_returnurl', get_option(self::OPTION_MP_PUBLIC_KEY), true);
        }
    }

    /**
     * Get country configurations
     */
    public static function getCountryConfigs()
    {
        return array(
            'MCO' => array(
                'site_id' => 'MCO',
                'sponsor_id' => 208687643,
                'checkout_banner' => plugins_url('../../assets/images/MCO/standard_mco.jpg', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MCO/credit_card.png', __FILE__),
                'currency' => 'COP',
                'zip_code' => '110111',
            ),
            'MLA' => array(
                'site_id' => 'MLA',
                'sponsor_id' => 208682286,
                'checkout_banner' => plugins_url('../../assets/images/MLA/standard_mla.jpg', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLA/credit_card.png', __FILE__),
                'currency' => 'ARS',
                'zip_code' => '3039',
            ),
            'MLB' => array(
                'site_id' => 'MLB',
                'sponsor_id' => 208686191,
                'checkout_banner' => plugins_url('../../assets/images/MLB/standard_mlb.jpg', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLB/credit_card.png', __FILE__),
                'currency' => 'BRL',
                'zip_code' => '01310924',
            ),
            'MLC' => array(
                'site_id' => 'MLC',
                'sponsor_id' => 208690789,
                'checkout_banner' => plugins_url('../../assets/images/MLC/standard_mlc.gif', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLC/credit_card.png', __FILE__),
                'currency' => 'CLP',
                'zip_code' => '7591538',
            ),
            'MLM' => array(
                'site_id' => 'MLM',
                'sponsor_id' => 208692380,
                'checkout_banner' => plugins_url('../../assets/images/MLM/standard_mlm.jpg ', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLM/credit_card.png', __FILE__),
                'currency' => 'MXN',
                'zip_code' => '11250',
            ),
            'MLU' => array(
                'site_id' => 'MLU',
                'sponsor_id' => 243692679,
                'checkout_banner' => plugins_url('../../assets/images/MLU/standard_mlu.png', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLU/credit_card.png', __FILE__),
                'currency' => 'UYU',
                'zip_code' => '11800',
            ),
            'MLV' => array(
                'site_id' => 'MLV',
                'sponsor_id' => 208692735,
                'checkout_banner' => plugins_url('../../assets/images/MLV/standard_mlv.jpg', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MLV/credit_card.png', __FILE__),
                'currency' => 'VEF',
                'zip_code' => '1160',
            ),
            'MPE' => array(
                'site_id' => 'MPE',
                'sponsor_id' => 216998692,
                'checkout_banner' => plugins_url('../../assets/images/MPE/standard_mpe.png', __FILE__),
                'checkout_banner_custom' => plugins_url('../../assets/images/MPE/credit_card.png', __FILE__),
                'currency' => 'PEN',
                'zip_code' => '15074',
            )
        );
    }

    /**
     * Get store categories
     * @return array
     */
    public function getCategories()
    {
        return array(
            'store_categories_id' => [
                "art", "baby", "coupons", "donations", "computing", "cameras", "video games", "television",
                "car electronics", "electronics", "automotive", "entertainment", "fashion", "games", "home",
                "musical", "phones", "services", "learnings", "tickets", "travels", "virtual goods", "others"
            ],
            'store_categories_description' => [
                "Collectibles & Art", "Toys for Baby, Stroller, Stroller Accessories, Car Safety Seats", "Coupons",
                "Donations", "Computers & Tablets", "Cameras & Photography", "Video Games & Consoles",
                "LCD, LED, Smart TV, Plasmas, TVs", "Car Audio, Car Alarm Systems & Security, Car DVRs, Car Video Players, Car PC",
                "Audio & Surveillance, Video & GPS, Others", "Parts & Accessories", "Music, Movies & Series, Books, Magazines & Comics, Board Games & Toys",
                "Men's, Women's, Kids & baby, Handbags & Accessories, Health & Beauty, Shoes, Jewelry & Watches",
                "Online Games & Credits", "Home appliances. Home & Garden", "Instruments & Gear",
                "Cell Phones & Accessories", "General services", "Trainings, Conferences, Workshops",
                "Tickets for Concerts, Sports, Arts, Theater, Family, Excursions tickets, Events & more",
                "Plane tickets, Hotel vouchers, Travel vouchers",
                "E-books, Music Files, Software, Digital Images, PDF Files and any item which can be electronically stored in a file, Mobile Recharge, DTH Recharge and any Online Recharge",
                "Other categories"
            ]
        );
    }

    /**
     * Set payment gateway methods
     * @param $methods
     * @return array
     */
    public function setPaymentGateway($methods = null)
    {
        global $wp;
        if (!empty($wp) && isset($wp->query_vars['wc-api'])) {
            $api_request = wc_clean($wp->query_vars['wc-api']);
            if (!empty($api_request) && in_array($api_request, ['WC_WooMercadoPagoSplit_BasicGateway', 'WC_WooMercadoPagoSplit_CustomGateway', 'WC_WooMercadoPagoSplit_TicketGateway'])) {
                $methods[] = $api_request;
            }
            return $methods;
        }

        $methods[] = 'WC_WooMercadoPagoSplit_BasicGateway';
        $methods[] = 'WC_WooMercadoPagoSplit_CustomGateway';
        $methods[] = 'WC_WooMercadoPagoSplit_TicketGateway';
        return $methods;
    }
}
