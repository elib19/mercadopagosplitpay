<?php
// Função de desativação do plugin
function mercado_pago_split_payment_deactivate() {
    // Aqui você pode adicionar qualquer limpeza ou redefinição de configurações se necessário
}
register_deactivation_hook(__FILE__, 'mercado_pago_split_payment_deactivate');
