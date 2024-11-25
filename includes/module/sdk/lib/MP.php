<?php

if (!defined('ABSPATH')) {
    exit;
}

$GLOBALS['LIB_LOCATION'] = dirname(__FILE__);

/**
 * Class MPP
 */
class MPP
{
    private string $client_id;
    private string $client_secret;
    private ?string $ll_access_token = null;
    private bool $sandbox = false;
    private ?string $accessTokenByClient = null;
    private ?string $paymentClass = null;

    /**
     * MP constructor.
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public function __construct(...$args)
    {
        $includes_path = dirname(__FILE__);
        require_once($includes_path . '/RestClient/AbstractRestClientSplit.php');
        require_once($includes_path . '/RestClient/MeliRestClientSplit.php');
        require_once($includes_path . '/RestClient/MpRestClientSplit.php');

        if (count($args) > 2 || count($args) < 1) {
            throw new WC_WooMercadoPagoSplit_Exception('Invalid arguments. Use CLIENT_ID and CLIENT SECRET, or ACCESS_TOKEN');
        }

        if (count($args) === 1) {
            $this->ll_access_token = $args[0];
        }

        if (count($args) === 2) {
            [$this->client_id, $this->client_secret] = $args;
        }
    }

    public function set_email(string $email): void
    {
        MPRestClientSplit::set_email($email);
        MeliRestClientSplit::set_email($email);
    }

    public function set_locale(string $country_code): void
    {
        MPRestClientSplit::set_locale($country_code);
        MeliRestClientSplit::set_locale($country_code);
    }

    public function sandbox_mode(?bool $enable = null): bool
    {
        if ($enable !== null) {
            $this->sandbox = $enable;
        }
        return $this->sandbox;
    }

    public function get_access_token(): ?string
    {
        if ($this->ll_access_token) {
            return $this->ll_access_token;
        }

        if (!empty($this->accessTokenByClient)) {
            return $this->accessTokenByClient;
        }

        $app_client_values = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'grant_type' => 'client_credentials'
        ];

        $access_data = MPRestClientSplit::post([
            'uri' => '/oauth/token',
            'data' => $app_client_values,
            'headers' => ['content-type' => 'application/x-www-form-urlencoded']
        ], WC_WooMercadoPagoSplit_Constants::VERSION);

        if ($access_data['status'] !== 200) {
            return null;
        }

        $response = $access_data['response'];
        $this->accessTokenByClient = $response['access_token'];

        return $this->accessTokenByClient;
    }

    public function search_paymentV1(string $id): ?array
    {
        $request = [
            'uri' => '/v1/payments/' . $id,
            'params' => ['access_token' => $this->get_access_token()]
        ];

        return MPRestClientSplit::get($request, WC_WooMercadoPagoSplit_Constants::VERSION);
    }

    //=== CUSTOMER CARDS FUNCTIONS ===

    public function get_or_create_customer(string $payer_email): array
    {
        $customer = $this->search_customer($payer_email);

        if ($customer['status'] === 200 && $customer['response']['paging']['total'] > 0) {
            return $customer['response']['results'][0];
        } else {
            $resp = $this->create_customer($payer_email);
            return $resp['response'];
        }
    }

    public function create_customer(string $email): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/customers',
            'data' => ['email' => $email]
        ];

        return MPRestClientSplit::post($request);
    }

    public function search_customer(string $email): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/customers/search',
            'params' => ['email' => $email]
        ];

        return MPRestClientSplit::get($request);
    }

    public function create_card_in_customer(
        string $customer_id,
        string $token,
        ?string $payment_method_id = null,
        ?string $issuer_id = null
    ): ?array {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/customers/' . $customer_id . '/cards',
            'data' => [
                'token' => $token,
                'issuer_id' => $issuer_id,
                'payment_method_id' => $payment_method_id
            ]
        ];

        return MPRestClientSplit::post($request);
    }

    public function get_all_customer_cards(string $customer_id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/customers/' . $customer_id . '/cards',
        ];

        return MPRestClientSplit::get($request);
    }

    //=== COUPON AND DISCOUNTS FUNCTIONS ===
    public function check_discount_campaigns(float $transaction_amount, string $payer_email, string $coupon_code): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/discount_campaigns',
            'params' => [
                'transaction_amount' => $transaction_amount,
                'payer_email' => $payer_email,
                'coupon_code' => $coupon_code
            ]
        ];
        return MPRestClientSplit::get($request);
    }

    //=== CHECKOUT AUXILIARY FUNCTIONS ===

    public function get_authorized_payment(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/authorized_payments/' . $id,
        ];

        return MPRestClientSplit::get($request);
    }

    public function create_preference(array $preference): ?array
    {
        $request = [
            'uri' => '/checkout/preferences',
            'headers' => [
                'user-agent' => 'platform:desktop,type:woocommerce,so:' . WC_WooMercadoPagoSplit_Constants::VERSION,
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'data' => $preference
        ];

        return MPRestClientSplit::post($request);
    }

    public function update_preference(string $id, array $preference): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/checkout/preferences/' . $id,
            'data' => $preference
        ];

        return MPRestClientSplit::put($request);
    }

    public function get_preference(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/checkout/preferences/' . $id,
        ];

        return MPRestClientSplit::get($request);
    }

    public function create_payment(array $preference): ?array
    {
        $request = [
            'uri' => '/v1/payments',
            'headers' => [
                'X-Tracking-Id' => 'platform:v1-whitelabel,type:woocommerce,so:' . WC_WooMercadoPagoSplit_Constants::VERSION,
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'data' => $preference
        ];

        return MPRestClientSplit::post($request, WC_WooMercadoPagoSplit_Constants::VERSION);
    }

    public function create_preapproval_payment(array $preapproval_payment): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/preapproval',
            'data' => $preapproval_payment
        ];

        return MPRestClientSplit::post($request);
    }

    public function get_preapproval_payment(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/preapproval/' . $id
        ];

        return MPRestClient Split::get($request);
    }

    public function update_preapproval_payment(string $id, array $preapproval_payment): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/preapproval/' . $id,
            'data' => $preapproval_payment
        ];

        return MPRestClientSplit::put($request);
    }

    public function cancel_preapproval_payment(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/preapproval/' . $id,
            'data' => ['status' => 'cancelled']
        ];

        return MPRestClientSplit::put($request);
    }

    //=== REFUND AND CANCELING FLOW FUNCTIONS ===

    public function refund_payment(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/payments/' . $id . '/refunds'
        ];

        return MPRestClientSplit::post($request);
    }

    public function partial_refund_payment(string $id, float $amount, string $reason, string $external_reference): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/payments/' . $id . '/refunds',
            'data' => [
                'amount' => $amount,
                'metadata' => [
                    'metadata' => $reason,
                    'external_reference' => $external_reference
                ]
            ]
        ];

        return MPRestClientSplit::post($request);
    }

    public function cancel_payment(string $id): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/payments/' . $id,
            'data' => '{"status":"cancelled"}'
        ];

        return MPRestClientSplit::put($request);
    }

    public function get_payment_methods(): ?array
    {
        $request = [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->get_access_token()
            ],
            'uri' => '/v1/payment_methods',
        ];

        return MPRestClientSplit::get($request);
    }

    public function getCredentialsWrapper(?string $access_token = null, ?string $public_key = null): ?array
    {
        $request = [
            'uri' => '/plugins-credentials-wrapper/credentials',
        ];

        if (!empty($access_token) && empty($public_key)) {
            $request['headers'] = ['Authorization' => 'Bearer ' . $access_token];
        }

        if (empty($access_token) && !empty($public_key)) {
            $request['params'] = ['public_key' => $public_key];
        }

        $response = MPRestClientSplit::get($request);

        if ($response['status'] > 202) {
            $log = WC_WooMercadoPagoSplit_Log::init_mercado_pago_log('getCredentialsWrapper');
            $log->write_log('API GET Credentials Wrapper error:', $response['response']['message']);
            return null;
        }

        return $response['response'];
    }

    //=== GENERIC RESOURCE CALL METHODS ===

    public function get($request, array $headers = [], bool $authenticate = true): ?array
    {
        if (is_string($request)) {
            $request = [
                'headers' => $headers,
                'uri' => $request,
                'authenticate' => $authenticate
            ];
        }

        if (!isset($request['authenticate']) || $request['authenticate'] !== false) {
            $access_token = $this->get_access_token();
            if (!empty($access_token)) {
                $request['headers']['Authorization'] = 'Bearer ' . $access_token;
            }
        }

        return MPRestClientSplit::get($request);
    }

    public function post($request, $data = null, $params = null): ?array
    {
        if (is_string($request)) {
            $request = [
                'headers' => ['Authorization' => 'Bearer ' . $this->get_access_token()],
                'uri' => $request,
                'data' => $data,
                'params' => $params
            ];
        }

 $request['params'] = isset($request['params']) && is_array($request['params']) ? $request['params'] : [];

        return MPRestClientSplit::post($request);
    }

    public function put($request, $data = null, $params = null): ?array
    {
        if (is_string($request)) {
            $request = [
                'headers' => ['Authorization' => 'Bearer ' . $this->get_access_token()],
                'uri' => $request,
                'data' => $data,
                'params' => $params
            ];
        }

        $request['params'] = isset($request['params']) && is_array($request['params']) ? $request['params'] : [];

        return MPRestClientSplit::put($request);
    }

    public function delete($request, $params = null): ?array
    {
        if (is_string($request)) {
            $request = [
                'headers' => ['Authorization' => 'Bearer ' . $this->get_access_token()],
                'uri' => $request,
                'params' => $params
            ];
        }

        $request['params'] = isset($request['params']) && is_array($request['params']) ? $request['params'] : [];

        return MPRestClientSplit::delete($request);
    }

    public function setPaymentClass($payment = null): void
    {
        if (!empty($payment)) {
            $this->paymentClass = get_class($payment);
        }
    }

    public function getPaymentClass(): ?string
    {
        return $this->paymentClass;
    }
}
