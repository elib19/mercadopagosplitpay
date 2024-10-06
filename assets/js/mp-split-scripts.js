// Scripts para a página de configurações do Mercado Pago
jQuery(document).ready(function($) {
    // Exemplo: Ao clicar no botão de salvar configurações, exibe uma mensagem
    $('form').on('submit', function(e) {
        // Previne o envio padrão do formulário, se necessário
        e.preventDefault();
        
        // Validação simples dos campos
        var accessToken = $('input[name="mercado_pago_settings[access_token]"]').val();
        var publicKey = $('input[name="mercado_pago_settings[public_key]"]').val();
        
        if (!accessToken || !publicKey) {
            alert('Por favor, preencha todos os campos obrigatórios.');
            return;
        }

        // Se a validação passar, você pode prosseguir com o envio do formulário
        this.submit();
    });

    // Exemplo de manipulação de um checkbox
    $('input[name="mercado_pago_settings[sandbox]"]').on('change', function() {
        if ($(this).is(':checked')) {
            alert('Modo Sandbox habilitado. Transações não serão processadas.');
        } else {
            alert('Modo Sandbox desabilitado. Transações serão processadas normalmente.');
        }
    });
});
