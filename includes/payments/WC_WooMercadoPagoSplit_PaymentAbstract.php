<?php

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WC_WooMercadoPagoSplit_PaymentAbstract
 */
class WC_WooMercadoPagoSplit_PaymentAbstract extends WC_Payment_Gateway
{
    // Configurações comuns
    const COMMON_CONFIGS = [
        '_mp_public_key_test',
        '_mp_access_token_test',
        '_mp_public_key_prod',
        '_mp_access_token_prod',
        'checkout_country',
        'mp_statement_descriptor',
        '_mp_category_id',
        '_mp_store_identificator',
        '_mp_integrator_id',
        '_mp_custom_domain',
        'installments',
        'auto_return'
    ];

    const CREDENTIAL_FIELDS = [
        '_mp_public_key_test',
        '_mp_access_token_test',
        '_mp_public_key_prod',
        '_mp_access_token_prod'
    ];

    const ALLOWED_CLASSES = [
        'wc_woomercadopago_basicgateway',
        'wc_woomercadopago_customgateway',
        'wc_woomercadopago_ticketgateway'
    ];

    public function __construct()
    {
        $this->id = 'mercadopago_split';
        $this->method_title = __('Mercado Pago Split', 'woocommerce-mercadopago-split');
        $this->method_description = __('Accept payments through Mercado Pago Split.', 'woocommerce-mercadopago-split');

        // Configurações
        $this->init_settings();
        $this->mp_public_key_test = $this->get_option('_mp_public_key_test');
        $this->mp_access_token_test = $this->get_option('_mp_access_token_test');
        $this->mp_public_key_prod = $this->get_option('_mp_public_key_prod');
        $this->mp_access_token_prod = $this->get_option('_mp_access_token_prod');
        $this->checkout_country = $this->get_option('checkout_country', '');
        $this->wc_country = get_option('woocommerce_default_country', '');
        $this->mp_category_id = $this->get_option('_mp_category_id', 0);
        $this->store_identificator = $this->get_option('_mp_store_identificator', 'WC-');
        $this->integrator_id = $this->get_option('_mp_integrator_id', '');
        $this->debug_mode = $this->get_option('_mp_debug_mode', 'no');
        $this->custom_domain = $this->get_option('_mp_custom_domain', '');
        $this->binary_mode = $this->get_option('binary_mode', 'no');
        $this->gateway_discount = $this->get_option('gateway_discount', 0);
        $this->commission = $this->get_option('commission', 0);
        $this->sandbox = $this->isTestUser ();
        $this->supports = ['products', 'refunds'];
        $this->icon = $this->getMpIcon();
        $this->log = new WC_WooMercadoPagoSplit_Log($this);
        $this->mp = $this->getMpInstance();
        $this->homolog_validate = $this->getHomologValidate();
        $this->application_id = $this->getApplicationId($this->mp_access_token_prod);
        $this->logged_user_email = (wp_get_current_user()->ID != 0) ? wp_get_current_user()->user_email : null;

        // URL de ação de desconto
        $this->discount_action_url = get_site_url() . '/index.php/woocommerce-mercadopago-split/?wc-api=' . get_class($this);
    }

    // Métodos de configuração e validação

    public function getHomologValidate()
    {
        $homolog_validate = (int)get_option('homolog_validate', 0);
        if ($this->isProductionMode() && !empty($this->mp_access_token_prod) && $homolog_validate == 0) {
            if ($this->mp instanceof MP) {
                $homolog_validate = $this->mp->getCredentialsWrapper($this->mp_access_token_prod);
                $homolog_validate = isset($homolog_validate['homologated']) && $homolog_validate['homologated'] ? 1 : 0;
                update_option('homolog_validate', $homolog_validate, true);
                return $homolog_validate;
            }
            return 0;
        }
        return 1;
    }

    public function getAccessToken()
    {
        return $this->isProduction Mode() ? $this->mp_access_token_prod : $this->mp_access_token_test;
    }

    public function getPublicKey()
    {
        return $this->isProductionMode() ? $this->mp_public_key_prod : $this->mp_public_key_test;
    }

