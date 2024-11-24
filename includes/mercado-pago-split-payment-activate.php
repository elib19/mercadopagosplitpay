<?php
// Função de ativação do plugin
function mercado_pago_split_payment_activate() {
    // Verificar se as credenciais do Mercado Pago estão definidas
    if (!defined('MERCADO_PAGO_CLIENT_ID') || !defined('MERCADO_PAGO_CLIENT_SECRET')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('O plugin Mercado Pago Split Payment requer as credenciais do Mercado Pago.');
    }

    // Verificar se o WooCommerce está instalado
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        deactivate_plugins(plugin_basename(__FILE__));
        wp_die('O plugin Mercado Pago Split Payment requer o WooCommerce.');
    }
}
register_activation_hook(__FILE__, 'mercado_pago_split_payment_activate');
