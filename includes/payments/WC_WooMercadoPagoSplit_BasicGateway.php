<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class WC_WooMercadoPagoSplit_BasicGateway
 */
class WC_WooMercadoPagoSplit_BasicGateway extends WC_WooMercadoPagoSplit_PaymentAbstract
{
    const ID = 'woo-mercado-pago-split-basic';

    /**
     * WC_WooMercadoPagoSplit_BasicGateway constructor.
     * @throws WC_WooMercadoPagoSplit_Exception
     */
    public function __construct()
    {
        $this->id = self::ID;

        if (!$this->validateSection()) {
            return;
        }

        $this->description = __('It offers all means of payment: credit and debit cards, cash and account money. Your customers choose whether they pay as guests or from their Mercado Pago account.', 'woocommerce-mercadopago-split');

        $this->form_fields = [];
        $this->method_title = __('Mercado Pago Checkout', 'woocommerce-mercadopago-split');
        $this->method = $this->getOption('method', 'redirect');
        $this->title = __('Pay with the payment method you prefer', 'woocommerce-mercadopago-split');
        $this->method_description = $this->getMethodDescription($this->description);
        $this->auto_return = $this->getOption('auto_return', 'yes');
        $this->success_url = $this->getOption('success_url', '');
        $this->failure_url = $this->getOption('failure_url', '');
        $this->pending_url = $this->getOption('pending_url', '');
        $this->installments = $this->getOption('installments', '24');
        $this->gateway_discount = $this->getOption('gateway_discount', 0);
        $this->clientid_old_version = $this->getClientId();
        $this->field_forms_order = $this->get_fields_sequence();
        $this->ex_payments = $this->getExPayments();

        parent::__construct();
        $this->form_fields = $this->getFormFields('Basic');
        $this->hook = new WC_WooMercadoPagoSplit_Hook_Basic($this);
        $this->notification = new WC_WooMercadoPagoSplit_Notification_IPN($this);
        $this->currency_convertion = true;
    }

    /**
     * Get form fields for the payment gateway.
     *
     * @param string $label
     * @return array
     */
    public function getFormFields($label)
    {
        if (is_admin() && $this->isManageSection()) {
            $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';
            wp_enqueue_script(
                'woocommerce-mercadopago-split-basic-config-script',
                plugins_url('../assets/js/basic_config_mercadopago' . $suffix . '.js', plugin_dir_path(__FILE__)),
                [],
                WC_WooMercadoPagoSplit_Constants::VERSION
            );
        }

        // Limit fields based on conditions
        if (empty($this->checkout_country)) {
            $this->field_forms_order = array_slice($this->field_forms_order, 0, 7);
        } elseif (empty($this->getAccessToken()) && empty($this->getPublicKey())) {
            $this->field_forms_order = array_slice($this->field_forms_order, 0, 22);
        }

        $form_fields = [
            'checkout_header' => $this->field_checkout_header(),
        ];

        if (!empty($this->checkout_country) && !empty($this->getAccessToken()) && !empty($this->getPublicKey())) {
            $form_fields = array_merge($form_fields, [
                'checkout_options_title' => $this->field_checkout_options_title(),
                'checkout _payments_title' => $this->field_checkout_payments_title(),
                'checkout_payments_subtitle' => $this->field_checkout_payments_subtitle(),
                'checkout_payments_description' => $this->field_checkout_options_description(),
                'binary_mode' => $this->field_binary_mode(),
                'installments' => $this->field_installments(),
                'checkout_payments_advanced_title' => $this->field_checkout_payments_advanced_title(),
                'method' => $this->field_method(),
                'success_url' => $this->field_success_url(),
                'failure_url' => $this->field_failure_url(),
                'pending_url' => $this->field_pending_url(),
                'auto_return' => $this->field_auto_return(),
            ]);

            foreach ($this->field_ex_payments() as $key => $value) {
                $form_fields[$key] = $value;
            }
        }

        $form_fields_abs = parent::getFormFields($label);
        if (count($form_fields_abs) == 1) {
            return $form_fields_abs;
        }

        $form_fields_merge = array_merge($form_fields_abs, $form_fields);
        return $this->sortFormFields($form_fields_merge, $this->field_forms_order);
    }

