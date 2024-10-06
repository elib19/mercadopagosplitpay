<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

class Mercado_Pago_Vendor {
    public static function init() {
        // Adiciona hooks e lógica para gerenciar vendedores
        add_action('wcfm_after_vendors_settings', array(__CLASS__, 'vendor_settings'));
    }

    public static function vendor_settings() {
        // Renderiza as configurações específicas do vendedor
        ?>
        <div id="mp-split-vendor-settings">
            <h2><?php _e('Configurações do Mercado Pago', 'mercado-pago-split'); ?></h2>
            <!-- Campos de configurações do vendedor -->
        </div>
        <?php
    }

    public static function make_payment($payment_data, $user_id) {
        // Lógica para processar o pagamento com o Mercado Pago
        $api_url = 'https://api.mercadopago.com/v1/payments?access_token=' . get_option('mercado_pago_settings')['access_token'];

        $response = wp_remote_post($api_url, array(
            'method'    => 'POST',
            'body'      => json_encode($payment_data),
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
        ));

        return json_decode(wp_remote_retrieve_body($response));
    }
}

// Inicializa a classe de vendedores
Mercado_Pago_Vendor::init();
