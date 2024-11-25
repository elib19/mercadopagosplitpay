<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class MeliRestClientSplit
 */
class MeliRestClientSplit extends AbstractRestClientSplit
{
    const API_MELI_BASE_URL = 'https://api.mercadolibre.com';

    /**
     * Send a GET request to the API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function get(array $request): ?array
    {
        $request['method'] = 'GET';
        return self::execAbs($request, self::API_MELI_BASE_URL);
    }

    /**
     * Send a POST request to the API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function post(array $request): ?array
    {
        $request['method'] = 'POST';
        return self::execAbs($request, self::API_MELI_BASE_URL);
    }

    /**
     * Send a PUT request to the API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function put(array $request): ?array
    {
        $request['method'] = 'PUT';
        return self::execAbs($request, self::API_MELI_BASE_URL);
    }

    /**
     * Send a DELETE request to the API.
     *
     * @param array $request The request parameters.
     * @return array|null The response from the API.
     * @throws WC_WooMercadoPagoSplit_Exception If an error occurs during the request.
     */
    public static function delete(array $request): ?array
    {
        $request['method'] = 'DELETE';
        return self::execAbs($request, self::API_MELI_BASE_URL);
    }
}
