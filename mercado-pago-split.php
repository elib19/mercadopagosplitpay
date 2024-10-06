<?php
/**
 * Plugin Name: Mercado Pago Integration
 * Plugin URI: https://brasilnarede.online/
 * Description: Integração do Mercado Pago com WooCommerce e WCFM Marketplace.
 * Version: 1.0
 * Author: Eli Silva
 * Author URI: https://brasilnarede.online/
 * Text Domain: mercado-pago-split
 * Domain Path: /languages
 * License: GPL2
 */

// Evita acesso direto ao arquivo
defined('ABSPATH') || exit;

// Define constantes do plugin
define('MERCADO_PAGO_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('MERCADO_PAGO_PLUGIN_URL', plugin_dir_url(__FILE__));

// Inclui os arquivos necessários
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/helper.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-settings.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/class-mercado-pago-vendor.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/gateway.php';
require_once MERCADO_PAGO_PLUGIN_DIR . 'includes/install.php';

// Ativa as funções do plugin
add_action('plugins_loaded', 'mercado_pago_init');

function mercado_pago_init() {
    // Verifica se o WooCommerce está ativo
    if (!class_exists('WooCommerce')) {
        add_action('admin_notices', 'mercado_pago_woocommerce_inactive_notice');
        return;
    }

    // Inicializa as configurações do Mercado Pago
    Mercado_Pago_Settings::init();
    Mercado_Pago_Vendor::init();

    // Registra o gateway de pagamento
    add_filter('woocommerce_payment_gateways', 'mercado_pago_add_gateway');
}

function mercado_pago_add_gateway($methods) {
    $methods[] = 'WC_Mercado_Pago_Gateway'; // Adiciona o gateway Mercado Pago
    return $methods;
}

function mercado_pago_woocommerce_inactive_notice() {
    echo '<div class="error"><p>' . __('O plugin Mercado Pago Split Payment requer o WooCommerce para funcionar.', 'mercado-pago-split') . '</p></div>';
}

// Hook para ativar o plugin
register_activation_hook(__FILE__, 'mercado_pago_activate');

function mercado_pago_activate() {
    // Chama a função de instalação
    mercado_pago_install();
}

// Hook para desinstalar o plugin
register_uninstall_hook(__FILE__, 'mercado_pago_uninstall');

function mercado_pago_uninstall() {
    // Limpa as configurações do plugin ao desinstalar
    delete_option('mercado_pago_settings');
}

// Declaração da classe de configurações do Mercado Pago
if (!class_exists('Mercado_Pago_Settings')) {
    class Mercado_Pago_Settings {
        public static function init() {
            add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
            add_action('admin_init', array(__CLASS__, 'register_settings'));
        }

        public static function add_admin_menu() {
            add_menu_page(
                'Configurações Mercado Pago',
                'Configurações Mercado Pago',
                'manage_options',
                'mercado-pago-settings',
                array(__CLASS__, 'settings_page')
            );
        }

        public static function register_settings() {
            register_setting('mercado_pago_group', 'mercado_pago_settings');
        }

        public static function settings_page() {
            ?>
            <div class="wrap">
                <h1><?php _e('Configurações Mercado Pago', 'mercado-pago-split'); ?></h1>
                <form method="post" action="options.php">
                    <?php settings_fields('mercado_pago_group'); ?>
                    <?php $options = get_option('mercado_pago_settings'); ?>
                    <table class="form-table">
                        <tr valign="top">
                            <th scope="row"><?php _e('Access Token', 'mercado-pago-split'); ?></th>
                            <td><input type="text" name="mercado_pago_settings[access_token]" value="<?php echo esc_attr($options['access_token']); ?>" /></td>
                        </tr>
                    </table>
                    <?php submit_button(); ?>
                </form>
            </div>
            <?php
        }
    }
}

// Declaração da classe de vendedores do Mercado Pago
if (!class_exists('Mercado_Pago_Vendor')) {
    class Mercado_Pago_Vendor {
        public static function init() {
            add_action('wcfm_vendors_dashboard', array(__CLASS__, 'add_vendor_dashboard'));
            add_action('wp_ajax_wcfm_vendors_ajax_process_payment', array(__CLASS__, 'process_payment'));
        }

        public static function add_vendor_dashboard() {
            require_once plugin_dir_path(__FILE__) . '../views/vendor-dashboard.php';
        }

        public static function process_payment() {
            if (isset($_POST['payment_data'])) {
                $payment_data = json_decode(stripslashes($_POST['payment_data']), true);
                $vendor_id = get_current_user_id(); // ID do vendedor

                // Processar o pagamento
                $response = self::make_payment($payment_data, $vendor_id);
                echo json_encode($response);
                wp_die(); // encerra corretamente a execução
            }
        }

        private static function make_payment($payment_data, $vendor_id) {
            $valor = $payment_data['amount'];
            $descricao = $payment_data['description'];
            $access_token = Mercado_Pago_Settings::get_settings()['access_token'];

            // Cálculo das taxas
            $marketplace_fee = $valor * 0.10; // 10% de taxa do marketplace
            $valor_vendedor = $valor - $marketplace_fee;

            // Realiza a chamada para a API do Mercado Pago
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.mercadopago.com/v1/payments',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => json_encode(array(
                    'transaction_amount' => $valor,
                    'description' => $descricao,
                    'payment_method_id' => 'pix', // ou outro método
                    'payer' => array('email' => $payment_data['email']),
                    'application_fee' => $marketplace_fee,
                    'external_reference' => 'reference-' . time(),
                )),
                CURLOPT_HTTPHEADER => array(
                    'Authorization: Bearer ' . $access_token,
                    'Content-Type: application/json',
                ),
            ));

            $response = curl_exec($curl);
            curl_close($curl);

            return json_decode($response);
        }
    }
}

