<?php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class WC_WooMercadoPagoSplit_PreferenceAnalytics {
    private static array $ignoreFields = [
        '_mp_public_key_prod', 
        '_mp_public_key_test', 
        'title', 
        'description', 
        '_mp_access_token_prod', 
        '_mp_access_token_test'
    ];

    /**
     * Retrieve basic settings.
     *
     * @return array Valid basic settings.
     */
    public function getBasicSettings(): array {
        return $this->getSettings('woocommerce_woo-mercado-pago-split-basic_settings');
    }

    /**
     * Retrieve custom settings.
     *
     * @return array Valid custom settings.
     */
    public function getCustomSettings(): array {
        return $this->getSettings('woocommerce_woo-mercado-pago-split-custom_settings');
    }

    /**
     * Retrieve ticket settings.
     *
     * @return array Valid ticket settings.
     */
    public function getTicketSettings(): array {
        return $this->getSettings('woocommerce_woo-mercado-pago-split-ticket_settings');
    }

    /**
     * Retrieve settings from the database, filtering out invalid values.
     *
     * @param string $option The option name to retrieve.
     * @return array Filtered settings.
     */
    private function getSettings(string $option): array {
        $dbOptions = get_option($option, []);
        $validValues = [];

        foreach ($dbOptions as $key => $value) {
            if (!empty($value) && !in_array($key, self::$ignoreFields, true)) {
                $validValues[$key] = $value;
            }
        }

        return $validValues;
    }
}
