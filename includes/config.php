<?php
// includes/config.php

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

// Define constants.
define('MERCADO_PAGO_CLIENT_ID', 'YOUR_CLIENT_ID');
define('MERCADO_PAGO_CLIENT_SECRET', 'YOUR_CLIENT_SECRET');
define('MERCADO_PAGO_REDIRECT_URI', 'https://juntoaqui.com.br?store-setup=yes&step=payment/callback');