// Declaração da classe do gateway de pagamento do Mercado Pago
if (!class_exists('WC_Mercado_Pago_Gateway')) {
    class WC_Mercado_Pago_Gateway extends WC_Payment_Gateway {
        public function __construct() {
            $this->id = 'mercado_pago';
            $this->icon = ''; // URL do ícone do gateway
            $this->has_fields = false;
            $this->method_title = __('Mercado Pago', 'mercado-pago-split');
            $this->method_description = __('Aceite pagamentos via Mercado Pago.', 'mercado-pago-split');

            // Carrega as configurações
            $this->init_form_fields();
            $this->init_settings();

            // Ações
            add_action('woocommerce_receipt_' . $this->id, array($this, 'receipt_page'));
            add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'check_response'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
                    'title' => __('Habilitar/Desabilitar', 'mercado-pago-split'),
                    'type' => 'checkbox',
                    'label' => __('Habilitar Mercado Pago', 'mercado-pago-split'),
                    'default' => 'yes'
                ),
                'title' => array(
                    'title' => __('Título', 'mercado-pago-split'),
                    'type' => 'text',
                    'description' => __('Título que o usuário vê durante o checkout.', 'mercado-pago-split'),
                    'default' => __('Mercado Pago', 'mercado-pago-split')
                ),
                'description' => array(
                    'title' => __('Descrição', 'mercado-pago-split'),
                    'type' => 'textarea',
                    'description' => __('Descrição do método de pagamento.', 'mercado-pago-split'),
                    'default' => __('Pague usando o Mercado Pago.', 'mercado-pago-split')
                ),
            );
        }

        public function process_payment($order_id) {
            $order = wc_get_order($order_id);

            // Lógica para processar o pagamento usando o Mercado Pago
            $payment_data = array(
                // Dados necessários para o pagamento, incluindo split
            );

            // Envie os dados de pagamento para o Mercado Pago

            // Retorne o status do pagamento
            return array(
                'result' => 'success',
                'redirect' => $order->get_checkout_order_received_url()
            );
        }

        public function receipt_page($order) {
            // Página de recebimento do pagamento
        }

        public function check_response() {
            // Verifique a resposta do Mercado Pago
        }
    }
}
