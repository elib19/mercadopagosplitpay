<?php
/**
 * Script de desinstalação do Mercado Pago Split Payment
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

function mp_split_uninstall() {
    // Remove configurações do plugin
    delete_option('mp_split_settings');
}

mp_split_uninstall();
