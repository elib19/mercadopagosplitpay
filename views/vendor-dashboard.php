<div class="wrap">
    <h2><?php _e('Dashboard do Vendedor', 'woocommerce'); ?></h2>
    
    <form id="payment-form">
        <label for="amount"><?php _e('Valor', 'woocommerce'); ?></label>
        <input type="text" name="amount" id="amount" required>

        <label for="description"><?php _e('Descrição', 'woocommerce'); ?></label>
        <input type="text" name="description" id="description" required>

        <label for="email"><?php _e('Email do Cliente', 'woocommerce'); ?></label>
        <input type="email" name="email" id="email" required>

        <button type="submit"><?php _e('Processar Pagamento', 'woocommerce'); ?></button>
    </form>

    <div id="payment-response"></div>
</div>

<script>
    document.getElementById('payment-form').onsubmit = function(event) {
        event.preventDefault();

        let formData = new FormData(this);
        let paymentData = {
            amount: formData.get('amount'),
            description: formData.get('description'),
            email: formData.get('email')
        };

        fetch('<?php echo admin_url('admin-ajax.php'); ?>?action=wcfm_vendors_ajax_process_payment', {
            method: 'POST',
            body: JSON.stringify({ payment_data: paymentData }),
            headers: {
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('payment-response').innerText = JSON.stringify(data);
        })
        .catch(error => {
            console.error('Error:', error);
        });
    };
</script>
