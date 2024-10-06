<?php

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

/**
 * Exibe o relatório do vendedor
 */
function mercado_pago_vendor_report_page() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'mercado_pago_transactions';

    // Consulta para obter o relatório do vendedor
    $vendor_id = get_current_user_id(); // Assume que o vendedor está logado
    $transactions = $wpdb->get_results("SELECT * FROM $table_name WHERE vendor_id = $vendor_id");

    // Calcula lucros e comissões
    $total_profit = 0;
    $total_commission = 0;

    foreach ($transactions as $transaction) {
        $total_profit += $transaction->amount; // Aqui você deve implementar a lógica para calcular o lucro real
        $total_commission += $transaction->amount * 0.1; // Exemplo de 10% de comissão
    }

    ?>
    <div class="wrap">
        <h1>Relatório do Vendedor</h1>
        <p><strong>Total de Lucros:</strong> <?php echo esc_html(number_format($total_profit, 2)); ?></p>
        <p><strong>Total de Comissões:</strong> <?php echo esc_html(number_format($total_commission, 2)); ?></p>
        <h2>Transações</h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th>ID da Transação</th>
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
                            <td><?php echo esc_html($transaction->transaction_id); ?></td>
                            <td><?php echo esc_html($transaction->amount); ?></td>
                            <td><?php echo esc_html($transaction->description); ?></td>
                            <td><?php echo esc_html($transaction->status); ?></td>
                            <td><?php echo esc_html($transaction->created_at); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr>
                        <td colspan="5">Nenhuma transação encontrada.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php
}
