<?php

namespace MANA_Gateway\Inc\Common;
class Gateway_Settings
{

    protected $text_domain;

    protected $gateways;

    protected $gateway_order;

    protected $currencies;

    /**
     * Gateway_Settings constructor.
     * @param $text_domain
     */
    public function __construct($text_domain)
    {
        $this->text_domain = $text_domain;
        $this->gateways = array();
        $this->currencies = $this->default_currencies();
        $this->load_gateways();
    }

    /**
     *
     */
    public function load_gateways()
    {
        $gateway_dir = dirname(__FILE__) . '/gateways/';
        $gateway_classes = scandir($gateway_dir);
        foreach ($gateway_classes as $gateway_class):
            if ($gateway_class != '.' && $gateway_class != '..'):
                include_once($gateway_dir . $gateway_class);
            endif;
        endforeach;
        $this->add_gateway();
    }

    public function add_gateway()
    {
        $this->gateways = apply_filters($this->text_domain . '_mana_gateways', $this->gateways);

        foreach ($this->gateways as $gateway):
            $this->gateway_order[] = $gateway->id;
        endforeach;

        $this->load_settings();
    }

    /**
     * @return array
     */
    public function get_gateways()
    {
        return $this->gateways;
    }

    public function get_gateway_by_id($id)
    {
        return $this->gateways[$id];
    }

    public function get_gateway_order()
    {
        return $this->gateway_order;
    }

    public function update_options($option_name, $option_value)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mana_gateway_options';

        $option_value = addslashes(serialize($option_value));

        if (!$wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table_name WHERE option_name = %s",$option_name), 0, 0)):
            $wpdb->insert($table_name,
                array(
                    'option_name' => $option_name,
                    'option_value' => $option_value,
                )
            );
        else:

            $wpdb->update($table_name,
                array(
                    'option_value' => $option_value,
                ),
                array(
                    'option_name' => $option_name,
                )
            );
        endif;
    }

    public function get_options($option_name)
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'mana_gateway_options';

        return unserialize(stripslashes($wpdb->get_var($wpdb->prepare("SELECT option_value FROM $table_name WHERE option_name = %s",$option_name), 0, 0)));
    }

    public function save_settings()
    {
        $this->update_options(
            'settings',
            array($this->gateways, $this->gateway_order)
        );
    }

    public function load_settings()
    {
        if ($settings = $this->get_options('settings')):
            foreach ($settings[1] as $order):
                if (!is_null($this->gateways[$order]) && ! in_array($order,$this->gateway_order)):
                    $this->gateway_order[] = $order;
                endif;
            endforeach;
            foreach ($settings[0] as $gateway):
                if (!is_null($this->gateways[$gateway->id])):
                    $this->gateways[$gateway->id]->set_settings($gateway->get_settings());
                endif;
            endforeach;
        endif;
        $this->save_settings();
    }

    public function reorder_gateways($index, $other)
    {
        $temp = $this->gateway_order[$index];
        $this->gateway_order[$index] = $this->gateway_order[$index + $other];
        $this->gateway_order[$index + $other] = $temp;
        $this->save_settings();
    }

    public static function register_gateway($gateway_class)
    {
        add_filter('mana-gateway_mana_gateways', function ($gateways) use ($gateway_class) {
            $obj = new $gateway_class();
            $gateways[$obj->id] = $obj;
            return $gateways;
        });
    }

    private function default_currencies()
    {
        return array(
            'IRR' => __('Rials', $this->text_domain),
            'USD' => __('Dollars', $this->text_domain),
        );
    }

    public function get_currencies()
    {
        return $this->currencies;
    }
}