<?php
function mp_split_add_vendor_settings( $vendor_id ) {
    ?>
    <h2>Configurações do Mercado Pago</h2>
    <table class="form-table">
        <tr>
            <th scope="row"><label for="mp_access_token">Access Token</label></th>
            <td>
                <input type="text" name="mp_access_token" id="mp_access_token" value="<?php echo esc_attr( get_user_meta( $vendor_id, 'mp_access_token', true ) ); ?>" class="regular-text" />
                <p class="description">Insira seu Access Token do Mercado Pago.</p>
            </td>
        </tr>
        <tr>
            <th scope="row"><label for="mp_sponsor_id">Sponsor ID</label></th>
            <td>
                <input type="text" name="mp_sponsor_id" id="mp_sponsor_id" value="<?php echo esc_attr( get_user_meta( $vendor_id, 'mp_sponsor_id', true ) ); ?>" class="regular-text" />
                <p class="description">Insira seu Sponsor ID do Mercado Pago.</p>
            </td>
        </tr>
    </table>
    <?php
}

// Salvar as configurações do vendedor
add_action( 'wcfm_vendors_settings_update', function( $vendor_id ) {
    MP_Split_Helper::save_vendor_settings( $vendor_id, $_POST );
} );
