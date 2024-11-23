<?php
// includes/class-wcfm-mercado-pago-gateway.php

class WCFMmp_Gateway_Mercado_Pago {
    public function __construct() {
        // Adiciona o método de pagamento
        add_filter('wcfm_marketplace_withdrwal_payment_methods', [$this, 'add_gateway']);
        // Adiciona configurações ao método de pagamento
        add_filter('wcfm_marketplace_settings_fields_withdrawal_payment_keys', [$this, 'add_settings'], 50, 2);
    }

    // Adiciona o Mercado Pago como método de pagamento
    public function add_gateway($payment_methods) {
        $payment_methods['mercado_pago'] = 'Mercado Pago';
        return $payment_methods;
    }

    // Adiciona as configurações para o Mercado Pago
    public function add_settings($payment_keys, $wcfm_withdrawal_options) {
        $gateway_slug = 'mercado_pago';
        $payment_keys = array_merge($payment_keys, [
            "withdrawal_{$gateway_slug}_client_id" => [
                'label' => __('Mercado Pago Client ID', 'wcfmmp'),
                'name'  => "wcfm_withdrawal_options[{$gateway_slug}_client_id]",
                'type'  => 'text',
                'value' => $wcfm_withdrawal_options["{$gateway_slug}_client_id"] ?? '',
            ],
            "withdrawal_{$gateway_slug}_secret_key" => [
                'label' => __('Mercado Pago Secret Key', 'wcfmmp'),
                'name'  => "wcfm_withdrawal_options[{$gateway_slug}_secret_key]",
                'type'  => 'text',
                'value' => $wcfm_withdrawal_options["{$gateway_slug}_secret_key"] ?? '',
            ],
        ]);

        return $payment_keys;
    }
}
?>
