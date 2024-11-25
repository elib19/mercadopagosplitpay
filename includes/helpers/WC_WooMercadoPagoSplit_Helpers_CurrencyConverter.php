<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Helpers_CurrencyConverter
 */
class WC_WooMercadoPagoSplit_Helpers_CurrencyConverter
{
    const CONFIG_KEY = 'currency_conversion';
    const DEFAULT_RATIO = 1;

    /** @var WC_WooMercadoPagoSplit_Helpers_CurrencyConverter */
    private static $instance;

    /** @var string */
    private $msg_description;

    /** @var array */
    private $ratios = [];

    /** @var array */
    private $cache = [];

    /** @var array */
    private $currencyCache = [];

    /** @var array|null */
    private $supportedCurrencies;

    /** @var bool */
    private $isShowingAlert = false;

    /** @var WC_WooMercadoPagoSplit_Log */
    private $log;

    /**
     * Private constructor to make class singleton
     */
    private function __construct()
    {
        $this->msg_description = __('Activate this option so that the value of the currency set in WooCommerce is compatible with the value of the currency you use in Mercado Pago.', 'woocommerce-mercadopago-split');
        $this->log = new WC_WooMercadoPagoSplit_Log();
    }

    /**
     * @return static
     */
    public static function getInstance(): self
    {
        if (is_null(self::$instance)) {
            self::$instance = new static();
        }

        return self::$instance;
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return $this
     */
    private function init(WC_WooMercadoPagoSplit_PaymentAbstract $method): self
    {
        if (!isset($this->ratios[$method->id])) {
            try {
                if (!$this->isEnabled($method)) {
                    $this->setRatio($method->id);
                    return $this;
                }

                $accountCurrency = $this->getAccountCurrency($method);
                $localCurrency = get_woocommerce_currency();

                if (!$accountCurrency || $accountCurrency === $localCurrency) {
                    $this->setRatio($method->id);
                    return $this;
                }

                $this->setRatio($method->id, $this->loadRatio($localCurrency, $accountCurrency, $method));
            } catch (Exception $e) {
                $this->setRatio($method->id);
                $this->log->write_log(__FUNCTION__, 'Error initializing currency ratio: ' . $e->getMessage());
                throw $e;
            }
        }

        return $this;
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string|null
     */
    private function getAccountCurrency(WC_WooMercadoPagoSplit_PaymentAbstract $method): ?string
    {
        $key = $method->id;

        if (isset($this->currencyCache[$key])) {
            return $this->currencyCache[$key];
        }

        $siteId = $this->getSiteId($this->getAccessToken($method));

        if (!$siteId) {
            return null;
        }

        $configs = $this->getCountryConfigs();

        return $configs[$siteId]['currency'] ?? null;
    }

    /**
     * @return array
     */
    private function getCountryConfigs(): array
    {
        try {
            $configInstance = new WC_WooMercadoPagoSplit_Configs();
            return $configInstance->getCountryConfigs();
        } catch (Exception $e) {
            $this->log->write_log(__FUNCTION__, 'Error fetching country configs: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string
     */
    private function getAccessToken(WC_WooMercadoPagoSplit_PaymentAbstract $method): string
    {
        $type = $ method->getOption('checkout_credential_prod') === 'no'
            ? '_mp_access_token_test'
            : '_mp_access_token_prod';

        return $method->getOption($type);
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return bool
     */
    public function isEnabled(WC_WooMercadoPagoSplit_PaymentAbstract $method): bool
    {
        return $method->getOption(self::CONFIG_KEY, 'no') === 'yes';
    }

    /**
     * @param string $methodId
     * @param float $value
     */
    private function setRatio(string $methodId, float $value = self::DEFAULT_RATIO): void
    {
        $this->ratios[$methodId] = $value;
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return float
     */
    private function getRatio(WC_WooMercadoPagoSplit_PaymentAbstract $method): float
    {
        $this->init($method);
        return $this->ratios[$method->id] ?? self::DEFAULT_RATIO;
    }

    /**
     * @param string $fromCurrency
     * @param string $toCurrency
     * @param WC_WooMercadoPagoSplit_PaymentAbstract|null $method
     * @return float
     */
    public function loadRatio(string $fromCurrency, string $toCurrency, WC_WooMercadoPagoSplit_PaymentAbstract $method = null): float
    {
        $cacheKey = "{$fromCurrency}--{$toCurrency}";

        if (isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $ratio = self::DEFAULT_RATIO;

        if ($fromCurrency === $toCurrency) {
            $this->cache[$cacheKey] = $ratio;
            return $ratio;
        }

        try {
            $result = MeliRestClientSplit::get([
                'uri' => sprintf('/currency_conversions/search?from=%s&to=%s', $fromCurrency, $toCurrency),
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->getAccessToken($method)
                ]
            ]);

            if ($result['status'] !== 200) {
                $this->log->write_log(__FUNCTION__, 'Error fetching currency ratio: ' . print_r($result, true));
                throw new Exception('Status: ' . $result['status'] . ' Message: ' . $result['response']['message']);
            }

            $ratio = $result['response']['ratio'] > 0 ? $result['response']['ratio'] : self::DEFAULT_RATIO;
        } catch (Exception $e) {
            $this->log->write_log(__FUNCTION__, 'Error loading ratio: ' . $e->getMessage());
            throw $e;
        }

        $this->cache[$cacheKey] = $ratio;
        return $ratio;
    }

    /**
     * @param string $accessToken
     * @return string|null
     */
    private function getSiteId(string $accessToken): ?string
    {
        try {
            $mp = new MPP($accessToken);
            $result = $mp->get('/users/me', ['Authorization' => 'Bearer ' . $accessToken]);
            return $result['response']['site_id'] ?? null;
        } catch (Exception $e) {
            $this->log->write_log(__FUNCTION__, 'Error fetching site ID: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return float
     */
    public function ratio(WC_WooMercadoPagoSplit_PaymentAbstract $method): float
    {
        $this->init($method);
        return $this->getRatio($method);
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string
     */
    public function getDescription(WC_WooMercadoPagoSplit_PaymentAbstract $method): string
    {
        return $this->msg_description;
    }

    /**
     * Check if currency is supported in Mercado Pago API
     * @param string $currency
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return bool
     */
    private function isCurrencySupported(string $currency, WC_WooMercadoPagoSplit_PaymentAbstract $method): bool
    {
        foreach ($this->getSupportedCurrencies($method) as $country) {
            if ($country['id'] === $currency) {
                return true }
        }

        return false;
    }

    /**
     * Get supported currencies from Mercado Pago API
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return array
     */
    public function getSupportedCurrencies(WC_WooMercadoPagoSplit_PaymentAbstract $method): array
    {
        if (is_null($this->supportedCurrencies)) {
            try {
                $request = [
                    'uri' => '/currencies',
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->getAccessToken($method)
                    ]
                ];

                $result = MeliRestClientSplit::get($request);

                if (!isset($result['response'])) {
                    return [];
                }

                $this->supportedCurrencies = $result['response'];
            } catch (Exception $e) {
                $this->log->write_log(__FUNCTION__, 'Error fetching supported currencies: ' . $e->getMessage());
                $this->supportedCurrencies = [];
            }
        }

        return $this->supportedCurrencies;
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return array
     */
    public function getField(WC_WooMercadoPagoSplit_PaymentAbstract $method): array
    {
        return [
            'title'       => __('Convert Currency', 'woocommerce-mercadopago-split'),
            'type'        => 'select',
            'default'     => 'no',
            'description' => $this->msg_description,
            'options'     => [
                'no'  => __('No', 'woocommerce-mercadopago-split'),
                'yes' => __('Yes', 'woocommerce-mercadopago-split'),
            ],
        ];
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @param array $oldData
     * @param array $newData
     */
    public function scheduleNotice(WC_WooMercadoPagoSplit_PaymentAbstract $method, array $oldData, array $newData): void
    {
        if (!isset($oldData[self::CONFIG_KEY]) || !isset($newData[self::CONFIG_KEY])) {
            return;
        }

        if ($oldData[self::CONFIG_KEY] !== $newData[self::CONFIG_KEY]) {
            $_SESSION[self::CONFIG_KEY]['notice'] = [
                'type'   => $newData[self::CONFIG_KEY] === 'yes' ? 'enabled' : 'disabled',
                'method' => $method,
            ];
        }
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     */
    public function notices(WC_WooMercadoPagoSplit_PaymentAbstract $method): void
    {
        $show = $_SESSION[self::CONFIG_KEY] ?? [];
        $localCurrency = get_woocommerce_currency();
        $accountCurrency = $this->getAccountCurrency($method);

        if ($localCurrency === $accountCurrency || empty($accountCurrency)) {
            return;
        }

        if (isset($show['notice'])) {
            unset($_SESSION[self::CONFIG_KEY]['notice']);
            if ($show['notice']['type'] === 'enabled') {
                echo $this->noticeEnabled($method);
            } elseif ($show['notice']['type'] === 'disabled') {
                echo $this->noticeDisabled($method);
            }
        }

        if (!$this->isEnabled($method) && !$this->isShowingAlert && $method->isCurrencyConvertable()) {
            echo $this->noticeWarning($method);
        }
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string
     */
    public function noticeEnabled(WC_WooMercadoPagoSplit_PaymentAbstract $method): string
    {
        $localCurrency = get_woocommerce_currency();
        $currency = $this->getAccountCurrency($method);
        $message = sprintf(__('Now we convert your currency from %s to %s.', 'woocommerce-mercadopago-split'), $localCurrency, $currency);
        return WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, 'notice-error');
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string
     */
    public function noticeDisabled(WC_WooMercadoPagoSplit_PaymentAbstract $method): string
    {
        $localCurrency = get_woocommerce_currency();
        $currency = $this->getAccountCurrency($method);
        $message = sprintf(__('We no longer convert your currency from %s to %s.', 'woocommerce-mercadopago-split'), $localCurrency, $currency);
        return WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, 'notice-error');
    }

    /**
     * @param WC_WooMercadoPagoSplit_PaymentAbstract $method
     * @return string
     */
    public function noticeWarning(WC_WooMercadoPagoSplit_PaymentAbstract $method): string
    {
        global $current_section;

        if (in_array($current_section, [$method->id, sanitize_title(get_class($method))], true)) {
            $this->isShowingAlert = true;
            $message = __('<b>Attention:</b> The currency settings you have in WooCommerce are not compatible with the currency you use in your Mercado Pago account. Please activate the currency conversion.', 'woocommerce-mercadopago-split');
            return WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, 'notice-error');
        }

        return '';
    }

    /**
     * @param string $str
     * @param mixed ...$values
     * @return string
     */
    private function __($str, ...$values): string
    {
        return !empty($values) ? vsprintf($str, $values) : $str;
    }
}
