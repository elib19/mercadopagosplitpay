<?php

if (!defined('ABSPATH')) {
    exit; // Evitar acesso direto
}

class MP_Split_Helper
{
    public static function get_mp_access_token()
    {
        return get_option('mp_access_token', '');
    }

    public static function get_application_fee()
    {
        return (int)get_option('mp_application_fee', 10); // 10% por padrão
    }

    public static function call_mp_api($url, $method = 'GET', $data = array())
    {
        $access_token = self::get_mp_access_token();
        $headers = array(
            "Authorization: Bearer $access_token",
            "Content-Type: application/json",
            "Accept: application/json"
        );

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if (!empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }

        $response = curl_exec($ch);
        curl_close($ch);

        return json_decode($response, true);
    }
}
