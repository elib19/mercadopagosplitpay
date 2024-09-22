<?php
/**
 * Plugin Name: Mercado Pago Split Payment for WCFM
 * Plugin URI: https://seumarketplace.com
 * Description: Plugin de integração de split de pagamento do Mercado Pago com WCFM Multivendor.
 * Version: 1.0.0
 * Author: Eli Silva
 * Author URI: https://brasilnarede.online
 * Text Domain: mercado-pago-split-wcfm
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evitar acesso direto
}

// Carregar a biblioteca do Mercado Pago
require_once plugin_dir_path( __FILE__ ) . 'lib/mercadopago.php'; // SDK do Mercado Pago

// Incluir arquivos do admin
if ( is_admin() ) {
    require_once plugin_dir_path( __FILE__ ) . 'admin/class-mp-split-admin.php';
}

// Inicializar o plugin
add_action( 'plugins_loaded', 'mp_split_init' );

function mp_split_init() {
    // Verificar se WooCommerce e WCFM estão ativos
    if ( class_exists( 'WooCommerce' ) && class_exists( 'WCFMmp' ) ) {
        include_once plugin_dir_path( __FILE__ ) . 'includes/class-mp-split-wcfm.php';
    } else {
        add_action( 'admin_notices', 'mp_split_wc_notice' );
    }
}

function mp_split_wc_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php _e( 'O WooCommerce e o WCFM Multivendor precisam estar ativos para o Mercado Pago Split funcionar!', 'mercado-pago-split-wcfm' ); ?></p>
    </div>
    <?php
}
