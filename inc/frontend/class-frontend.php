<?php

namespace MANA_Gateway\Inc\Frontend;

use MANA_Gateway\Inc\Common;

/**
 *
 * @since      1.0.0
 *
 * @author    MANA
 */
class Frontend
{

    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_name The ID of this plugin.
     */
    private $plugin_name;

    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $version The current version of this plugin.
     */
    private $version;

    /**
     * The text domain of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string $plugin_text_domain The text domain of this plugin.
     */
    private $plugin_text_domain;

    /**
     * The prefix of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $plugin_prefix;

    public static $already_run;

    /**
     * Initialize the class and set its properties.
     *
     * @since       1.0.0
     * @param       string $plugin_name The name of this plugin.
     * @param       string $version The version of this plugin.
     * @param       string $plugin_text_domain The text domain of this plugin.
     * @param       string $plugin_prefix The prefix of this plugin.
     */
    public function __construct($plugin_name, $version, $plugin_text_domain, $plugin_prefix)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_text_domain = $plugin_text_domain;
        $this->plugin_prefix = $plugin_prefix;

    }

    /**
     * Register the stylesheets for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/mana-gateway-frontend.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/mana-gateway-frontend.js', array('jquery'), $this->version, false);
    }

    public function pay()
    {
        $args = array();
        foreach ($_POST as $key => $value):
            $args[sanitize_text_field($key)] = sanitize_text_field($value);
        endforeach;
        $settings = new Common\Gateway_Settings($this->plugin_text_domain);
        $gateway = $settings->get_gateway_by_id($args['gateway']);
        unset($args['action']);
        $args['price'] = $settings->get_options('post_price_list')[$args['post_id']]['price'];
        if (is_user_logged_in()):
            $args['user'] = get_current_user_id();
        endif;
        $unique_pay_id = true;
        while ($unique_pay_id):
            $pay_id = $this->generate_pay_id();
            global $wpdb;
            $table_name = $wpdb->prefix . 'mana_gateway_options';
            if ( $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE option_name = 'pay_log_%d';",$pay_id)) == 0):
                $unique_pay_id = false;
            endif;
        endwhile;
        $args['pay_id'] = $pay_id;
        $request_result = $gateway->request($args);
        $args['date'] = current_time('Y-m-d H:i');
        $args['gateway_name'] = $gateway->get_title();
        $args['currency'] = $settings->get_currencies()[$gateway->get_currency()];

        $settings->update_options(
            'pay_log_' . $args['pay_id'],
            array(
                'user_info' => $args,
                'request_result' => $request_result,
            )
        );
        if ($request_result['status'] === 'success'):
            wp_redirect($request_result['payment_url']);
        else:
            wp_redirect(add_query_arg(
                array(
                    'status' => $request_result['status'],
                    'pay_id' => $args['pay_id'],
                    'gateway' => $args['gateway'],
                ),
                get_permalink($args['post_id'])
            ));
        endif;
        die();
    }

    public function generate_pay_id()
    {
        $id = '';
        for ($i = 0; $i < 7; $i++):
            $id .= $this->rand_to_chr(mt_rand(0, 35));
        endfor;
        return $id;
    }

    public function rand_to_chr($num)
    {
        return chr(($num < 26) ? ($num + 65) : ($num + 22));
    }

    public function verify()
    {
        $pay_id = esc_sql(sanitize_text_field($_GET['pay_id']));
        $gateway_id = sanitize_text_field($_GET['mana_gid']);
        $settings = new Common\Gateway_Settings($this->plugin_text_domain);
        $pay_info = $settings->get_options('pay_log_' . $pay_id);
        $pay_info['verify_result'] = $settings->get_gateway_by_id($gateway_id)->verify($pay_info);
        $pay_info['verify_result']['date'] = current_time('Y-m-d H:i');

        $settings->update_options(
            'pay_log_' . $pay_id,
            $pay_info
        );

        $status = $pay_info['verify_result']['status'];
        wp_redirect(add_query_arg(
            array(
                'status' => $status,
                'pay_id' => $pay_id,
                'gateway' => $gateway_id,
            ),
            get_permalink($pay_info['user_info']['post_id'])
        ));
        die();
    }

    public function check_status($post)
    {
        $status = sanitize_text_field($_GET['status']);
        $pay_id = esc_sql(sanitize_text_field($_GET['pay_id']));
        $gateway_id = sanitize_text_field($_GET['gateway']);

        if ( ! empty($status) && ! empty($pay_id)):
            $settings = new Common\Gateway_Settings($this->plugin_text_domain);
            $pay_info = $settings->get_options('pay_log_' . $pay_id);
            $gateway = $settings->get_gateway_by_id($gateway_id);
            if (isset($settings->get_options('post_price_list')[$post->ID]['price'])):
                add_filter('the_content', function () use ($gateway, $pay_info, $status) {
                    $result_html = '<div id="mana-payment-form" class="mgw-div-container-fluid">
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-2"></div>
                                    <div class="mgw-div-col-8">
                                        <p class="mgw-notice ' . (($status === 'error') ? 'mgw-error' : 'mgw-success') . '">' . $gateway->get_message($pay_info, $status) . '</p>
                                    </div>
                                    <div class="mgw-div-col-2"></div>
                                </div>
                            </div>';

                    return apply_filters($this->plugin_prefix . 'payment_result_html', $result_html);
                });
            endif;
        endif;
    }

    public function payment_form_shortcode($atts)
    {
        if (!defined('MANA_SHORT_CODE_USED')):
            define('MANA_SHORT_CODE_USED', true);

            $atts_array = $this->default_shortcode_atts(array());

            $atts = shortcode_atts(
                apply_filters($this->plugin_prefix . 'payment_shortcode_atts', $atts_array),
                $atts,
                'payment_form'
            );

            if ($atts['price'] !== 0 && $atts['price'] !== ''):
                $form_html = $this->default_shortcode_html($atts);

                return apply_filters($this->plugin_prefix . 'payment_shortcode_html', $form_html);
            else:
                return '<p style="font-size: 1.5em;"><span style="color: red;">' . __('Error', $this->plugin_text_domain) . ':</span> ' . __('Missing Required Parameter: Price', $this->plugin_text_domain) . '!</p>';
            endif;
        endif;
        return '';
    }

    public function default_shortcode_atts($atts_array)
    {
        $atts_array['price'] = 0;
        $atts_array['currency'] = '';
        return $atts_array;
    }

    public function default_shortcode_html($atts)
    {
        $settings = new Common\Gateway_Settings($this->plugin_text_domain);
        $currency = $settings->get_currencies()[$atts['currency']];
        $gateways = $settings->get_gateways();
        $gateway_order = $settings->get_gateway_order();
        $gateway_select_options = '';
        foreach ($gateway_order as $gateway_id):
            if ($gateways[$gateway_id]->get_setting('status') && $gateways[$gateway_id]->get_currency() == $atts['currency']):
                $gateway_select_options .= '<option value="' . $gateway_id . '">' . $gateways[$gateway_id]->get_title() . '</option>';
            endif;
        endforeach;
        return '<div id="mana-payment-form" class="mgw-div-container-fluid">
                    <div class="mgw-div-row">
                        <div class="mgw-div-col-2"></div>
                        <div class="mgw-div-col-8">
                            <form id="mgw-payment" method="post" style="text-align: ' . ((is_rtl()) ? 'right' : 'left') . '" action="' . admin_url('admin-post.php') . '">
                                <h3>' . __('Online Payment', $this->plugin_text_domain) . '</h3>
                                <hr style="margin-bottom: 10px;">
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-name">'
            . __('Name', $this->plugin_text_domain) . '*:' .
            '</label>
                                        <input name="name" class="text_input is_empty" type="text" id="mgw-name" value="">
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-email">'
            . __('Email', $this->plugin_text_domain) . '*:' .
            '</label>
                                        <input name="email" class="text_input is_email" type="text" id="mgw-email" placeholder="' . __('Valid Email (i.e. example@domain.com)', $this->plugin_text_domain) . '" value="">
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-mobile">'
            . __('Mobile', $this->plugin_text_domain) . '*:' .
            '</label>
                                        <input name="mobile" class="text_input is_empty is_number" type="text" id="mgw-mobile" placeholder="09xxxxxxxxx" value="">
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-phone">'
            . __('Phone', $this->plugin_text_domain) . ':' .
            '</label>
                                        <input name="phone" class="text_input is_empty is_number" type="text" id="mgw-phone" placeholder="' . __('Phone number including area code', $this->plugin_text_domain) . '" value="">
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-address">'
            . __('Address', $this->plugin_text_domain) . '*:' .
            '</label>
                                        <textarea rows="2" name="address" class="text_input is_empty" type="text" id="mgw-address" value=""></textarea>
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-comment">'
            . __('Comment', $this->plugin_text_domain) . ':' .
            '</label>
                                        <textarea rows="2" name="comment" class="text_input" type="text" id="mgw-comment" value=""></textarea>
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-price">'
            . __('Price', $this->plugin_text_domain) . ':' .
            '</label>
                                        <input name="price" class="text_input is_empty is_number" type="text" id="mgw-price" value="' . $atts['price'] . '" disabled="disabled">
                                    </div>
                                    <div class="mgw-div-col-4"><span style="margin-right: -20px; font-size: 1em; color: #e53935; font-weight: bold;">' . $currency . '</span></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <label class="mgw-label" for="mgw-gateway">'
            . __('Gateway', $this->plugin_text_domain) . ':' .
            '</label>
                                        <select name="gateway" class="select is_empty" id="mgw-gateway" >
                                        <option value="empty" selected="selected">' . __('Select...', $this->plugin_text_domain) . '</option>'
            . $gateway_select_options .
            '</select>
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                                <div class="mgw-div-row">
                                    <div class="mgw-div-col-8">
                                        <input type="hidden" name="action" value="' . $this->plugin_name . '_pay">
                                        <input type="hidden" name="post_id" value="' . get_the_ID() . '">
                                        <input type="submit" class="mgw-button" value="' . __('Pay', $this->plugin_text_domain) . '">
                                    </div>
                                    <div class="mgw-div-col-4"></div>
                                </div>
                            </form>
                        </div>
                        <div class="mgw-div-col-2"></div>
                    </div>
                </div>';
    }

}
