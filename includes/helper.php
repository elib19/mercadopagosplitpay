<?php
/**
 * Funções auxiliares para o Mercado Pago Split Payment
 */

function mp_split_is_valid_amount($value) {
    return is_numeric($value) && $value > 0;
}

function mp_split_format_currency($amount) {
    return number_format($amount, 2, ',', '.');
}

function mp_split_are_settings_complete() {
    $options = get_option('mp_split_settings');
    return !empty($options['mp_split_app_id']) && !empty($options['mp_split_client_secret']) && !empty($options['mp_split_redirect_uri']);
}

function mp_split_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[MP Split Error] ' . $message);
    }
}