    public function getOption($key, $default = '')
    {
        return in_array($key, self::COMMON_CONFIGS) ? get_option($key, $default) : parent::get_option($key, $default);
    }

    public function normalizeCommonAdminFields()
    {
        if (empty($this->mp_access_token_test) && empty($this->mp_access_token_prod) && isset($this->settings['enabled']) && $this->settings['enabled'] == 'yes') {
            $this->settings['enabled'] = 'no';
            $this->disableAllPaymentsMethodsMP();
        }

        $changed = false;
        foreach (self::COMMON_CONFIGS as $config) {
            $commonOption = get_option($config);
            if (isset($this->settings[$config]) && $this->settings[$config] != $commonOption) {
                $changed = true;
                $this->settings[$config] = $commonOption;
            }
        }

        if ($changed) {
            update_option($this->get_option_key(), apply_filters('woocommerce_settings_api_sanitized_fields_' . $this->id, $this->settings));
        }
    }

    public function isManageSection()
    {
        return isset($_GET['section']) && $this->id === $_GET['section'] || in_array($_GET['section'], self::ALLOWED_CLASSES);
    }

    public function getMpIcon()
    {
        return apply_filters('woocommerce_mercadopago_icon', plugins_url('../assets/images/mercadopago.png', plugin_dir_path(__FILE__)));
    }

    public function getMethodDescription($description)
    {
        return '<div class="mp-header-logo">
            <div class="mp-left-header">
                <img src="' . plugins_url('../assets/images/mplogo.png', plugin_dir_path(__FILE__)) . '">
            </div>
            <div>' . $description . '</div>
        </div>';
    }

    public function update_option($key, $value = '')
    {
        if ($key == 'enabled' && $value == 'yes' && empty($this->mp->get_access_token())) {
            $message = __('Configure your credentials to enable Mercado Pago payment methods.', 'woocommerce-mercadopago-split');
            $this->log->write_log(__FUNCTION__, $message);
            echo json_encode(['success' => false, 'data' => $message]);
            die();
        }
        return parent::update_option($key, $value);
    }

    public function noticeHomologValidate()
    {
        $type = 'notice-warning';
        $message = sprintf(__('%s, it only takes a few minutes', 'woocommerce-mercadopago-split'), '<a class="mp-mouse_pointer" href="https://www.mercadopago.com/' . $this->checkout_country . '/account/credentials/appliance?application_id=' . $this->application_id . '" target="_blank"><b><u>' . __('Approve your account', 'woocommerce-mercadopago-split') . '</u></b></a>');
        echo WC_WooMercadoPagoSplit_Notices::getAlertFrame($message, $type);
    }

    public function getFormFields($label)
    {
        $this->init_form_fields();
        $this->init_settings();
        $form_fields = [
            'title' => $this->field_title(),
            'description' => $this->field_description(),
            'checkout_steps' => $this->field_checkout_steps(),
            'checkout_country_title' => $this->field_checkout_country_title(),
            'checkout_country' => $this->field_checkout_country($this->wc_country, $this->checkout_country),
            'checkout_btn_save' => $this->field_checkout_btn_save(),
        ];

        if (!empty($this->checkout_country)) {
            $form_fields = array_merge($form_fields, $this->getCredentialFields());
        }

        if (is_admin()) {
            $this->normalizeCommonAdminFields();
        }

        return $form_fields;
    }

    private function getCredentialFields()
    {
        return [
            'checkout_credential_title' => $this->field_checkout_credential_title(),
            'checkout_credential_mod_test_title' => $this->field_checkout_credential_mod_test_title(),
            'checkout_credential_mod_test_description' => $this->field_checkout_credential_mod_test_description(),
            'checkout_credential_mod_prod_title' => $this->field_checkout_credential_mod_prod_title(),
 'checkout_credential_mod_prod_description' => $this->field_checkout_credential_mod_prod_description(),
            'checkout_credential_prod' => $this->field_checkout_credential_production(),
            'checkout_credential_link' => $this->field_checkout_credential_link($this->checkout_country),
            'checkout_credential_title_test' => $this->field_checkout_credential_title_test(),
            'checkout_credential_description_test' => $this->field_checkout_credential_description_test(),
            '_mp_public_key_test' => $this->field_checkout_credential_publickey_test(),
            '_mp_access_token_test' => $this->field_checkout_credential_accesstoken_test(),
            'checkout_credential_title_prod' => $this->field_checkout_credential_title_prod(),
            'checkout_credential_description_prod' => $this->field_checkout_credential_description_prod(),
            '_mp_public_key_prod' => $this->field_checkout_credential_publickey_prod(),
            '_mp_access_token_prod' => $this->field_checkout_credential_accesstoken_prod(),
            '_mp_appid' => $this->field_checkout_credential_appid(),
            '_mp_returnurl' => $this->field_checkout_credential_returnurl(),
            '_mp_category_id' => $this->field_category_store(),
        ];
    }

