<?php

/**
 * Part of Woo Mercado Pago Module
 * Author - Mercado Pago
 * Developer
 * Copyright - Copyright(c) MercadoPago [https://www.mercadopago.com]
 * License - https://www.gnu.org/licenses/gpl.html GPL version 2 or higher
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AbstractRestClientSplit
 */
class AbstractRestClientSplit
{
    public static $email_admin = '';
    public static $site_locale = '';
    public static $check_loop = 0;

    /**
     * @param array $request
     * @param string $url
     * @return array|null
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function execAbs(array $request, string $url): ?array
    {
        try {
            $connect = self::build_request($request, $url);
            return self::execute($request, $connect);
        } catch (Exception $e) {
            // Log the exception message for debugging
            error_log($e->getMessage());
            return null;
        }
    }

    /**
     * @param array $request
     * @param string $url
     * @return false|resource
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function build_request(array $request, string $url)
    {
        if (!extension_loaded('curl')) {
            throw new WC_WooMercadoPagoSplit_Exception('cURL extension not found. You need to enable cURL in your php.ini or another configuration you have.');
        }

        if (empty($request['method'])) {
            throw new WC_WooMercadoPagoSplit_Exception('No HTTP METHOD specified');
        }

        if (empty($request['uri'])) {
            throw new WC_WooMercadoPagoSplit_Exception('No URI specified');
        }

        $headers = ['Accept: application/json'];
        if ($request['method'] === 'POST') {
            $headers[] = 'x-product-id:' . (WC_WooMercadoPagoSplit_Module::is_mobile() ? WC_WooMercadoPagoSplit_Constants::PRODUCT_ID_MOBILE : WC_WooMercadoPagoSplit_Constants::PRODUCT_ID_DESKTOP);
            $headers[] = 'x-platform-id:' . WC_WooMercadoPagoSplit_Constants::PLATAFORM_ID;
            $headers[] = 'x-integrator-id:' . get_option('_mp_integrator_id', null);
        }

        $json_content = true;
        $form_content = false;
        $default_content_type = true;

        if (!empty($request['headers']) && is_array($request['headers'])) {
            foreach ($request['headers'] as $h => $v) {
                if (strcasecmp($h, 'content-type') === 0) {
                    $default_content_type = false;
                    $json_content = strcasecmp($v, 'application/json') === 0;
                    $form_content = strcasecmp($v, 'application/x-www-form-urlencoded') === 0;
                }
                $headers[] = "{$h}: {$v}";
            }
        }
        if ($default_content_type) {
            $headers[] = 'Content-Type: application/json';
        }

        $connect = curl_init();
        curl_setopt($connect, CURLOPT_USERAGENT, 'platform:v1-whitelabel,type:woocommerce,so:' . WC_WooMercadoPagoSplit_Constants::VERSION);
        curl_setopt($connect, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($connect, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($connect, CURLOPT_CAINFO, $GLOBALS['LIB_LOCATION'] . '/cacert.pem');
        curl_setopt($connect, CURLOPT_CUSTOMREQUEST, $request['method']);
        curl_setopt($connect, CURLOPT_HTTPHEADER, $headers);

        if (!empty($request['params']) && is_array($request['params'])) {
            if (count($request['params']) > 0) {
                $request['uri'] .= (strpos($request['uri'], '?') === false) ? '?' : '&';
                $request['uri'] .= self::build_query($request['params']);
            }
        }

        curl_setopt($connect, CURLOPT_URL, $url . $request['uri']);

        if (!empty($request['data'])) {
            if ($json_content) {
                $request['data'] = is_string($request['data']) ? $request['data'] : json_encode($request['data']);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    throw new WC_WooMercadoPagoSplit_Exception("JSON Error [{$json_last error}] - Data: " . $request['data']);
                }
            } elseif ($form_content) {
                $request['data'] = self::build_query($request['data']);
            }
            curl_setopt($connect, CURLOPT_POSTFIELDS, $request['data']);
        }

        return $connect;
    }

    /**
     * @param resource $connect
     * @return array|null
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public static function execute($request, $connect): ?array
    {
        $response = null;
        $api_result = curl_exec($connect);
        if (curl_errno($connect)) {
            throw new WC_WooMercadoPagoSplit_Exception(curl_error($connect));
        }
        $api_http_code = curl_getinfo($connect, CURLINFO_HTTP_CODE);

        if ($api_http_code !== null && $api_result !== null) {
            $response = ['status' => $api_http_code, 'response' => json_decode($api_result, true)];
        }

        curl_close($connect);
        return $response;
    }

    /**
     * @param array $params
     * @return string
     */
    public static function build_query(array $params): string
    {
        return http_build_query($params, '', '&');
    }

    /**
     * @param string $email
     */
    public static function set_email(string $email): void
    {
        self::$email_admin = $email;
    }

    /**
     * @param string $country_code
     */
    public static function set_locale(string $country_code): void
    {
        self::$site_locale = $country_code;
    }
}