    /**
     * Get the sequence of fields for the form.
     *
     * @return array
     */
    public function get_fields_sequence()
    {
        return [
            'title',
            'description',
            'checkout_header',
            'checkout_steps',
            'checkout_country_title',
            'checkout_country',
            'checkout_btn_save',
            'checkout_credential_title',
            'checkout_credential_mod_test_title',
            'checkout_credential_mod_test_description',
            'checkout_credential_mod_prod_title',
            'checkout_credential_mod_prod_description',
            'checkout_credential_prod',
            'checkout_credential_link',
            'checkout_credential_title_test',
            'checkout_credential_description_test',
            '_mp_public_key_test',
            '_mp_access_token_test',
            'checkout_credential_title_prod',
            'checkout_credential_description_prod',
            '_mp_public_key_prod',
            '_mp_access_token_prod',
            '_mp_appid',
            '_mp_returnurl',
            'checkout_homolog_title',
            'checkout_homolog_subtitle',
            'checkout_homolog_link',
            'checkout_options_title',
            'mp_statement_descriptor',
            '_mp_category_id',
            '_mp_store_identificator',
            '_mp_integrator_id',
            'checkout_advanced_settings',
            '_mp_debug_mode',
            '_mp_custom_domain',
            'checkout_payments_title',
            'checkout_payments_subtitle',
            'checkout_payments_description',
            'enabled',
            WC_WooMercadoPagoSplit_Helpers_CurrencyConverter::CONFIG_KEY,
            'installments',
            'checkout_payments_advanced_title',
            'checkout_payments_advanced_description',
            'method',
            'auto_return',
            'success_url',
            'failure_url',
            'pending_url',
            'binary_mode',
            'gateway_discount',
            'commission',
            'checkout_support_title',
            'checkout_support_description',
            'checkout_support_description_link',
            'checkout_support_problem',
            'checkout_ready_title',
            'checkout_ready_description',
            'checkout_ready_description_link'
        ];
    }

