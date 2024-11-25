<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_WooMercadoPagoSplit_Exception
 *
 * Custom exception class for WooCommerce MercadoPago Split.
 */
class WC_WooMercadoPagoSplit_Exception extends Exception {

    /**
     * WC_WooMercadoPagoSplit_Exception constructor.
     *
     * @param string $message The exception message.
     * @param int $code The exception code (default is 500).
     * @param Exception|null $previous The previous exception used for the exception chaining (default is null).
     */
    public function __construct(string $message, int $code = 500, ?Exception $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
