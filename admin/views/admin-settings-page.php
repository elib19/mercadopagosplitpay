<div class="wrap">
    <h1><?php _e( 'Configurações do Mercado Pago Split', 'mercado-pago-split-wcfm' ); ?></h1>

    <form method="post" action="options.php">
        <?php settings_fields( 'mp-split-settings-group' ); ?>
        <?php do_settings_sections( 'mp-split-settings-group' ); ?>

        <table class="form-table">
            <tr valign="top">
                <th scope="row"><?php _e( 'Access Token do Mercado Pago', 'mercado-pago-split-wcfm' ); ?></th>
                <td><input type="text" name="mp_access_token" value="<?php echo esc_attr( get_option('mp_access_token') ); ?>" size="50" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'Taxa de Aplicação (%)', 'mercado-pago-split-wcfm' ); ?></th>
                <td><input type="number" name="mp_application_fee" value="<?php echo esc_attr( get_option('mp_application_fee') ); ?>" size="50" min="0" max="100" /></td>
            </tr>
            <tr valign="top">
                <th scope="row"><?php _e( 'Chave PIX do Mercado Pago', 'mercado-pago-split-wcfm' ); ?></th>
                <td><input type="text" name="mp_pix_key" value="<?php echo esc_attr( get_option('mp_pix_key') ); ?>" size="50" /></td>
            </tr>
        </table>

        <?php submit_button(); ?>
    </form>
</div>