    public function field_title()
    {
        return [
            'title' => __('Title', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'class' => 'hidden-field-mp-title mp-hidden-field',
            'default' => $this->title
        ];
    }

    public function field_description()
    {
        return [
            'title' => __('Description', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'class' => 'hidden-field-mp-desc mp-hidden-field',
            'default' => $this->method_description
        ];
    }

    public function field_checkout_steps()
    {
        return [
            'title' => sprintf(
                '<div class="mp-row">
                  <h4 class="mp-title-checkout-body mp-pb-20"><b>' . __('Follow these steps to activate Mercado Pago in your store:', 'woocommerce-mercadopago-split') . '</b></h4>
                  <div class="mp-col-md-2 mp-text-center mp-pb-10">
                    <p class="mp-number-checkout-body">1</p>
                    <p class="mp-text-steps mp-text-center mp-px-20">' . __('<b>Upload your credentials</b> depending on the country in which you are registered.', 'woocommerce-mercadopago-split') . '</p>
                  </div>
                  <div class="mp-col-md-2 mp-text-center mp-pb-10">
                    <p class="mp-number-checkout-body">2</p>
                    <p class="mp-text-steps mp-text-center mp-px-20">' . __('<b>Approve your account</b> to be able to charge.', 'woocommerce-mercadopago-split') . '</p>
                  </div>
                  <div class="mp-col-md-2 mp-text-center mp-pb-10">
                    <p class="mp-number-checkout-body">3</p>
                    <p class="mp-text-steps mp-text-center mp-px-20">' . __('<b>Add the basic information of your business</b> in the plugin configuration.', 'woocommerce-mercadopago-split') . '</p>
                  </div>
                  <div class="mp-col-md-2 mp-text-center mp-pb-10">
                    <p class="mp-number-checkout-body">4</p>
                    <p class="mp-text-steps mp-text-center mp-px-20">' . __('<b>Configure the payment preferences</b> for your customers.', 'woocommerce-mercadopago-split') . '</p>
                  </div>
                  <div class="mp-col-md-2 mp-text-center mp-pb-10">
                    <p class="mp-number-checkout-body">5</p>
                    <p class="mp-text-steps mp-text-center mp-px-20">' . __('<b>Go to advanced settings</b> only when you want to change the presets.', 'woocommerce-mercadopago-split') . '</p>
                  </div>
                </div>'
            ),
            'type' => 'title',
            'class' => 'mp_title_checkout'
        ];
    }

    public function field_checkout_country_title()
    {
        return [
            'title' => __('In which country does your Mercado Pago account operate?', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class => 'mp_subtitle_bd'
        ];
    }

    public function field_checkout_country($wc_country, $checkout_country)
    {
        $country = [
            'AR' => 'mla', // Argentinian
            'BR' => 'mlb', // Brazil
            'CL' => 'mlc', // Chile
            'CO' => 'mco', // Colombia
            'MX' => 'mlm', // Mexico
            'PE' => 'mpe', // Peru
            'UY' => 'mlu', // Uruguay
        ];

        $country_default = '';
        if (!empty($wc_country) && empty($checkout_country)) {
            $country_default = strlen($wc_country) > 2 ? substr($wc_country, 0, 2) : $wc_country;
            $country_default = array_key_exists($country_default, $country) ? $country[$country_default] : 'mla';
        }

        return [
            'title' => __('Select your country', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'description' => __('Select the country in which you operate with Mercado Pago', 'woocommerce-mercadopago-split'),
            'default' => empty($checkout_country) ? $country_default : $checkout_country,
            'options' => [
                'mla' => __('Argentina', 'woocommerce-mercadopago-split'),
                'mlb' => __('Brazil', 'woocommerce-mercadopago-split'),
                'mlc' => __('Chile', 'woocommerce-mercadopago-split'),
                'mco' => __('Colombia', 'woocommerce-mercadopago-split'),
                'mlm' => __('Mexico', 'woocommerce-mercadopago-split'),
                'mpe' => __('Peru', 'woocommerce-mercadopago-split'),
                'mlu' => __('Uruguay', 'woocommerce-mercadopago-split'),
            ]
        ];
    }

    public function field_checkout_btn_save()
    {
        $btn_save = '<button name="save" class="button button-primary" type="submit" value="Save changes">' . __('Save Changes', 'woocommerce-mercadopago-split') . '</button>';

        $wc = WC_WooMercadoPagoSplit_Module::woocommerce_instance();
        if (version_compare($wc->version, '4.4') >= 0) {
            $btn_save = '<div name="save" class="button-primary mp-save-button" type="submit" value="Save changes">' . __('Save Changes', 'woocommerce-mercadopago-split') . '</div>';
        }

        return [
            'title' => sprintf('%s', $btn_save),
            'type' => 'title',
            'class' => ''
        ];
    }

    public function field_enabled($label)
    {
        return [
            'title' => __('Activate checkout', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'default' => 'no',
            'description' => __('Activate the Mercado Pago experience at the checkout of your store.', 'woocommerce-mercadopago-split'),
            'options' => [
                'no' => __('No', 'woocommerce-mercadopago-split'),
                'yes' => __('Yes', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function field_checkout_credential_title()
    {
        return [
            'title' => __('Enter your credentials and choose how to operate', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd'
        ];
    }

    public function field_checkout_credential_mod_test_title()
    {
        return [
            'title' => __('Test Mode', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_mt'
        ];
    }

    public function field_checkout_credential_mod_test_description()
    {
        return [
            'title' => __('By default, we activate the Sandbox test environment for you to test before you start selling.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text mp-mt--12'
        ];
    }

    public function field_checkout_credential_mod_prod_title()
    {
        return [
            'title' => __('Production Mode', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_mt'
        ];
    }

    public function field_checkout_credential_mod_prod_description()
    {
        return [
            'title' => __('When you see that everything is going well, deactivate Sandbox, turn on Production and make way for your online sales.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text mp-mt--12'
        ];
    }

    public function field_checkout_credential_production()
    {
        $production_mode = $this->isProductionMode() ? 'yes' : 'no';
        return [
            'title' => __('Production', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'description' => __('Choose “Yes” only when you’re ready to sell. Switch to “No” to activate Testing mode.', 'woocommerce-mercadopago-split'),
            'default' => $this->id == 'woo-mercado-pago-split-basic' && $this->clientid_old_version ? 'yes' : $production_mode,
            'options' => [
                'no' => __('No', 'woocommerce-mercadopago-split'),
                'yes' => __('Yes', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function field_checkout_credential_link($country)
    {
        return [
            'title' => sprintf(
                '%s',
                '<table class="form-table" id="mp_table_7">
                    <tbody>
                        <tr valign="top">
                            <th scope="row" id="mp_field_text">
                                <label>' . __('Load credentials', 'woocommerce-mercadopago-split') . '</label>
                            </th>
                            <td class="forminp">
                                <fieldset>
                                    <a class="mp_general_links" href="https://www.mercadopago.com/' . $country . '/account/credentials" target="_blank">' . __('Search my credentials', 'woocommerce-mercadopago-split') . '</a>
                                    <p class="description mp-fw-400 mp-mb-0"></p>
                                </fieldset>
                            </td>
                        </tr>
                    </tbody>
                </table>'
            ),
            'type' => 'title',
        ];
    }

    public function field_checkout_credential_title_test()
    {
        return [
            'title' => __('Test credentials', 'woocommerce-mercadopago-split'),
            'type' => 'title',
        ];
    }

    public function field_checkout_credential_description_test()
    {
        return [
            'title' => __('With these keys you can do the tests you want.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text mp-mt--12'
        ];
    }

    public function field_checkout_credential_publickey_test()
    {
        return [
            'title' => __('Public key', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => '',
            'default' => $this->getOption('_mp_public_key_test', ''),
            'placeholder' => 'TEST-00000000-0000-0000-0000-000000000000'
        ];
    }

    public function field_checkout_credential_accesstoken_test()
    {
        return [
            'title' => __('Access token', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => '',
            'default' => $this->getOption('_mp_access_token_test', ''),
            'placeholder' => 'TEST-000000000000000000000000000000000-000000-00000000000000000000000000000000-000000000'
        ];
    }

    public function field_checkout_credential_title_prod()
    {
        return [
            'title' => __('Production credentials', 'woocommerce-mercadopago-split'),
            'type' => 'title',
        ];
    }

    public function field_checkout_credential_description_prod()
    {
        return [
            'title' => __('With these keys you can receive real payments from your customers.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text mp-mt--12'
        ];
    }

    public function field_checkout_credential_publickey_prod()
    {
        return [
            'title' => __('Public key', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => '',
            'default' => $this->getOption('_mp_public_key_prod', ''),
            'placeholder' => 'APP-USR-00000000-0000-0000-0000-000000000000'
        ];
    }

    public function field_checkout_credential_accesstoken_prod {
            return [
                'title' => __('Access token', 'woocommerce-mercadopago-split'),
                'type' => 'text',
                'description' => '',
                'default' => $this->getOption('_mp_access_token_prod', ''),
                'placeholder' => 'APP-USR-000000000000000000000000000000000-000000-00000000000000000000000000000000-000000000'
            ];
        }

    public function field_checkout_credential_appid()
    {
        return [
            'title' => __('App ID', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => '',
            'default' => $this->getOption('_mp_appid', ''),
            'placeholder' => '000000000'
        ];
    }

    public function field_checkout_credential_returnurl()
    {
        return [
            'title' => __('Return URL (the page where the account linking shortcode is included. Enter exactly as when creating the app in Mercado Pago)', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => '',
            'default' => $this->getOption('_mp_returnurl', ''),
            'placeholder' => 'https://'
        ];
    }

    public function field_checkout_homolog_title()
    {
        return [
            'title' => __('Approve your account, it will only take a few minutes', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd'
        ];
    }

    public function field_checkout_homolog_subtitle()
    {
        return [
            'title' => __('Complete this process to secure your customers data and comply with the regulations<br> and legal provisions of each country.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_text mp-mt--12'
        ];
    }

    public function field_checkout_homolog_link($country_link, $application_id)
    {
        return [
            'title' => sprintf(
                __('%s', 'woocommerce-mercadopago-split'),
                '<a href="https://www.mercadopago.com/' . $country_link . '/account/credentials/appliance?application_id=' . $application_id . '" target="_blank">' . __('Homologate account in Mercado Pago', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'type' => 'title',
            'class' => 'mp_tienda_link'
        ];
    }

    public function field_mp_statement_descriptor()
    {
        return [
            'title' => __('Store name', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => __('This name will appear on your customers invoice.', 'woocommerce-mercadopago-split'),
            'default' => $this->getOption('mp_statement_descriptor', __('Mercado Pago', 'woocommerce-mercadopago-split')),
        ];
    }

    public function field_category_store()
    {
        $category_store = WC_WooMercadoPagoSplit_Module::$categories;
        $option_category = [];
        foreach ($category_store['store_categories_id'] as $category_id) {
            $option_category[$category_id] = __($category_id, 'woocommerce-mercadopago-split');
        }
        return [
            'title' => __('Store Category', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'description' => __('What category do your products belong to? Choose the one that best characterizes them (choose "other" if your product is too specific).', 'woocommerce-mercadopago-split'),
            'default' => $this->getOption('_mp_category_id', __('Categories', 'woocommerce-mercadopago-split')),
            'options' => $option_category
        ];
    }

    public function field_mp_store_identificator()
    {
        return [
            'title' => __('Store ID', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => __('Use a number or prefix to identify orders and payments from this store.', 'woocommerce-mercadopago-split'),
            'default' => $this->getOption('_mp_store_identificator', 'WC-'),
        ];
    }

    public function field_mp_integrator_id()
    {
        $links_mp = WC_WooMercadoPagoSplit_Module::define_link_country();
        return [
            'title' => __('Integrator ID', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => sprintf(
                __('Do not forget to enter your integrator_id as a certified Mercado Pago Partner. If you don`t have it, you can %s', 'woocommerce-mercadopago-split'),
                '<a target="_blank" href="https://www.mercadopago.' . $links_mp['sufix_url'] . 'developers/' . $links_mp['translate'] . '/guides/plugins/woocommerce/preferences/#bookmark_informações_do_negócio">' . __('request it now.', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'default' => $this->getOption('_mp_integrator_id', '')
        ];
    }

    public function field_checkout_advanced_settings()
    {
        return [
            'title' => __('Advanced adjustment', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd'
        ];
    }

    public function field_debug_mode()
    {
        return [
            'title' => __('Debug and Log mode', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'default' => 'no',
            'description' => __('Record your store actions in our changes file to have more support information.', 'woocommerce-mercadopago-split'),
            'desc_tip' => __('We debug the information in our change file.', 'woocommerce-mercadopago-split'),
            'options' => [
                'no' => __('No', 'woocommerce-mercadopago-split'),
                'yes' => __('Yes', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function field_checkout_payments_subtitle()
    {
        return [
            'title' => __('Basic Configuration', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle mp-mt-5 mp-mb-0'
        ];
    }

    public function field_installments()
    {
        return [
            'title' => __('Max of installments', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'description' => __('What is the maximum quota with which a customer can buy?', 'woocommerce-mercadopago-split'),
            'default' => '24',
            'options' => [
                '1' => __('1x installment', 'woocommerce-mercadopago-split'),
                '2' => __('2x installments', 'woocommerce-mercadopago-split'),
                '3' => __('3x installments', 'woocommerce-mercadopago-split'),
                '4' => __('4x installments', 'woocommerce-mercadopago-split'),
                '5' => __('5x installments', 'woocommerce-mercadopago-split'),
                '6' => __('6x installments', 'woocommerce-mercadopago-split'),
                '10' => __('10x installments', 'woocommerce-mercadopago-split'),
                '12' => __('12x installments', 'woocommerce-mercadopago-split'),
                '15' => __('15x installments', 'woocommerce-mercadopago-split'),
                '18' => __('18x installments', 'woocommerce-mercadopago-split'),
                '24' => __('24x installments', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function getCountryLinkGuide($checkout)
    {
        $countryLink = [
            'mla' => 'https://www.mercadopago.com.ar/developers/es/', // Argentinian
            'mlb' => 'https://www.mercadopago.com.br/developers/pt/', // Brazil
            'mlc' => 'https://www.mercadopago.cl/developers/es/', // Chile
            'mco' => 'https://www.mercadopago.com.co/developers/es/', // Colombia
            'mlm' => 'https://www.mercadopago.com.mx/developers/es/', // Mexico
            'mpe' => 'https://www.mercadopago.com.pe/developers/es/', // Peru
            'mlu' => 'https://www.mercadopago.com.uy/developers/es/', // Uruguay
        ];
        return $countryLink[$checkout];
    }

    public function field_custom_url_ipn()
    {
        return [
            'title' => __('URL for IPN', 'woocommerce-mercadopago-split'),
            'type' => 'text',
            'description' => sprintf(
                __('Enter a URL to receive payment notifications. In %s you can check more information.', 'woocommerce-mercadopago-split'),
                '<a href="' . $this->getCountryLinkGuide($this->checkout_country) . 'guides/notifications/ipn/">' . __('our guides', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'default' => '',
            'desc_tip' => __('IPN (Instant Payment Notification) is a notification of events that take place on your platform and that is sent from one server to another through an HTTP POST call. See more information in our guides.', 'woocommerce-services')
        ];
    }

    public function field_checkout_payments_advanced_description()
    {
        return [
            'title' => __('Edit these advanced fields only when you want to modify the preset values.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text mp-mt--12 mp-mb-18'
        ];
    }

    public function field_coupon_mode()
    {
        return [
            'title' => __('Discount coupons', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'default' => 'no',
            'description' => __('Will you offer discount coupons to customers who buy with Mercado Pago?', 'woocommerce-mercadopago-split'),
            'options' => [
                'no' => __('No', 'woocommerce-mercadopago-split'),
                'yes' => __('Yes', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function field_no_credentials()
    {
        return [
            'title' => sprintf(
                __('It appears that your credentials are not properly configured.<br/>Please, go to %s and configure it.', 'woocommerce-mercadopago-split'),
                '<a href="' . esc_url(admin_url('admin.php?page=mercado-pago-settings')) . '">' . __('Market Payment Configuration', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'type' => 'title'
        ];
    }

    public function field_binary_mode()
    {
        return [
            'title' => __('Binary mode', 'woocommerce-mercadopago-split'),
            'type' => 'select',
            'default' => 'no',
            'description' => __('Accept and reject payments automatically. Do you want us to activate it?', 'woocommerce-mercadopago-split'),
            'desc_tip' => __('If you activate binary mode you will not be able to leave pending payments. This can affect fraud prevention. Leave it idle to be backed by our own tool.', 'woocommerce-services'),
            'options' => [
                'yes' => __('Yes', 'woocommerce-mercadopago-split'),
                'no' => __('No', 'woocommerce-mercadopago-split')
            ]
        ];
    }

    public function field_gateway_discount()
    {
        return [
            'title' => __('Discounts per purchase with Mercado Pago', 'woocommerce-mercadopago-split'),
            'type' => 'number',
            'description' => __('Choose a percentage value that you want to discount your customers for paying with Mercado Pago.', 'woocommerce-mercadopago-split'),
            'default' => '0',
            'custom_attributes' => [
                'step' => '0.01',
                'min' => '0',
                'max' => '99'
            ]
        ];
    }

    public function field_commission()
    {
        return [
            'title' => __('Commission for purchase with Mercado Pago', 'woocommerce-mercadopago-split'),
            'type' => 'number',
            'description' => __('Choose an additional percentage value that you want to charge as commission to your customers for paying with Mercado Pago.', 'woocommerce-mercadopago-split'),
            'default' => '0',
            'custom_attributes' => [
                'step' => '0.01',
                'min' => '0',
                'max' => '99'
            ]
        ];
    }

    public function field_currency_conversion(WC_WooMercadoPagoSplit_PaymentAbstract $method)
    {
        return WC_WooMercadoPagoSplit_Helpers_CurrencyConverter::getInstance()->getField($method);
    }

    public function field_checkout_support_title()
    {
        return [
            'title' => __('Questions?', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd_mb mp-mg-0'
        ];
    }

    public function field_checkout_support_description()
    {
        return [
            'title' => __('Check out the step-by-step of how to integrate the Mercado Pago Plugin for Woo Commerce in our developer website.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text'
        ];
    }

    public function field_checkout_support_description_link()
    {
        return [
            'title' => sprintf(
                __('%s', 'woocommerce-mercadopago-split'),
                '<a href="' . $this->getCountryLinkGuide($this->checkout_country) . 'guides/plugins/woocommerce/integration" target="_blank">' . __('Review documentation', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'type' => 'title',
            'class' => 'mp_tienda_link'
        ];
    }

    public function field_checkout_support_problem()
    {
        return [
            'title' => sprintf(
                __('Still having problems? Contact our support team through their %s', 'woocommerce-mercadopago-split'),
                '<a href="' . $this->getCountryLinkGuide($this->checkout_country) . 'support/" target="_blank">' . __('contact form.', 'woocommerce-mercadopago-split') . '</a>'
            ),
            'type' => 'title',
            'class' => 'mp-text-support'
        ];
    }

    public function field_checkout_ready_title()
    {
        return [
            'title' => $this->isProductionMode() ? __('Everything ready for the takeoff of your sales?', 'woocommerce-mercadopago-split') : __('Everything set up? Go to your store in Sandbox mode', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_subtitle_bd_mb mp-mg-0'
        ];
    }

    public function field_checkout_ready_description()
    {
        return [
            'title' => $this->isProductionMode() ? __('Visit your store as if you were one of your customers and check that everything is fine. If you already went to Production,<br> bring your customers and increase your sales with the best online shopping experience.', 'woocommerce-mercadopago-split') : __('Visit your store and simulate a payment to check that everything is fine.', 'woocommerce-mercadopago-split'),
            'type' => 'title',
            'class' => 'mp_small_text'
        ];
    }

    public function field_checkout_ready_description_link()
    {
        return [
            'title' => sprintf(
                __('%s', 'woocommerce-mercadopago-split'),
                '<a href="' . get_site_url() . '" target="_blank">' . ($this->isProductionMode() ? __('Visit my store', 'woocommerce-mercadopago-split') : __('I want to test my sales', 'woocommerce-mercadopago-split')) . '</a>'
            ),
            'type' => 'title',
            'class' => 'mp_tienda_link'
        ];
    }

    public function is_available()
    {
        if (!did_action('wp_loaded')) {
            return false;
        }
        global $woocommerce;
        $w_cart = $woocommerce->cart;

        if (isset($w_cart) && WC_WooMercadoPagoSplit_Module::is_subscription($w_cart->get_cart())) {
            return false;
        }

        $_mp_public_key = $this->getPublicKey();
        $_mp_access_token = $this->getAccessToken();
        $_site_id_v1 = $this->getOption('_site_id_v1');

        return isset($this->settings['enabled']) && 'yes' == $this->settings['enabled'] && !empty($_mp_public_key) && !empty($_mp_access_token) && !empty($_site_id_v1);
    }

    public function admin_url()
    {
        return defined('WC_VERSION') && version_compare(WC_VERSION, '2.1', '>=') ? admin_url('admin.php?page=wc-settings&tab=checkout&section=' . $this->id) : admin_url('admin.php?page=woocommerce_settings&tab=payment_gateways&section=' . get_class($this));
    }

    public function getCommonConfigs()
    {
        return self::COMMON_CONFIGS;
    }

    public function isTestUser ()
    {
        return !$this->isProductionMode();
    }

    public function getMpInstance()
    {
        $mp = WC_WooMercadoPagoSplit_Module::getMpInstanceSingleton($this);
        if (!empty($mp)) {
            $mp->sandbox_mode($this->sandbox);
        }
        return $mp;
    }

    public function disableAllPaymentsMethodsMP()
    {
        $gateways = apply_filters('woocommerce_payment_gateways', []);
        foreach ($gateways as $gateway) {
            if (strpos($gateway, "MercadoPago") === false) {
                continue;
            }

            $key = 'woocommerce_' . $gateway::getId() . '_settings';
            $options = get_option($key);
            if (!empty($options)) {
                if (isset($options['checkout_credential_prod']) && $options['checkout_credential_prod'] == 'yes' && !empty($this->mp_access_token_prod)) {
                    continue;
                }

                if (isset($options['checkout_credential_prod']) && $options['checkout_credential_prod'] == 'no' && !empty($this->mp_access_token_test)) {
                    continue;
                }

                $options['enabled'] = 'no';
                update_option($key, apply_filters('woocommerce_settings_api_sanitized_fields_' . $gateway::getId(), $options));
            }
        }
    }

    public function isCurrencyConvertable()
    {
        return $this->currency_convertion;
    }

    public function isProductionMode()
    {
        $this->updateCredentialProduction();
        return $this->getOption('checkout_credential_prod', get_option('checkout_credential_prod', 'no')) === 'yes';
    }

    public function updateCredentialProduction()
    {
        if (!empty($this->getOption('checkout_credential_prod', null))) {
            return;
        }

        $gateways = apply_filters('woocommerce_payment_gateways', []);
        foreach ($gateways as $gateway) {
            if (strpos($gateway, "MercadoPago") === false) {
                continue;
            }

            $key = 'woocommerce_' . $gateway::getId() . '_settings';
            $options = get_option($key);
            if (!empty($options) && !isset($options['checkout_credential_production'])) {
                continue;
            }
            $options['checkout_credential_prod'] = $options['checkout_credential_production'];
            update_option($key, apply_filters('woocommerce_settings_api_sanitized_fields_' . $gateway::getId(), $options));
        }
    }
}
