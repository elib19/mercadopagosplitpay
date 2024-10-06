<?php
// Este arquivo é utilizado para renderizar as configurações do Mercado Pago para os vendedores.
// Evitar acesso direto ao arquivo
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Proteção básica
}

// Função para exibir as configurações do Mercado Pago no painel do vendedor
function mp_split_render_vendor_settings( $vendor_id ) {
    // Obter as credenciais do vendedor
    $access_token = get_user_meta( $vendor_id, 'mp_access_token', true ); // Obtenha do meta do usuário
    $sponsor_id = get_user_meta( $vendor_id, 'mp_sponsor_id', true ); // Obtenha do meta do usuário

    // Renderizar o formulário de configurações
    ?>
    <div id="mp-split-vendor-settings">
        <h2>Configurações do Mercado Pago</h2>
        <form method="post" action="">
            <?php wp_nonce_field( 'mp_split_save_settings', 'mp_split_nonce' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><label for="mp_access_token">Access Token</label></th>
                    <td>
                        <input type="text" name="mp_access_token" id="mp_access_token" value="<?php echo esc_attr( $access_token ); ?>" class="regular-text" required />
                        <p class="description">Insira seu Access Token do Mercado Pago.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="mp_sponsor_id">Sponsor ID</label></th>
                    <td>
                        <input type="text" name="mp_sponsor_id" id="mp_sponsor_id" value="<?php echo esc_attr( $sponsor_id ); ?>" class="regular-text" required />
                        <p class="description">Insira seu Sponsor ID do Mercado Pago.</p>
                    </td>
                </tr>
            </table>
            <p class="submit">
                <button type="submit" class="button button-primary">Salvar Configurações</button>
            </p>
        </form>
    </div>
    <?php
}

// Função para salvar as configurações do vendedor
add_action( 'wcfm_vendors_settings_update', function( $vendor_id ) {
    if ( ! isset( $_POST['mp_split_nonce'] ) || ! wp_verify_nonce( $_POST['mp_split_nonce'], 'mp_split_save_settings' ) ) {
        return; // Verificação de segurança
    }

    // Salvar as configurações
    update_user_meta( $vendor_id, 'mp_access_token', sanitize_text_field( $_POST['mp_access_token'] ) );
    update_user_meta( $vendor_id, 'mp_sponsor_id', sanitize_text_field( $_POST['mp_sponsor_id'] ) );
} );

// Hook para adicionar as configurações no painel do vendedor
add_action( 'wcfm_vendors_settings', function( $vendor_id ) {
    mp_split_render_vendor_settings( $vendor_id );
} );
