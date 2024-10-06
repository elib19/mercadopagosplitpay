<h2>Dashboard do Vendedor</h2>

<table>
    <tr>
        <th>Data da Venda</th>
        <th>Preço do Produto</th>
        <th>Taxa do Marketplace</th>
        <th>Lucro Total</th>
    </tr>
    <?php
    // Obtenha o ID do vendedor logado
    $user_id = get_current_user_id();

    global $wpdb;
    $table_name = $wpdb->prefix . 'mercado_pago_sales'; // Nome da tabela com as vendas

    // Busque as vendas do vendedor
    $sales = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name WHERE seller_id = %d",
        $user_id
    ));

    if ($sales) {
        foreach ($sales as $sale) {
            // Calcule a taxa do marketplace e o lucro total
            $marketplace_fee = $sale->price * 0.10; // 10% de taxa
            $total_profit = $sale->price - $marketplace_fee;

            echo '<tr>';
            echo '<td>' . date('d/m/Y H:i:s', strtotime($sale->sale_date)) . '</td>'; // Formata a data da venda
            echo '<td>R$ ' . number_format($sale->price, 2, ',', '.') . '</td>'; // Preço do produto
            echo '<td>R$ ' . number_format($marketplace_fee, 2, ',', '.') . '</td>'; // Taxa do marketplace
            echo '<td>R$ ' . number_format($total_profit, 2, ',', '.') . '</td>'; // Lucro total
            echo '</tr>';
        }
    } else {
        echo '<tr><td colspan="4">Nenhuma venda encontrada.</td></tr>';
    }
    ?>
</table>
