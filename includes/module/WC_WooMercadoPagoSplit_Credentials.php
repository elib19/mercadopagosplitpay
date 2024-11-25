<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Credentials
 */
class WC_WooMercadoPagoSplit_Credentials
{
    const TYPE_ACCESS_CLIENT = 'client';
    const TYPE_ACCESS_TOKEN = 'token';

    public $payment;
    public $publicKey;
    public $accessToken;
    public $clientId;
    public $clientSecret;
    public $sandbox;
    public $log;

    /**
     * WC_WooMercadoPagoSplit_Credentials constructor.
     * @param null $payment
     */
    public function __construct($payment = null)
    {
        $this->payment = $payment;
        $this->initializeCredentials();
    }

    /**
     * Initialize credentials based on the payment settings.
     */
    private function initializeCredentials(): void
    {
        $this->publicKey = get_option('_mp_public_key_prod', '');
        $this->accessToken = get_option('_mp_access_token_prod', '');

        if ($this->payment) {
            $this->sandbox = $this->payment->isTestUser ();
            if ($this->payment->getOption('checkout_credential_prod', '') === 'no') {
                $this->publicKey = get_option('_mp_public_key_test', '');
                $this->accessToken = get_option('_mp_access_token_test', '');
            }
        }

        if (is_null($this->payment) && empty($this->publicKey) && empty($this->accessToken)) {
            $this->publicKey = get_option('_mp_public_key_test', '');
            $this->accessToken = get_option('_mp_access_token_test', '');
        }

        $vendorId = wcmps_get_cart_vendor();
        if ((int)$vendorId > 0) {
            $credentials = json_decode(get_user_meta($vendorId, 'wcmps_credentials', true));
            $this->publicKey = $credentials->public_key ?? '';
            $this->accessToken = $credentials->access_token ?? '';
        }

        $this->clientId = get_option('_mp_client_id');
        $this->clientSecret = get_option('_mp_client_secret');
    }

    /**
     * Validate the type of credentials being used.
     * 
     * @return string
     */
    public function validateCredentialsType(): string
    {
        $basicIsEnabled = self::basicIsEnabled();
        if (!$this->tokenIsValid() && ($this->payment instanceof WC_WooMercadoPagoSplit_BasicGateway || $basicIsEnabled === 'yes')) {
            return $this->clientIsValid() ? self::TYPE_ACCESS_CLIENT : self::TYPE_ACCESS_TOKEN;
        }

        return self::TYPE_ACCESS_TOKEN;
    }

    /**
     * Check if client credentials are valid.
     * 
     * @return bool
     */
    public function clientIsValid(): bool
    {
        return !empty($this->clientId) && !empty($this->clientSecret);
    }

    /**
     * Check if token credentials are valid.
     * 
     * @return bool
     */
    public function tokenIsValid(): bool
    {
        return !empty($this->publicKey) && !empty($this->accessToken);
    }

    /**
     * Set no credentials.
     */
    public static function setNoCredentials(): void
    {
        update_option('_test_user_v1', '', true);
        update_option('_site_id_v1', '', true);
        update_option('_collector_id_v1', '', true);
        update_option('_all_payment_methods_v0', [], true);
        update_option('_all_payment_methods_ticket', '[]', true);
        update_option('_can_do_currency_conversion_v1', false, true);
    }

    /**
     * Validate access token.
     * 
     * @param string $access_token
     * @return bool
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function access_token_is_valid(string $access_token): bool
    {
        $ mpInstance = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton();
        if (empty($mpInstance)) {
            return false;
        }
        $get_request = $mpInstance->get('/users/me', ['Authorization' => 'Bearer ' . $access_token], false);
        if ($get_request['status'] > 202) {
            $log = WC_WooMercadoPagoSplit_Log::init_mercado_pago_log('WC_WooMercadoPagoSplit_Credentials');
            $log->write_log('API valid_access_token error:', $get_request['response']['message']);
            return false;
        }

        if (isset($get_request['response']['site_id'])) {
            update_option('_site_id_v1', $get_request['response']['site_id'], true);
            update_option('_test_user_v1', in_array('test_user', $get_request['response']['tags']), true);
        }

        if (isset($get_request['response']['id'])) {
            update_option('_collector_id_v1', $get_request['response']['id'], true);
        }

        return true;
    }

    /**
     * Validate credentials for version 1.
     * 
     * @return bool
     */
    public static function validate_credentials_v1(): bool
    {
        $credentials = new self();
        $basicIsEnabled = 'no';
        if (!$credentials->tokenIsValid()) {
            $basicIsEnabled = self::basicIsEnabled();
            if ($basicIsEnabled !== 'yes') {
                self::setNoCredentials();
                return false;
            }
        }

        try {
            $mpInstance = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton();
            if (!($mpInstance instanceof MP)) {
                self::setNoCredentials();
                return false;
            }
            $access_token = $mpInstance->get_access_token();
            $get_request = $mpInstance->get('/users/me', ['Authorization' => 'Bearer ' . $access_token]);

            if (isset($get_request['response']['site_id']) && (!empty($credentials->publicKey) || $basicIsEnabled === 'yes')) {
                update_option('_test_user_v1', in_array('test_user', $get_request['response']['tags']), true);
                update_option('_site_id_v1', $get_request['response']['site_id'], true);
                update_option('_collector_id_v1', $get_request['response']['id'], true);

                $payments_response = self::getPaymentResponse($mpInstance, $access_token);
                self::updatePaymentMethods($mpInstance, $access_token, $payments_response);
                self::updateTicketMethod($mpInstance, $access_token, $payments_response);

                $currency_ratio = WC_WooMercadoPagoSplit_Module::get_conversion_rate(
                    WC_WooMercadoPagoSplit_Module::$country_configs[$get_request['response']['site_id']]['currency']
                );

                update_option('_can_do_currency_conversion_v1', $currency_ratio > 0, true);
                return true;
            }
        } catch (WC_WooMercadoPagoSplit_Exception $e) {
            $log = WC_WooMercadoPagoSplit_Log::init_mercado_pago_log('WC_WooMercadoPagoSplit_Credentials');
            $log->write_log('validate_credentials_v1', 'Exception ERROR');
        }

        self::setNoCredentials();
        return false;
    }

