<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class WC_WooMercadoPagoSplit_Log
 */
class WC_WooMercadoPagoSplit_Log
{
    private WC_Logger $log;
    private string $id;
    private bool $debugMode;

    /**
     * WC_WooMercadoPagoSplit_Log constructor.
     *
     * @param object|null $payment
     * @param bool $debugMode
     */
    public function __construct(?object $payment = null, bool $debugMode = false)
    {
        $this->setDebugMode($payment, $debugMode);
        if ($payment !== null) {
            $this->id = get_class($payment);
        }
        $this->initLog();
    }

    /**
     * Set the debug mode based on payment and provided debug mode.
     *
     * @param object|null $payment
     * @param bool $debugMode
     */
    private function setDebugMode(?object $payment, bool $debugMode): void
    {
        if ($payment !== null) {
            $debugMode = $payment->debug_mode === 'no' ? false : true;
        }

        if ($payment === null && !$debugMode) {
            $debugMode = true;
        }

        $this->debugMode = $debugMode;
    }

    /**
     * Initialize the logger.
     *
     * @return WC_Logger|null
     */
    private function initLog(): ?WC_Logger
    {
        if ($this->debugMode) {
            if (class_exists('WC_Logger')) {
                $this->log = new WC_Logger();
            } else {
                $this->log = WC_WooMercadoPagoSplit_Module::woocommerce_instance()->logger();
            }
            return $this->log;
        }
        return null;
    }

    /**
     * Initialize Mercado Pago log.
     *
     * @param string|null $id
     * @return WC_WooMercadoPagoSplit_Log|null
     */
    public static function init_mercado_pago_log(?string $id = null): ?self
    {
        $log = new self(null, true);
        if ($id !== null) {
            $log->setId($id);
        }
        return $log;
    }

    /**
     * Write log message.
     *
     * @param string $function
     * @param string $message
     */
    public function write_log(string $function, string $message): void
    {
        if ($this->debugMode) {
            $this->log->add($this->id, '[' . $function . ']: ' . $message);
        }
    }

    /**
     * Set the log ID.
     *
     * @param string $id
     */
    public function setId(string $id): void
    {
        $this->id = $id;
    }
}
