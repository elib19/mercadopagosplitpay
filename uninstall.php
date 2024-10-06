<?php
if (!defined('ABSPATH')) {
    exit; // Impede acesso direto
}

// Limpa as configurações do plugin ao desinstalar
delete_option('mercado_pago_settings');

// Se o plugin armazenou dados personalizados, remova-os também
global $wpdb;

// Exemplo: Remover uma tabela personalizada (caso tenha)
$table_name = $wpdb->prefix . 'mercado_pago_custom_table';
$wpdb->query("DROP TABLE IF EXISTS $table_name");

// Exemplo: Remover post types personalizados, se houver
$wpdb->query("DELETE FROM $wpdb->posts WHERE post_type = 'mercado_pago'");

// Exemplo: Remover termos personalizados do taxonomia, se houver
$wpdb->query("DELETE FROM $wpdb->term_relationships WHERE term_id IN (SELECT term_id FROM $wpdb->terms WHERE slug = 'mercado_pago_slug')");

// Exemplo: Limpar metadados, se necessário
$wpdb->query("DELETE FROM $wpdb->postmeta WHERE meta_key = '_mercado_pago_meta_key'");
