<div id="mp-split-settings">
    <h2>Configurações de Split de Pagamento Mercado Pago</h2>

    <form method="post" action="options.php">
        <?php settings_fields( 'mp_split_settings_group' ); ?>
        <?php do_settings_sections( 'mp_split_settings_group' ); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row">Access Token da Loja</th>
                <td><input type="text" name="mp_split_access_token" value="<?php echo get_option( 'mp_split_access_token' ); ?>" /></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
