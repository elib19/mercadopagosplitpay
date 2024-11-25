<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Class WC_WooMercadoPagoSplit_Constants
 *
 * Esta classe contém constantes utilizadas pelo plugin WooMercadoPagoSplit.
 */
class WC_WooMercadoPagoSplit_Constants
{
    const PRODUCT_ID_DESKTOP = 'BT7OF5FEOO6G01NJK3QG'; // ID do produto para desktop
    const PRODUCT_ID_MOBILE = 'BT7OFH09QS3001K5A0H0'; // ID do produto para mobile
    const PLATAFORM_ID = 'bo2hnr2ic4p001kbgpt0'; // ID da plataforma
    const VERSION = '4.7.0'; // Versão atual do plugin
    const MIN_PHP = '7.0'; // Mínimo PHP suportado
    const API_MP_BASE_URL = 'https://api.mercadopago.com'; // URL base da API do Mercado Pago
}
