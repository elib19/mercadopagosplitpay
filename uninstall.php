<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Função para desinstalar o plugin
 */
function mercado_pago_uninstall() {
    // Remove opções do banco de dados
    delete_option('mercado_pago_settings');
    
    // Se necessário, adicione código para remover tabelas do banco de dados aqui
    global $wpdb;
    $table_name = $wpdb->prefix . 'mercado_pago_transactions';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
}

// Aciona a função de desinstalação ao desinstalar o plugin
register_uninstall_hook(__FILE__, 'mercado_pago_uninstall');
