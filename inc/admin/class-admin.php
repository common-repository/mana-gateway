<?php

namespace MANA_Gateway\Inc\Admin;

use MANA_Gateway\Inc\Common;

/**
 *
 * 
 * @since      1.0.0
 *
 * @author    Your Name or Your Company
 */
class Admin
{

    /**
     * The prefix of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $plugin_prefix;
    /**
     * Base dir path
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $plugin_base_dir;
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

    private $log_table;

    /**
     * Initialize the class and set its properties.
     *
     * @since       1.0.0
     * @param       string $plugin_name The name of this plugin.
     * @param       string $version The version of this plugin.
     * @param       string $plugin_text_domain The text domain of this plugin.
     * @param       string $plugin_prefix The prefix of this plugin.
     * @param       string $plugin_base_dir Base dir path
     */
    public function __construct($plugin_name, $version, $plugin_text_domain, $plugin_prefix, $plugin_base_dir)
    {

        $this->plugin_name = $plugin_name;
        $this->version = $version;
        $this->plugin_text_domain = $plugin_text_domain;
        $this->plugin_prefix = $plugin_prefix;
        $this->plugin_base_dir = $plugin_base_dir;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style($this->plugin_name, plugin_dir_url(__FILE__) . 'css/mana-gateway-admin.css', array(), $this->version, 'all');
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        $params = array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'plugin_text_domain' => $this->plugin_text_domain,
        );
        wp_enqueue_script($this->plugin_name, plugin_dir_url(__FILE__) . 'js/mana-gateway-admin.js', array('jquery'), $this->version, false);
        wp_localize_script($this->plugin_name, 'mgw_params', $params);
    }

    public function add_admin_menus()
    {
        add_menu_page(
            __('Payment Gateway', $this->plugin_text_domain),
            __('Payment Gateway', $this->plugin_text_domain),
            'manage_options',
            $this->plugin_name,
            array($this, 'load_settings_page'),
            file_get_contents($this->plugin_base_dir . 'assets/images/credit-card.txt'),
            87
        );
        $log_menu_hook = add_submenu_page(
            $this->plugin_name,
            __('Payment Log', $this->plugin_text_domain),
            __('Payment Log', $this->plugin_text_domain),
            'manage_options',
            $this->plugin_name . '-log',
            array($this, 'load_log_page')
        );

        add_action('load-' . $log_menu_hook, array($this, 'load_log_table_screen_options'));

        add_submenu_page(
            null,
            __('Payment Log', $this->plugin_text_domain),
            __('Payment Log', $this->plugin_text_domain),
            'manage_options',
            $this->plugin_name . '-log-details',
            array($this, 'load_log_details_page')
        );
    }

    public function check_shortcode($new_status, $old_status, $post)
    {
        if ($new_status === 'publish'):
            $pattern = "/[\[]payment_form.*[\]]/";
            $content = $post->post_content;
            if (preg_match($pattern, $content)):
                $pattern = "/(?<=[\[]payment_form price=')\d+(?=' currency='.{3}'[\]])|(?<=[\[]payment_form currency='.{3}' price=')\d+(?='[\]])/";
                preg_match($pattern, $content, $price);
                $price = $price[0];
                $pattern = "/(?<=[\[]payment_form currency=').{3}(?=' price='$price'[\]])|(?<=[\[]payment_form price='$price' currency=').{3}(?='[\]])/";
                preg_match($pattern, $content, $currency);
                $currency = $currency[0];

                $settings = new Common\Gateway_Settings($this->plugin_text_domain);
                if ($list = $settings->get_options('post_price_list')):
                    $list[$post->ID] = array(
                        'price' => $price,
                        'currency' => $currency,
                    );
                else:
                    $list = array(
                        $post->ID => array(
                            'price' => $price,
                            'currency' => $currency,
                        ));
                endif;
                $settings->update_options('post_price_list', $list);
            endif;
        endif;
    }

    public function load_settings_page()
    {
        $settings = new Common\Gateway_Settings($this->plugin_text_domain);
        if (!isset($_GET['view'])):
            $gateways = $settings->get_gateways();
            $gateway_order = $settings->get_gateway_order();
            include_once('views/html-mana-gateway-admin-display.php');
        elseif (wp_verify_nonce(sanitize_text_field($_GET['nonce']), $this->plugin_text_domain . '_' . sanitize_text_field($_GET['view']))):
            $gateway_id = esc_sql(sanitize_text_field($_GET['view']));
            $currencies = $settings->get_currencies();
            $selected_currency = explode(':', $settings->get_options('currency'), 2)[0];
            $gateway = $settings->get_gateway_by_id($gateway_id);
            $fields = $gateway->fields();
            include_once('views/html-mana-gateway-manage.php');
        else:
            wp_redirect(
                add_query_arg(
                    array(
                        'page' => $this->plugin_name,
                    ),
                    admin_url('admin.php')
                )
            );
        endif;
    }

    public function load_log_table_screen_options()
    {
        $arguments = array(
            'label' => __('Logs Per Page', $this->plugin_text_domain),
            'default' => 20,
            'option' => 'logs_per_page'
        );

        add_screen_option('per_page', $arguments);

        // instantiate the User List Table
        $this->log_table = new Payment_Log_Table($this->plugin_text_domain);
    }

    public function load_log_page()
    {
        $this->log_table->prepare_items();
        include_once('views/partials-mana-item-list-table.php');
    }

    public function load_log_details_page()
    {
        if (isset($_GET['pay_id']) && wp_verify_nonce(sanitize_text_field($_GET['nonce']), $this->plugin_text_domain . '_' . sanitize_text_field($_GET['pay_id']))):
            global $wpdb;

            $table_name = $wpdb->prefix . 'mana_gateway_options';

            $pay_id = esc_sql(sanitize_text_field($_GET['pay_id']));

            $result = $wpdb->get_var(
                "SELECT option_value FROM $table_name WHERE option_name='pay_log_$pay_id';",
                0, 0
            );

            $result = unserialize(stripslashes($result));

            echo '<div class="wrap">
                    <h1 class="wp-heading-inline">'
                . __('Log Details', $this->plugin_text_domain) .
                '</h1>
                    <a href="' . add_query_arg(array('page' => $this->plugin_name . '-log'), admin_url('admin.php')) . '" class="page-title-action">'
                . __('Back', $this->plugin_text_domain) .
                '</a>
                    <hr class="wp-header-end">
                    <table class="log-details">
                        <tbody>';
            $this->print_r($result);
            echo '</tbody>
                    </table>
                </div>';
        else:
            wp_redirect(add_query_arg(array('page' => $this->plugin_name . '-log'), admin_url('admin.php')));
        endif;
    }

    public function print_r($obj, $label = '')
    {
        if ($label !== '' && $obj !== ''):
            $label_words = explode('_', $label);
            for ($i = 0; $i < count($label_words); $i++):
                $label_words[$i] = ucwords($label_words[$i]);
            endfor;
            $label = implode(' ', $label_words);
            echo '<tr><td><strong>' . $label . '</strong></td>';
        endif;
        if (is_array($obj)):
            if (count(array_filter(array_keys($obj), 'is_string')) > 0):
                foreach ($obj as $key => $element):
                    $this->print_r($element, $key);
                endforeach;
                echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            else:
                foreach ($obj as $element):
                    $this->print_r($element);
                endforeach;
                echo '<tr><td>&nbsp;</td><td>&nbsp;</td></tr>';
            endif;
        else:
            if ($obj !== ''):
                echo '<td style="padding: 0px 10px;">' . $obj . '</td></tr>';
            endif;
        endif;
    }

    public function save_gateway_settings()
    {
        if (isset($_POST['gateway_id'])):
            $settings = new Common\Gateway_Settings($this->plugin_text_domain);

            $post_data = array();

            foreach ($_POST as $key => $value):
                $post_data[sanitize_text_field($key)] = sanitize_text_field($value);
            endforeach;

            $gateway = $settings->get_gateway_by_id($post_data['gateway_id']);
            $gateway->set_settings(esc_sql($post_data));
            $settings->save_settings();
        endif;
        wp_redirect(admin_url('admin.php?page=' . $this->plugin_name));
        die();
    }

    public function toggle_gateway_status()
    {
        if (isset($_POST['id']) && wp_verify_nonce(sanitize_text_field($_POST['nonce']), $this->plugin_text_domain . '_' . sanitize_text_field($_POST['id']))):
            $settings = new Common\Gateway_Settings($this->plugin_text_domain);

            $gateway = $settings->get_gateway_by_id(sanitize_text_field($_POST['id']));
            $gateway->toggle_status();
            $settings->save_settings();
        endif;
        echo true;
        wp_die();
    }

    public function reorder_gateways()
    {
        $direction = ($_POST['direction'] === 'up' || $_POST['direction'] === 'down' )?$_POST['direction']:null;
        $index = $_POST['index'];
        if( ! is_null($direction) && is_numeric($index) ):
            $settings = new Common\Gateway_Settings($this->plugin_text_domain);
            $settings->reorder_gateways($index, (($direction == 'up') ? -1 : 1));
        endif;
        echo true;
        wp_die();
    }
}