    /**
     * Get payment response.
     * 
     * @param $mpInstance
     * @param $accessToken
     * @return array|null
     */
    public static function getPaymentResponse($mpInstance, $accessToken): ?array
    {
        $seller = get_option('_collector_id_v1', '');
        $payments = $mpInstance->get('/users/' . $seller . '/accepted_payment_methods?marketplace=NONE', ['Authorization' => 'Bearer ' . $accessToken]);
        return $payments['response'] ?? null;
    }

    /**
     * Update payment methods.
     * 
     * @param $mpInstance
     * @param null $accessToken
     * @param null $paymentsResponse
     */
    public static function updatePaymentMethods($mpInstance, $accessToken = null, $paymentsResponse = null): void
    {
        if (empty($accessToken) || empty($mpInstance)) {
            return;
        }

        if (empty($paymentsResponse)) {
            $paymentsResponse = self::getPaymentResponse($mpInstance, $accessToken);
        }

        if (empty($paymentsResponse) || (isset($paymentsResponse['status']) && !in_array($paymentsResponse['status'], [200, 201]))) {
            return;
        }

        $arr = [];
        $cho = [];
        $excluded = ['consumer_ credits', 'paypal'];

        foreach ($paymentsResponse as $payment) {
            if (in_array($payment['id'], $excluded)) {
                continue;
            }

            $arr[] = $payment['id'];

            $cho[] = [
                "id" => $payment['id'],
                "name" => $payment['name'],
                "type" => $payment['payment_type_id'],
                "image" => $payment['secure_thumbnail'],
                "config" => "ex_payments_" . $payment['id'],
            ];
        }

        update_option('_all_payment_methods_v0', implode(',', $arr), true);
        update_option('_checkout_payments_methods', $cho, true);
    }

    /**
     * Update ticket method.
     * 
     * @param $mpInstance
     * @param $accessToken
     * @param null $paymentsResponse
     */
    public static function updateTicketMethod($mpInstance, $accessToken, $paymentsResponse = null): void
    {
        if (empty($accessToken) || empty($mpInstance)) {
            return;
        }

        if (empty($paymentsResponse)) {
            $paymentsResponse = self::getPaymentResponse($mpInstance, $accessToken);
        }

        if (empty($paymentsResponse) || (isset($paymentsResponse['status']) && !in_array($paymentsResponse['status'], [200, 201]))) {
            return;
        }

        $payment_methods_ticket = [];
        $excluded = ['consumer_credits', 'paypal', 'pse'];

        foreach ($paymentsResponse as $payment) {
            if (
                !in_array($payment['id'], $excluded) &&
                !in_array($payment['payment_type_id'], ['account_money', 'credit_card', 'debit_card', 'prepaid_card'])
            ) {
                $payment_methods_ticket[] = [
                    "id" => $payment['id'],
                    "name" => $payment['name'],
                    "secure_thumbnail" => $payment['secure_thumbnail'],
                ];
            }
        }

        update_option('_all_payment_methods_ticket', $payment_methods_ticket, true);
    }

    /**
     * Check if basic settings are enabled.
     * 
     * @return string
     */
    public static function basicIsEnabled(): string
    {
        $basicSettings = get_option('woocommerce_woo-mercado-pago-split-basic_settings', []);
        return $basicSettings['enabled'] ?? 'no';
    }

    /**
     * Validate test credentials.
     * 
     * @param $mpInstance
     * @param null $access_token
     * @param null $public_key
     * @return bool
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function validateCredentialsTest($mpInstance, $access_token = null, $public_key = null): bool
    {
        $isTest = $mpInstance->getCredentialsWrapper($access_token, $public_key);
        return is_array($isTest) && isset($isTest['is_test']) && $isTest['is_test'] === true;
    }

    /**
     * Validate production credentials.
     * 
     * @param $mpInstance
     * @param null $access_token
     * @param null $public_key
     * @return bool
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function validateCredentialsProd($mpInstance, $access_token = null, $public_key = null): bool
    {
        $isTest = $mpInstance->getCredentialsWrapper($access_token, $public_key);
        return is_array($isTest) && isset($isTest['is_test']) && $isTest['is_test'] === false;
    }
}
