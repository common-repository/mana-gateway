<?php

namespace MANA_Gateway\Inc\Common\Gateways;

use MANA_Gateway\Inc\Common as NS;

if (class_exists('MANA_Gateway\Inc\Common\Gateway_Settings')):
    NS\Gateway_Settings::register_gateway('MANA_Gateway\Inc\Common\Gateways\Gateway_Zarinpal');

    class Gateway_Zarinpal extends NS\Gateway_Base
    {
        /**
         * Gateway_Zarinpal constructor.
         */
        public function __construct()
        {
            parent::__construct(
                'GW_ZARINPAL',
                __('ZarinPal secure payment', 'mana-gateway'),
                __('ZarinPal payment gateway for Wordpress', 'mana-gateway'),
                array(
                    'status' => false,
                    'merchantcode' => '',
                    'zarinwebgate' => false,
                    'description' => '',
                    'success_massage' => '',
                    'failed_massage' => '',
                ),
                'IRR'
            );
        }

        public function fields($fields = array())
        {
            return parent::fields(
                array(
                    'comment' => array(
                        'title' => __('ZarinPal gateway\'s settings for Mana gateway plugin', 'mana-gateway'),
                        'type' => 'p',
                        'size' => 'normal',
                    ),
                    'status' => array(
                        'title' => __('Status', 'mana-gateway'),
                        'type' => 'checkbox',
                        'label' => __('Status', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => false,
                    ),
                    'merchantcode' => array(
                        'title' => __('Merchant Code', 'mana-gateway'),
                        'type' => 'text',
                        'valid_value' => '\w{8}-\w{4}-\w{4}-\w{4}-\w{12}',
                        'example_value' => 'XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX',
                        'description' => __('ZarinPal gateway\'s merchant code', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => true,
                    ),
                    'zarinwebgate' => array(
                        'title' => __('Activate ZarinGate', 'mana-gateway'),
                        'type' => 'checkbox',
                        'label' => __('To activate direct gateway (ZarinGate), please tick the checkbox.', 'mana-gateway'),
                        'description' => __('ZarinPal\'s direct gateway', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => true,
                    ),
                    'description' => array(
                        'title' => __('Description', 'mana-gateway'),
                        'type' => 'textarea',
                        'description' => __('Transaction description: You can use these shortcodes: {pay_id},{name}', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => true,
                    ),
                    'success_massage' => array(
                        'title' => __('Success Message', 'mana-gateway'),
                        'type' => 'textarea',
                        'description' => __('Message after a successful transaction. You can use these shortcodes: {pay_id},{price},{name},{gateway},{refid},{message}', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => true,
                    ),
                    'failed_massage' => array(
                        'title' => __('Failed Message', 'mana-gateway'),
                        'type' => 'textarea',
                        'description' => __('Message after a successful transaction. You can use these shortcodes: {pay_id},{price},{name},{gateway},{message}', 'mana-gateway'),
                        'input' => true,
                        'desc_tip' => true,
                    ),
                )
            );
        }

        /**
         * @param $action
         * @param $data
         * @return mixed
         */
        private function zarinpal_request($action, $data)
        {
            $url = esc_url_raw("https://www.zarinpal.com/pg/rest/WebGate/$action.json");

            $args = array(
                'body' => $data,
                'headers'     => array('Content-Type'=> 'application/json', 'Content-Length' => strlen($data)),
            );

            $response = wp_safe_remote_post( $url, $args );

            if (is_wp_error($response)):
                return null;
            else:
                return json_decode(wp_remote_retrieve_body($response),true);
            endif;
        }

        /**
         * @param $args
         * @return mixed
         */
        public function request($args)
        {
            $description = $this->get_setting('description');
            $description = str_replace('{pay_id}', $args['pay_id'], $description);
            $description = str_replace('{name}', $args['name'], $description);
            $data = array(
                'MerchantID' => $this->get_setting('merchantcode'),
                'Amount' => $args['price'],
                'Description' => $description,
                'CallbackURL' => add_query_arg(array('pay_id' => $args['pay_id']), $this->callback),
            );
            $data = json_encode($data);

            $result = $this->zarinpal_request('PaymentRequest', $data);

            if (is_null($result)) {
                $Status_code = 0;
                $Status = 'error';
                $Message = __('HTTP Error', 'mana-gateway');
                $Authority = "";
                $payment_url = "";
            } else {
                $Status_code = (isset($result["Status"]) && $result["Status"] != "") ? $result["Status"] : 0;
                $Message = $this->error_message($Status_code, $this->get_setting('description'), $this->callback, true);
                $Authority = (isset($result["Authority"]) && $result["Authority"] != "") ? $result["Authority"] : "";
                $Status = ($Status_code == 100 && strlen($Authority) == 36) ? 'success' : 'error';
                $StartPay = (isset($result["Authority"]) && $result["Authority"] != "") ? "https://www.zarinpal.com/pg/StartPay/" . ltrim($Authority, '0') : "";
                $payment_url = ($this->get_setting('zarinwebgate') == true) ? "{$StartPay}/ZarinGate" : $StartPay;
            }
            return array(
                'merchantid' => $this->get_setting('merchantcode'),
                'description' => $this->get_setting('description'),
                'callbackurl' => $this->callback,
                'status' => $Status,
                'status_code' => $Status_code,
                'message' => $Message,
                'payment_url' => $payment_url,
                'authority' => $Authority,
            );
        }

        public function verify($pay_info)
        {
            $status = sanitize_text_field($_GET['Status']);
            $authority = sanitize_text_field($_GET['Authority']);
            $MerchantID = $pay_info['request_result']['merchantid'];
            $amount = $pay_info['user_info']['price'];
            $pattern = "/\d{36}/";
            if ($status === 'OK' && preg_match($pattern, $authority)):
                $data = array(
                    'MerchantID' => $MerchantID,
                    'Authority' => $authority,
                    'Amount' => $amount,
                );
                $data = json_encode($data);
                $result = $this->zarinpal_request('PaymentVerification', $data);
                $status_code = (isset($result["Status"]) && $result["Status"] != "") ? $result["Status"] : 0;
            elseif ($status === 'NOK'):
                $status_code = -22;
            else:
                $status_code = 0;
            endif;

            $status = ($status_code == 100 || $status_code == 101) ? 'success' : 'error';
            $RefID = (isset($result["RefID"]) && $result["RefID"] != "") ? $result["RefID"] : 0;
            $Message = $this->error_message($status_code, $this->get_setting('description'), $this->callback, false);

            return array(
                'status_code' => $status_code,
                'status' => $status,
                'refid' => $RefID,
                'message' => $Message,
            );
        }

        public function error_message($status, $description, $callback, $request = false)
        {
            if (empty($callback) && $request === true) {
                return __("Callback URL shouldn't be empty.", 'mana-gateway');
            }

            if (empty($description) && $request === true) {
                return __("Description shouldn't be empty.", 'mana-gateway');
            }
            $error = array(
                "-1" => __("Incomplete sent data.", 'mana-gateway'),
                "-2" => __("Invalid IP or Merchant code.", 'mana-gateway'),
                "-3" => __("Invalid amount due to restrictions.", 'mana-gateway'),
                "-11" => __("Couldn't find the request.", 'mana-gateway'),
                "-12" => __("Can't edit the request.", 'mana-gateway'),
                "-21" => __("No payment transaction found.", 'mana-gateway'),
                "-22" => __("Failed transaction.", 'mana-gateway'),
                "-33" => __("Paid amount doesn't match request amount.", 'mana-gateway'),
                "-54" => __("Request has been archived.", 'mana-gateway'),
                "100" => __("Successful transaction.", 'mana-gateway'),
                "101" => __("Transaction is successful and had been verified before.", 'mana-gateway'),
            );

            if (array_key_exists("{$status}", $error)) {
                return $error["{$status}"];
            } else {
                return __("Unknown Error occurred.", 'mana-gateway');
            }
        }

        public function get_message($info, $status)
        {
            if ($status === 'error'):
                $message = $this->settings['failed_massage'];
                $message = str_replace('{pay_id}', $info['user_info']['pay_id'], $message);
                $message = str_replace('{price}', $info['user_info']['price'] . ' ' . $info['user_info']['currency'], $message);
                $message = str_replace('{name}', $info['user_info']['name'], $message);
                $message = str_replace('{gateway}', $this->title, $message);

                $message = str_replace('{message}', (isset($info['verify_result']['message']) ? $info['verify_result']['message'] : $info['request_result']['message']), $message);
            else:
                $message = $this->settings['success_massage'];
                $message = str_replace('{pay_id}', $info['user_info']['pay_id'], $message);
                $message = str_replace('{price}', $info['user_info']['price'] . ' ' . $info['user_info']['currency'], $message);
                $message = str_replace('{name}', $info['user_info']['name'], $message);
                $message = str_replace('{gateway}', $this->title, $message);
                $message = str_replace('{refid}', $info['verify_result']['refid'], $message);
                $message = str_replace('{message}', $info['verify_result']['message'], $message);
            endif;
            $message = str_replace("\n", '<br>', $message);
            return $message;
        }
    }
endif;