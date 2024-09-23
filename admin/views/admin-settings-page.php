<div class="mp-split-admin-page">
    <h2><?php _e('Configurações do Mercado Pago Split', 'mp-split'); ?></h2>
    <form action="options.php" method="post">
        <?php
        settings_fields('mpSplit');
        do_settings_sections('mpSplit');
        submit_button(__('Salvar Configurações', 'mp-split'));
        ?>
    </form>
</div>
