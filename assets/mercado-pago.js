document.addEventListener("DOMContentLoaded", function() {
    // Implementação do Mercado Pago Checkout Transparente
    const mp = new MercadoPago('APP_USR-9c2f7f21-8f97-470b-b951-839924017b41', {
        locale: 'pt-BR'
    });

    // Código para gerenciar o pagamento
    const checkoutForm = document.getElementById('checkout-form');
    checkoutForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(checkoutForm);
        const paymentData = {
            transactionAmount: parseFloat(formData.get('transactionAmount')),
            token: formData.get('token'),
            description: formData.get('description'),
            installments: parseInt(formData.get('installments')),
            paymentMethodId: formData.get('paymentMethodId'),
            payer: {
                email: formData.get('email'),
            }
        };

        mp.createPayment(paymentData).then(function(response) {
            if (response.status === 'approved') {
                // Pagamento aprovado
                window.location.href = '/thank-you'; // Redirecionar para a página de agradecimento
            } else {
                // Lidar com erro
                alert('Erro no pagamento: ' + response.status);
            }
        });
    });
});