    /**
     * Check if the payment gateway is available.
     *
     * @return bool
     */
    public function is_available()
    {
        if (parent::is_available()) {
            return true;
        }

        if (isset($this->settings['enabled']) && $this->settings['enabled'] == 'yes') {
            if ($this->mp instanceof MP) {
                $accessToken = $this->mp->get_access_token();
                if (!WC_WooMercadoPagoSplit_Credentials::validateCredentialsTest($this->mp, $accessToken) && $this->sandbox) {
                    return false;
                }

                if (!WC_WooMercadoPagoSplit_Credentials::validateCredentialsProd($this->mp, $accessToken) && !$this->sandbox) {
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    /**
     * Get client ID for version updates.
     *
     * @return string|bool
     */
    public function getClientId()
    {
        $clientId = get_option('_mp_client_id', '');
        return !empty($clientId);
    }

    /**
     * Get excluded payment methods.
     *
     * @return array
     */
    private function getExPayments()
    {
        $ex_payments = [];
        $get_ex_payment_options = $this->getOption('_all_payment_methods_v0', '');
        if (!empty($get_ex_payment options)) {
            foreach (explode(',', $get_ex_payment_options) as $get_ex_payment_option) {
                if ($this->getOption('ex_payments_' . $get_ex_payment_option, 'yes') == 'no') {
                    $ex_payments[] = $get_ex_payment_option;
                }
            }
        }
        return $ex_payments;
    }

    /**
     * Get the checkout header field.
     *
     * @return array
     */
    public function field_checkout_header()
    {
        return [
            'title' => sprintf(
                __('Mercado Pago checkout %s', 'woocommerce-mercadopago-split'),
                '<div class="row">
                    <div class="mp-col-md-12 mp_subtitle_header">' . __('Accept all method of payment and take your charges to another level', 'woocommerce-mercadopago-split') . '</div>
                    <div class="mp-col-md-12">
                        <p class="mp-text-checkout-body mp-mb-0">' . __('Turn your online store into your customers preferred payment gateway. Choose if the final payment experience will be inside or outside your store.', 'woocommerce-mercadopago-split') . '</p>
                    </div>
                </div>'
            ),
            'type' => 'title',
            'class' => 'mp_title_header'
        ];
    }

    /**
     * Get the checkout options title field.
     *
     * @return array
     */
    public function field_checkout_options_title()
    {
        return [
            'title' => __('Configure Mercado Pago for WooCommerce', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_title_bd'
        ];
    }

    /**
     * Get the checkout options description field.
     *
     * @return array
     */
    public function field_checkout_options_description()
    {
        return [
            'title' => __('Enable the experience of the Mercado Pago Checkout in your online store, select the means of payment available to your customers and<br> define the maximum fees in which they can pay you.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text'
        ];
    }

    /**
     * Get the checkout payments title field.
     *
     * @return array
     */
    public function field_checkout_payments_title()
    {
        return [
            'title' => __('Set payment preferences in your store', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_title_bd'
        ];
    }

    /**
     * Get the checkout payments advanced title field.
     *
     * @return array
     */
    public function field_checkout_payments_advanced_title()
    {
        return [
            'title' => __('Advanced settings', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd'
        ];
    }

    /**
     * Get the payment method field.
     *
     * @return array
     */
    public function field_method()
    {
        return [
            'title' => __('Payment experience', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'description' => __('Define what payment experience your customers will have, whether inside or outside your store.', 'woocommerce-mercadopago-split'),
            'default' => ($this->method == 'iframe') ? 'redirect' : $this->method,
            'options' => [
                'redirect' => __('Redirect', 'woocommerce-mercadopago-split'),
                'modal' => __('Modal', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    /**
     * Get the success URL field.
     *
     * @return array
     */
    public function field_success_url()
    {
        $success_back_url_message = !empty($this->success_url) && filter_var($this->success_url, FILTER_VALIDATE_URL) === FALSE
            ? '<img width="14" height="14" src="' . plugins_url('assets/images/warning.png', plugin_dir_path(__FILE__)) . '"> ' . __('This seems to be an invalid URL.', 'woocommerce-mercadopago-split') . ' '
            : __('Choose the URL that we will show your customers when they finish their purchase.', 'woocommerce-mercadopago-split');

        return [
            'title' => __('Success URL', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => $success_back_url_message,
            'default' => ''
        ];
    }

    /**
     * Get the failure URL field.
 ```php
    /**
     * Get the failure URL field.
     *
     * @return array
     */
    public function field_failure_url()
    {
        $fail_back_url_message = !empty($this->failure_url) && filter_var($this->failure_url, FILTER_VALIDATE_URL) === FALSE
            ? '<img width="14" height="14" src="' . plugins_url('assets/images/warning.png', plugin_dir_path(__FILE__)) . '"> ' . __('This seems to be an invalid URL.', 'woocommerce-mercadopago-split') . ' '
            : __('Choose the URL that we will show to your customers when we refuse their purchase. Make sure it includes a message appropriate to the situation and give them useful information so they can solve it.', 'woocommerce-mercadopago-split');

        return [
            'title' => __('Payment URL rejected', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => $fail_back_url_message,
            'default' => ''
        ];
    }

    /**
     * Get the pending URL field.
     *
     * @return array
     */
    public function field_pending_url()
    {
        $pending_back_url_message = !empty($this->pending_url) && filter_var($this->pending_url, FILTER_VALIDATE_URL) === FALSE
            ? '<img width="14" height="14" src="' . plugins_url('assets/images/warning.png', plugin_dir_path(__FILE__)) . '"> ' . __('This seems to be an invalid URL.', 'woocommerce-mercadopago-split') . ' '
            : __('Choose the URL that we will show to your customers when they have a payment pending approval.', 'woocommerce-mercadopago-split');

        return [
            'title' => __('Payment URL pending', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => $pending_back_url_message,
            'default' => ''
        ];
    }

    /**
     * Get excluded payment methods field.
     *
     * @return array
     */
    public function field_ex_payments()
    {
        $ex_payments = [];
        $ex_payments_sort = [];

        $all_payments = get_option('_checkout_payments_methods', '');

        if (empty($all_payments)) {
            return $ex_payments;
        }

        $get_payment_methods = get_option('_all_payment_methods_v0', '');

        if (!empty($get_payment_methods)) {
            $get_payment_methods = explode(',', $get_payment_methods);
        }

        foreach ($all_payments as $key => $value) {
            if ($value['type'] == 'atm') {
                $all_payments[$key]['type'] = 'ticket';
            }
        }

        usort($all_payments, function($a, $b) {
            return $a['type'] <=> $b['type'];
        });

        $count_payment = 0;

        foreach ($all_payments as $payment_method) {
            $element = [
                'label' => $payment_method['name'],
                'id' => 'woocommerce_mercadopago_' . $payment_method['id'],
                'default' => 'yes',
                'type' => 'checkbox',
                'class' => $payment_method['type'] . '_payment_method',
                'custom_attributes' => [
                    'data-translate' => __('Select ' . $payment_method['type'] . 's', 'woocommerce-mercadopago-split')
                ],
            ];

            if ($count_payment == 0) {
                $element['title'] = __('Payment methods', 'woocommerce-mercadopago-split');
                $element['desc_tip'] = __('Choose the available payment methods in your store.', 'woocommerce-mercadopago-split');
            }
            if ($count_payment == count($get_payment_methods) - 1) {
                $element['description'] = __('Activate the available payment methods to your clients.', 'woocommerce-mercadopago-split');
            }

            $ex_payments["ex_payments_" . $payment_method['id']] = $element;
            $ex_payments_sort[] = "ex_payments_" . $payment_method['id'];
            $count_payment++;
        }

        array_splice($this->field_forms_order, 37, 0, $ex_payments_sort);

        return $ex_payments;
    }

    /**
     * Get the auto return field.
     *
     * @return array
     */
    public function field_auto_return()
    {
        return [
            'title' => __('Return to the store', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'default' => 'yes',
            'description' => __('Do you want your customer to automatically return to the store after payment?', 'woocommerce-mercadopago-split'),
            'options' => [
                'yes' => __('Yes', 'woocommerce-mercadopago-split'),
                'no' => __('No', 'woocommerce-mercadopago-split'),
            ]
        ];
    }

    /**
     * Payment Fields
     */
    public function payment_fields()
    {
        $suffix = defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? '' : '.min';

        // Add CSS
        wp_enqueue_style(
            'woocommerce-mercadopago-split-basic-checkout-styles',
            plugins_url('../assets/css/basic_checkout_mercadopago' . $suffix . '.css', plugin_dir_path(__FILE__))
        );

        // Validate active payment methods
        $debito = 0;
        $credito = 0;
        $efectivo = 0;
        $method = $this->getOption('method', 'redirect');
        $tarjetas = get_option('_checkout_payments_methods', '');
        $installments = $this->getOption('installments');
        $str_cuotas = __('installments', 'woocommerce-mercadopago-split');
        $cho_tarjetas = [];

        if ($installments == 1) {
            $str_cuotas = __('installment', 'woocommerce-mercadopago-split');
        }

        foreach ($tarjetas as $tarjeta) {
            if ($this->get_option($tarjeta['config'], '') == 'yes') {
                $cho_tarjetas[] = $tarjeta;
                if ($tarjeta['type'] == 'credit_card') {
                    $credito++;
                } elseif ($tarjeta['type'] == 'debit_card' || $tarjeta['type'] == 'prepaid_card') {
                    $debito++;
                } else {
                    $efectivo++;
                }
            }
        }

        $parameters = [
            "debito" => $debito,
            "credito" => $credito,
            "efectivo" => $efectivo,
            "tarjetas" => $cho_tarjetas,
            "method" => $method,
            "str_cuotas" => $str_cuotas,
            "installments" => $installments,
            "plugin_version" => WC_WooMercadoPagoSplit_Constants::VERSION,
            "cho_image" => plugins_url('../assets/images/redirect_checkout.png', plugin_dir_path(__FILE__)),
            "path_to_javascript" => plugins_url('../assets/js/basic-cho' . $suffix . '.js', plugin_dir_path(__FILE__))
        ];

        wc_get_template('checkout/basic_checkout.php', $parameters, 'woo/mercado/pago/module/', WC_WooMercadoPagoSplit_Module::get_templates_path());
    }

    /**
     * Process the payment.
     *
     * @param int $order_id
     * @return array
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $amount = $this->get_order_total();

        if (method_exists($order, 'update_meta_data')) {
            $order->update_meta_data('_used_gateway', get_class($this));

            if (!empty($this->gateway_discount)) {
                $discount = $amount * ($this->gateway_discount / 100);
                $order->update_meta_data('Mercado Pago: discount', __('discount of', 'woocommerce-mercadopago-split') . ' ' . $this->gateway_discount . '% / ' . __('discount of', 'woocommerce-mercadopago-split') . ' = ' . $discount);
            }

            if (!empty($this->commission)) {
                $comission = $amount * ($this->commission / 100);
                $order->update_meta_data('Mercado Pago: comission', __('fee of', 'woocommerce-mercadopago-split') . ' ' . $this->commission . '% / ' . __('fee of', 'woocommerce-mercadopago-split') . ' = ' . $comission);
            }
            $order->save();
        } else {
            update_post_meta($order_id, '_used_gateway', get_class($this));

            if (!empty($this->gateway_discount)) {
                $discount = $amount * ($this->gateway_discount / 100);
                update_post_meta($order_id, 'Mercado Pago: discount', __('discount of', 'woocommerce-mercadopago-split') . ' ' . $this->gateway_discount . '% / ' . __('discount of', 'woocommerce-mercadopago-split') . ' = ' . $discount);
 }

            if (!empty($this->commission)) {
                $comission = $amount * ($this->commission / 100);
                update_post_meta($order_id, 'Mercado Pago: comission', __('fee of', 'woocommerce-mercadopago-split') . ' ' . $this->commission . '% / ' . __('fee of', 'woocommerce-mercadopago-split') . ' = ' . $comission);
            }
        }

        $preference = $this->create_preference($order);

        if ($preference) {
            return [
                'result' => 'success',
                'redirect' => $preference
            ];
        } else {
            return [
                'result' => 'fail',
                'redirect' => ''
            ];
        }
    }

    /**
     * Create a preference for the payment.
     *
     * @param $order
     * @return bool
     */
    public function create_preference($order)
    {
        $preferencesBasic = new WC_WooMercadoPagoSplit_PreferenceBasic($this, $order);
        $preferences = $preferencesBasic->get_preference();

        try {
            $checkout_info = $this->mp->create_preference(json_encode($preferences));

            if ($checkout_info['status'] < 200 || $checkout_info['status'] >= 300) {
                $this->log->write_log(__FUNCTION__, 'mercado pago gave error, payment creation failed with error: ' . $checkout_info['response']['message']);
                return false;
            } elseif (is_wp_error($checkout_info)) {
                $this->log->write_log(__FUNCTION__, 'wordpress gave error, payment creation failed with error: ' . $checkout_info['response']['message']);
                return false;
            } else {
                $this->log->write_log(__FUNCTION__, 'payment link generated with success from mercado pago, with structure as follow: ' . json_encode($checkout_info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                if ($this->sandbox) {
                    return $checkout_info['response']['sandbox_init_point'];
                }
                return $checkout_info['response']['init_point'];
            }
        } catch (WC_WooMercadoPagoSplit_Exception $ex) {
            $this->log->write_log(__FUNCTION__, 'payment creation failed with exception: ' . json_encode($ex, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return false;
        }
    }

    /**
     * Get the ID of the payment gateway.
     *
     * @return string
     */
    public static function getId()
    {
        return self::ID;
    }
}
