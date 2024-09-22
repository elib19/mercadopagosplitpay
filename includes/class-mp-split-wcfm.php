<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Evitar acesso direto
}

class MP_Split_WCFM {

    // Construtor que inicializa as ações e filtros necessários
    public function __construct() {
        // Inicializar classes de administração
        if ( is_admin() ) {
            $this->load_admin_classes();
        }

        // Inicializar as funções de split de pagamento
        add_action( 'woocommerce_payment_complete', array( $this, 'process_split_payment' ) );
    }

    // Carregar classes de administração
    private function load_admin_classes() {
        require_once plugin_dir_path( __FILE__ ) . '../admin/class-mp-split-admin.php';
    }

    // Processar split de pagamento após conclusão do pagamento
    public function process_split_payment( $order_id ) {
        $order = wc_get_order( $order_id );

        // Obter valor total do pedido
        $transaction_amount = $order->get_total();

        // Obter a taxa de aplicação configurada
        $application_fee = MP_Split_Helper::get_application_fee();

        // Obter a chave PIX do vendedor
        $vendor_id = $order->get_meta('_vendor_id'); // Assumindo que você tem um método para obter o ID do vendedor
        $pix_key = MP_Split_Helper::get_vendor_pix_key($vendor_id);

        // Inicializar a biblioteca do Mercado Pago
        $mp = new MercadoPagoLib();

        // Criar o pagamento com o split de taxa
        $response = $mp->split_payment($transaction_amount, $application_fee, $pix_key);

        // Verificar o status do pagamento e salvar informações no pedido
        if ( isset( $response['status'] ) && $response['status'] == 'approved' ) {
            $order->add_order_note( __( 'Payment approved via Mercado Pago Split.', 'mp-split' ) );
        } else {
            $order->add_order_note( __( 'Error processing Mercado Pago payment.', 'mp-split' ) );
        }
    }

}

new MP_Split_WCFM();
