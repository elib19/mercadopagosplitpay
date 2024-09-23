<?php
/**
 * Helper Functions for Mercado Pago Split Payment
 */

/**
 * Função para validar se um valor é numérico e maior que zero.
 *
 * @param mixed $value O valor a ser validado.
 * @return bool Verdadeiro se o valor for numérico e maior que zero, falso caso contrário.
 */
function mp_split_is_valid_amount($value) {
    return is_numeric($value) && $value > 0;
}

/**
 * Função para formatar um valor monetário em formato padrão.
 *
 * @param float $amount O valor a ser formatado.
 * @return string O valor formatado como moeda.
 */
function mp_split_format_currency($amount) {
    return number_format($amount, 2, ',', '.');
}

/**
 * Função para verificar se as configurações do Mercado Pago estão completas.
 *
 * @return bool Verdadeiro se todas as configurações estiverem preenchidas, falso caso contrário.
 */
function mp_split_are_settings_complete() {
    $options = get_option('mp_split_settings');
    return !empty($options['mp_split_app_id']) && !empty($options['mp_split_client_secret']) && !empty($options['mp_split_redirect_uri']);
}

/**
 * Função para enviar um erro para o log do WordPress.
 *
 * @param string $message Mensagem de erro a ser registrada.
 */
function mp_split_log_error($message) {
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[MP Split Error] ' . $message);
    }
}
