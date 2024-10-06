<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Exibe a lista de transações
 */
function mercado_pago_transactions_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mercado_pago_transactions';

    // Consulta para obter transações
    $transactions = $wpdb->get_results("SELECT * FROM $table_name");

    ?>
    <div class="wrap">
        <h1>Transações Mercado Pago</h1>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>ID da Transação</th>
                    <th>ID do Vendedor</th>
                    <th>Valor</th>
                    <th>Descrição</th>
                    <th>Status</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($transactions) : ?>
                    <?php foreach ($transactions as $transaction) : ?>
                        <tr>
                            <td><?php echo esc_html($transaction->id); ?></td>
                            <td><?php echo esc_html($transaction->transaction_id); ?></td>
                            <td><?php echo esc_html($transaction->vendor_id); ?></td>
                            <td><?php echo esc_html($transaction->amount); ?></td>
                            <td><?php echo esc_html($transaction->description); ?></td>
                            <td><?php echo esc_html($transaction->status); ?></td>
                            <td><?php echo esc_html($transaction->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="7">Nenhuma transação encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
