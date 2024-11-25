jQuery(document).ready(function($) {
    // Validação dos campos de configuração do plugin
    $('#woocommerce_mercado_pago_client_id, #woocommerce_mercado_pago_client_secret').on('input', function() {
        validateFields();
    });

    function validateFields() {
        var clientId = $('#woocommerce_mercado_pago_client_id').val();
        var clientSecret = $('#woocommerce_mercado_pago_client_secret').val();
        
        if (clientId === '' || clientSecret === '') {
            $('#mp-validation-message').text('Por favor, preencha todos os campos obrigatórios.').show();
        } else {
            $('#mp-validation-message').hide();
        }
    }

    // Exibir mensagem de sucesso após salvar as configurações
    if ($('#setting-error-settings_updated').length) {
        $('<div class="mp-success-message">Configurações salvas com sucesso!</div>').insertAfter('#setting-error-settings_updated').fadeOut(5000);
    }
});
