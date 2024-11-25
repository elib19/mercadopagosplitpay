<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Class MPRestClientSplit
 */
class MPRestClientSplit extends AbstractRestClientSplit
{
    /**
     * Sends a GET request to the Mercado Pago API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API or null on failure.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function get(array $request): ?array
    {
        $request['method'] = 'GET';
        return self::execAbs($request, WC_WooMercadoPagoSplit_Constants::API_MP_BASE_URL);
    }

    /**
     * Sends a POST request to the Mercado Pago API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API or null on failure.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function post(array $request): ?array
    {
        $request['method'] = 'POST';
        return self::execAbs($request, WC_WooMercadoPagoSplit_Constants::API_MP_BASE_URL);
    }

    /**
     * Sends a PUT request to the Mercado Pago API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API or null on failure.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function put(array $request): ?array
    {
        $request['method'] = 'PUT';
        return self::execAbs($request, WC_WooMercadoPagoSplit_Constants::API_MP_BASE_URL);
    }

    /**
     * Sends a DELETE request to the Mercado Pago API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API or null on failure.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function delete(array $request): ?array
    {
        $request['method'] = 'DELETE';
        return self::execAbs($request, WC_WooMercadoPagoSplit_Constants::API_MP_BASE_URL);
    }
}
