<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="mp-panel-checkout">
  <div class="mp-row-checkout">

    <?php if($credito != 0): ?>
    <div class="mp-col-md-12">
        <div class="frame-tarjetas">
            <p class="mp-subtitle-basic-checkout">
                <?php echo esc_html__( 'Credit cards', 'woocommerce-mercadopago-split' ); ?>
                <span class="mp-badge-checkout"><?php echo esc_html__( 'Until', 'woocommerce-mercadopago-split' ) . ' ' . esc_html( $installments ) . ' ' . esc_html( $str_cuotas ); ?></span>
            </p>

            <?php foreach($tarjetas as $tarjeta): ?>
              <?php if ($tarjeta['type'] === 'credit_card'): ?>
                <img src="<?php echo esc_url( $tarjeta['image'] ); ?>" class="mp-img-fluid mp-img-tarjetas" alt="<?php echo esc_attr__( 'Credit Card', 'woocommerce-mercadopago-split' ); ?>"/>
              <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($debito != 0): ?>
    <div class="mp-col-md-6 mp-pr-15">
        <div class="frame-tarjetas">
            <p class="submp-title-checkout"><?php echo esc_html__( 'Debit card', 'woocommerce-mercadopago-split' ); ?></p>

            <?php foreach($tarjetas as $tarjeta): ?>
              <?php if (in_array($tarjeta['type'], ['debit_card', 'prepaid_card'])): ?>
                <img src="<?php echo esc_url( $tarjeta['image'] ); ?>" class="mp-img-fluid mp-img-tarjetas" alt="<?php echo esc_attr__( 'Debit Card', 'woocommerce-mercadopago-split' ); ?>" />
              <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($efectivo != 0): ?>
    <div class="mp-col-md-6">
        <div class="frame-tarjetas">
            <p class="submp-title-checkout"><?php echo esc_html__( 'Payments in cash', 'woocommerce-mercadopago-split' ); ?></p>

            <?php foreach($tarjetas as $tarjeta): ?>
              <?php if (!in_array($tarjeta['type'], ['credit_card', 'debit_card', 'prepaid_card'])): ?>
                <img src="<?php echo esc_url( $tarjeta['image'] ); ?>" class="mp-img-fluid mp-img-tarjetas" alt="<?php echo esc_attr__( 'Cash Payment', 'woocommerce-mercadopago-split' ); ?>"/>
              <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <?php if($method === 'redirect'): ?>
    <div class="mp-col-md-12 mp-pt-20">
        <div class="mp-redirect-frame">
            <img src="<?php echo esc_url( $cho_image ); ?>" class="mp-img-fluid mp-img-redirect" alt="<?php echo esc_attr__( 'Redirect to Payment', 'woocommerce-mercadopago-split' ); ?>"/>
            <p><?php echo esc_html__( 'We take you to our site to complete the payment', 'woocommerce-mercadopago-split' ); ?></p>
        </div>
    </div>
    <?php endif; ?>

  </div>
</div>

<script type="text/javascript" src="<?php echo esc_url( $path_to_javascript ); ?>?ver=<?php echo esc_attr( $plugin_version ); ?>"></script>
