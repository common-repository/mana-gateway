<?php

namespace MANA_Gateway\Inc\Common;

use MANA_Gateway;

class Gateway_Base
{
    public $id;

    protected $title;

    protected $description;

    protected $settings;

    protected $currency;

    protected $callback;

    /**
     * Gateway_Base constructor.
     * @param $id
     * @param $title
     * @param $description
     * @param $settings
     * @param $currency
     */
    public function __construct($id, $title, $description, $settings, $currency)
    {
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->settings = $settings;
        if ( ! isset($settings['success_massage'])):
            $settings['success_massage'] = '';
        endif;
        if ( ! isset($settings['failed_massage'])):
            $settings['failed_massage'] = '';
        endif;
        $this->currency = $currency;
        $this->callback = add_query_arg(
            array(
                'action' => 'mana-gateway_verify',
                'mana_gid' => $id,
            ),
            admin_url('admin-post.php')
        );
    }

    /**
     * @return string
     */
    public function get_title()
    {
        return $this->title;
    }

    /**
     * @return string
     */
    public function get_description()
    {
        return $this->description;
    }

    /**
     * @return array
     */
    public function get_settings()
    {
        return $this->settings;
    }

    /**
     * @return string
     */
    public function get_currency()
    {
        return $this->currency;
    }

    public function get_setting($field)
    {
        return $this->settings[$field];
    }

    public function set_settings($settings)
    {
        foreach ($settings as $option_name => $option):
            if (isset($this->settings[$option_name])):
                settype($option, gettype($this->settings[$option_name]));
                $this->settings[$option_name] = $option;
            endif;
        endforeach;
    }

    public function get_message( $info, $status )
    {
        if ( $status === 'error'):
            $message = $this->settings['failed_massage'];
        else:
            $message = $this->settings['success_massage'];
        endif;
        return $message;
    }

    public function fields( $fields = array())
    {
        if ( ! isset($fields['currency'])):
            $fields['currency'] = array(
                'title' => sprintf(__('Supported Currency is %s.', 'mana-gateway'),$this->currency),
                'type' => 'p',
                'size' => 'large',
            );
        endif;

        return $fields;
    }

    public function toggle_status()
    {
        $this->settings['status'] = !$this->settings['status'];
    }
}