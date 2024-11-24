<?php

class MP_WCFM_Connector {

    public static function init() {
        add_action('wcfm_marketplace_settings', [__CLASS__, 'add_mp_settings']);
    }

    public static function add_mp_settings() {
        $vendor_id = get_current_user_id();
        $access_token = get_user_meta($vendor_id, '_mp_access_token', true);

        echo '<h2>Integração Mercado Pago</h2>';

        if ($access_token) {
            echo '<p>Conta Mercado Pago conectada!</p>';
        } else {
            $connect_url = 'https://auth.mercadopago.com/authorization?response_type=code&client_id=SUA_CLIENT_ID&redirect_uri=' . urlencode(admin_url('admin-post.php?action=mp_connect'));
            echo '<a href="' . esc_url($connect_url) . '" class="button">Conectar ao Mercado Pago</a>';
        }
    }
}
