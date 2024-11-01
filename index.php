<?php
/*
Plugin Name: WooCommerce 3D Secure (Bankart) Payment Gateway
Description: Implements 3D Secure Payment Gateway provided by Bankart (http://www.bankart.si). Supports MasterCard and Visa.
Version: 1.2
Author: Gregor Zorc
Text Domain: wc-gateway-bankart
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!class_exists('SecureSettings')) {
    require_once(plugin_dir_path(__FILE__) . 'SecureSettings.php');
}

if (!class_exists('BankartHPPResponse')) {
    require_once(plugin_dir_path(__FILE__) . 'BankartHPPResponse.php');
}

if (!class_exists('BankartPurchaseResponse')) {
    require_once(plugin_dir_path(__FILE__) . 'BankartPurchaseResponse.php');
}

add_action('plugins_loaded', 'woocommerce_bankart_init', 0);

function woocommerce_bankart_init() {

    // Make sure WooCommerce is active.
    if (!class_exists('WC_Payment_Gateway')) {
        return;
    }

    load_plugin_textdomain('wc-gateway-bankart', false, dirname( plugin_basename( __FILE__ ) ) . '/languages');

    class woocommerce_bankart extends WC_Payment_Gateway {

        private $logger;

        private $environment;

        private $transaction_type;

        private $transaction_language;

        private $currency;

        private $secure_settings_path;

        public function __construct() {
            global $woocommerce;

            $this->logger = new WC_Logger();
            $this->id = "bankart";
            $this->method_title = "Bankart 3D Secure (Visa, Mastercard)";
            $this->method_description = "3D Secure Payment (Visa, Mastercard) provided by Bankart";
            $this->title = "3D Secure Payment (Visa, Mastercard) provided by Bankart";
            $this->icon = plugin_dir_url(__FILE__) . "img/mc.png";
            $this->secure_settings_path = plugin_dir_path(__FILE__) . 'cgn/';

            $this->has_fields = true;
            $this->supports = array();
            $this->init_form_fields();

            $this->init_settings();
            $this->title = $this->settings['title'];
            $this->description = $this->settings['description'];
            $this->environment = $this->settings['environment'];
            $this->transaction_type = $this->settings['transaction_type'];
            $this->transaction_language = $this->settings['transaction_language'];
            $this->curreny = $this->settings['currency'];

            // Actions
            add_action('init', array( $this, 'gateway_callback'));
            add_action('woocommerce_api_' . $this->id . '_callback', array(&$this, 'gateway_callback'));
            add_action('woocommerce_api_' . $this->id . '_customer_redirect', array(&$this, 'gateway_customer_redirect'));

            if (is_admin()) {
                add_action("woocommerce_update_options_payment_gateways_" . $this->id, array($this, "process_admin_options"));
            }
        }

        /**
         * Initialize Gateway Settings Form Fields
         */
        function init_form_fields() {
            $test_resource_file_available_desc =
                '<p><strong><span style="color: #900" class="dashicons dashicons-thumbs-down"></span> Not available</strong></p>';
            if (@fopen($this->secure_settings_path . 'testing/resource.cgn', 'a')) {
                $test_resource_file_available_desc = '<p><strong><span style="color: #090" class="dashicons dashicons-thumbs-up"></span> Avaialble.</strong></p>';
            }

            $production_resource_file_available_desc =
                '<p><strong><span style="color: #900" class="dashicons dashicons-thumbs-down"></span> Not available</strong></p>';
            if (@fopen($this->secure_settings_path . 'production/resource.cgn', 'a')) {
                $production_resource_file_available_desc = '<p><strong><span style="color: #090" class="dashicons dashicons-thumbs-up"></span> Avaialble.</strong></p>';
            }

            $this->form_fields = array(

                'enabled' => array(
                    'title' => __( 'Enable/Disable', 'wc-gateway-bankart' ),
                    'type' => 'checkbox',
                    'label' => __( 'Enable', 'wc-gateway-bankart' ),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title' => __( 'Title', 'wc-gateway-bankart' ),
                    'type' => 'text',
                    'description' => __( 'This controls the title which the user sees during checkout.', 'wc-gateway-bankart' ),
                    'default' => $this->method_title
                ),

                'description' => array(
                    'title' => __( 'Description', 'wc-gateway-bankart' ),
                    'type' => 'textarea',
                    'description' => __( 'This controls the description which the user sees during checkout.', 'wc-gateway-bankart' ),
                    'default' => __('Pay with your credit card.', 'wc-gateway-bankart')
                ),

                'currency' => array(
                    'title' => __('Store currency', 'wc-gateway-bankart'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'description' => __("Curreny code used by Bankart's 3D Secure gateway", 'wc-gateway-bankart'),
                    'options' => array('978' => 'EUR', '807' => 'MKD',), 'default' => '978'
                ),

                'transaction_language' => array(
                    'title' => __( 'Language', 'wc-gateway-bankart' ),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'options' => array(
                        'SI' => 'Slovenian',
                        'HR' => 'Croatian',
                        'US' => 'English',
                        'MKD' => 'Macedonian',
                        'SR' => 'Serbian',
                        'IT' => 'Italian',
                        'DE' => 'German',
                        'ESP' => 'Spanish'
                    ),
                    'description' => __( 'Credit card form language.', 'wc-gateway-bankart' ),
                    'default' => 'SI',
                ),

                'environment' => array(
                    'title' => __('Environment Type', 'wc-gateway-bankart'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'options' => array(
                        'testing' => 'Testing',
                        'production' => 'Production'
                    ),
                    'description' => __( 'Select environment.', 'wc-gateway-bankart' ),
                    'default' => 'testing'
                ),

                'transaction_type' => array(
                    'title' => __('Transaction type (action)', 'wc-gateway-bankart'),
                    'type' => 'select',
                    'class' => 'wc-enhanced-select',
                    'options' => array('1' => 'Purchase', '4' => 'Authorization',),
                    'default' => '4'
                ),

                'test_resource_available' => array(
                    'title' => __( 'Test resource.cgn available', 'wc-gateway-bankart' ),
                    'type' => 'title',
                    'description' => $test_resource_file_available_desc,
                ),

                'production_resource_available' => array(
                    'title' => __( 'Production resource.cgn available', 'wc-gateway-bankart' ),
                    'type' => 'title',
                    'description' => $production_resource_file_available_desc
                ),
            );
        }

        /**
         * Tries to retrieve HPP (Hosted Payment Page) URL from Bankart and redirect customer there.
         */
        function process_payment($order_id) {
            $result = null;

            $this->log("Processing payment for order_id=" . $order_id);

            $order = new WC_Order($order_id);
            $order->update_status('pending', 'Ready to fetch HPP URL');

            $hpp_response = $this->request_hpp_url($order_id, $order->get_total());

            $bankart_response = new BankartHPPResponse($hpp_response);
            if (!$bankart_response->is_error()) {
                $order->update_status('pending', 'Ready for HPP payment');
                update_post_meta($order_id, "bankart-hpp-url", $bankart_response->get_payment_url());
                update_post_meta($order_id, "bankart-payment-id", $bankart_response->get_payment_id());

                $result = array(
                    'result' => 'success',
                    'redirect' => $bankart_response->get_payment_url()
                );
            } else {
                $this->log("Error getting HPP from Bankart: " . $bankart_response->get_error_message());
                $order->update_status('failed', $bankart_response->get_error_message());
                wc_add_notice( __('Payment error:', 'woothemes') . $bankart_response->get_error_message(), 'error' );
            }

            return $result;
        }

        /**
         * HTTP POST to retrieve Bankart's HPP URL.
         */
        private function request_hpp_url($order_id, $amount) {
            $result = null;

            $secure_settings = new SecureSettings();
            $settings_file = $this->secure_settings_path . $this->environment;
            $secure_settings->load($settings_file);

            $response_url  = str_replace('http:', 'https:', home_url('/wc-api/' . $this->id . '_callback'));
            $data = array(
                'id' => $secure_settings->get_id(),
                'passwordhash' => $secure_settings->get_passwordhash(),
                'amt' => $amount,
                'currencycode' => $this->currency,
                'action' => $this->transaction_type,
                'langid' => $this->transaction_language,
                'responseURL' => $response_url,
                'errorURL' => $response_url,
                'trackid' => $order_id
            );

            $this->log(
                sprintf(
                    "POST-ing to Bankart: responseURL=%s, amt=%s, action=%s, trackid=%s ...",
                    $response_url, $data['amt'], $data['action'], $data['trackid']
                )
            );

            $args = array(
                'method' => 'POST',
                'body' => urldecode(http_build_query($data, '', '&')),
                'timeout' => 45,
                'sslverify' => false,
                'headers' => array(
                    'Content-Type' => 'application/x-www-form-urlencoded'
                ),
            );

            $response = wp_remote_post($secure_settings->get_url(), $args);
            if (wp_remote_retrieve_response_code($response) === 200) {
                $result = wp_remote_retrieve_body($response);
            } else {
                $msg = sprintf(
                    "Unexpected response from Bankart. Code=%s, body=%s",
                     wp_remote_retrieve_response_code($response),
                    wp_remote_retrieve_body($response)
                );
                $this->log($msg);
            }
            return $result;
        }

        /**
         * Called by Bankart, after processing customers' card on their side.
         */
        function gateway_callback() {
            global $woocommerce;
            global $wpdb;

            $this->log("Received POST request from gateway: " . print_r(filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING), true));
            $this->log("Received GET request from gateway: " . print_r(filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING), true));

            $response = new BankartPurchaseResponse();
            $payment_id = $response->get_request_param('paymentid');

            if (!$payment_id) {
                $this->log("payment_id is missing, exiting.");
                exit();
            }

            $query_string = "
                SELECT post_id
                FROM $wpdb->postmeta 
                WHERE meta_value = '$payment_id' and meta_key = 'bankart-payment-id'
            ";            

            $query_result = $wpdb->get_row($query_string, OBJECT);                                  
            $order = wc_get_order($query_result->post_id);
            
            if ($response->is_authorized()) {
                $order->add_order_note(sprintf('Bankart Payment Completed. PaymentID is %s.'), $payment_id);
                $order->payment_complete();
            } else {
                $order->update_status('failed', $response->get_error_message());
            }

            $customer_redirect_url = str_replace('http:', 'https:', home_url('/wc-api/' . $this->id . '_customer_redirect'));

            printf('REDIRECT=%s?orderId=%s&paymentId=%s', $customer_redirect_url, $order->get_id(), $payment_id);
            exit();
        }


        /**
         * Called by Bankart, when they're ready to redirect customer from their HPP back to our shop.
         */
        function gateway_customer_redirect() {
            $order_id = filter_input(INPUT_GET, 'orderId', FILTER_VALIDATE_INT);
            $payment_id = filter_input(INPUT_GET, 'paymentId', FILTER_SANITIZE_STRING);

            if (!$order_id || !$payment_id) {
                $this->log("Invalid GET request: " . print_r(filter_input_array(INPUT_GET, FILTER_SANITIZE_STRING), true));
                exit();
            }

            $order = new WC_Order($order_id);
            wp_redirect($this->get_return_url($order));
            exit();
        }

        protected function log($message) {
            $this->logger->add($this->id, $message);
        }
    }

    function add_bankart_gateway($methods) {
        $methods[] = 'woocommerce_bankart';
        return $methods;
    }

    /**
     * Add the gateway to available WC gateways.
     */
    add_filter('woocommerce_payment_gateways', 'add_bankart_gateway');
}
