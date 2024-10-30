<?php

namespace MANA_Gateway\Inc\Admin;

use MANA_Gateway\Inc\Libraries;
use MANA_Gateway\Inc\Common;

class Payment_Log_Table extends Libraries\WP_List_Table
{
    protected $plugin_text_domain;

    /*
	 * Call the parent constructor to override the defaults $args
	 *
	 * @param string $plugin_text_domain	Text domain of the plugin.
	 *
	 * @since 1.0.0
	 */
    public function __construct($plugin_text_domain)
    {
        $this->plugin_text_domain = $plugin_text_domain;

        parent::__construct(array(
            'plural' => 'logs',    // Plural value used for labels and the objects being listed.
            'singular' => 'log',        // Singular label for an object being listed, e.g. 'post'.
            'ajax' => false,        // If true, the parent class will call the _js_vars() method in the footer
        ));
    }

    /**
     * Prepares the list of items for displaying.
     *
     * Query, filter data, handle sorting, and pagination, and any other data-manipulation required prior to rendering
     *
     * @since   1.0.0
     */
    public function prepare_items()
    {

        // check if a search was performed.
        $table_search_key = isset($_REQUEST['s']) ? wp_unslash(trim($_REQUEST['s'])) : '';

        $this->_column_headers = $this->get_column_info();

        // check and process any actions such as bulk actions.
        $this->handle_table_actions();

        // fetch table data
        $table_data = $this->fetch_table_data();
        // filter the data in case of a search.
        if ($table_search_key) {
            $table_data = $this->filter_table_data($table_data, $table_search_key);
        }

        // required for pagination
        $logs_per_page = $this->get_items_per_page('logs_per_page');
        $table_page = $this->get_pagenum();

        // provide the ordered data to the List Table.
        // we need to manually slice the data based on the current pagination.

        $this->items = array_slice($table_data, (($table_page - 1) * $logs_per_page), $logs_per_page);

        // set the pagination arguments
        $total_logs = count($table_data);
        $this->set_pagination_args(array(
            'total_items' => $total_logs,
            'per_page' => $logs_per_page,
            'total_pages' => ceil($total_logs / $logs_per_page)
        ));
    }

    /**
     * Get a list of columns. The format is:
     * 'internal-name' => 'Title'
     *
     * @since 1.0.0
     *
     * @return array
     */
    public function get_columns()
    {

        $table_columns = array(
            'date' => __('Date', $this->plugin_text_domain),
            'pay_id' => __('Pay ID', $this->plugin_text_domain),
            'price' => __('Price', $this->plugin_text_domain),
            'payer' => __('Payer Name', $this->plugin_text_domain),
            'payer_info' => __('Payer Info', $this->plugin_text_domain),
            'gateway' => __('Gateway', $this->plugin_text_domain),
            'post_id' => __('From Page', $this->plugin_text_domain),
            'status' => __('Status', $this->plugin_text_domain),
            'log_details' => __('Log Details', $this->plugin_text_domain),
        );

        return $table_columns;

    }

    /**
     * Get a list of sortable columns. The format is:
     * 'internal-name' => 'orderby'
     * or
     * 'internal-name' => array( 'orderby', true )
     *
     * The second format will make the initial sorting order be descending
     *
     * @since 1.1.0
     *
     * @return array
     */
    protected function get_sortable_columns()
    {

        /*
         * actual sorting still needs to be done by prepare_items.
         * specify which columns should have the sort icon.
         *
         * key => value
         * column name_in_list_table => columnname in the db
         */
        $sortable_columns = array(
            'date' => 'date',
            'price' => 'price',
            'gateway' => 'gateway',
            'status' => 'status',
        );

        return $sortable_columns;
    }

    /**
     * Text displayed when no user data is available
     *
     * @since   1.0.0
     *
     * @return void
     */
    public function no_items()
    {
        _e('No logs available.', $this->plugin_text_domain);
    }

    /*
	 * Fetch table data from the WordPress database.
	 *
	 * @since 1.0.0
	 *
	 * @return	Array
	 */

    /**
     * @return array
     */
    public function fetch_table_data()
    {
        $orderby = (isset($_GET['orderby'])) ? esc_sql(sanitize_text_field($_GET['orderby'])) : 'date';
        $order = (isset($_GET['order'])) ? esc_sql(sanitize_text_field($_GET['order'])) : 'asc';
        $filter = (isset($_GET['filter'])) ? esc_sql(sanitize_text_field($_GET['filter'])) : '';

        // return result array to prepare_items.
        global $wpdb;

        $table_name = $wpdb->prefix . 'mana_gateway_options';

        $result = $wpdb->get_col(
            "SELECT option_value FROM $table_name WHERE option_name like 'pay_log_%';",
            0
        );
        $data_result = array();
        foreach ($result as $row):
            $row = unserialize(stripslashes($row));
            $data_row = $this->fill_row($row);
            if ($filter === ''):
                $data_result[] = $data_row;
            elseif ($filter === 'success' && $data_row['status'] === 'success'):
                $data_result[] = $data_row;
            elseif ($filter === 'error' && $data_row['status'] === 'error'):
                $data_result[] = $data_row;
            endif;
        endforeach;
        if ($order === 'asc'):
            usort($data_result, function ($element1, $element2) use ($orderby) {
                return ($element1[$orderby] > $element2[$orderby]);
            });
        else:
            usort($data_result, function ($element1, $element2) use ($orderby) {
                return ($element1[$orderby] < $element2[$orderby]);
            });
        endif;
        return $data_result;
    }

    public function fill_row( $row )
    {
        $data_row = array();
        if (isset($row['verify_result'])):
            $data_row['date'] = $row['verify_result']['date'];
            $data_row['status'] = $row['verify_result']['status'];
        else:
            $data_row['date'] = $row['user_info']['date'];
            $data_row['status'] = $row['request_result']['status'];
        endif;
        $data_row['pay_id'] = $row['user_info']['pay_id'];
        $data_row['price'] = $row['user_info']['price'] . ' ' . $row['user_info']['currency'];
        $data_row['payer'] = $row['user_info']['name'];
        $data_row['payer_info'] =
            __('Email', $this->plugin_text_domain) . ': ' . $row['user_info']['email'] . "<br>" .
            __('Mobile', $this->plugin_text_domain) . ': ' . $row['user_info']['mobile'] . "<br>" .
            (($row['user_info']['phone'] !== '') ?
                __('Phone', $this->plugin_text_domain) . ': ' . $row['user_info']['phone'] . "<br>" : '') .
            __('Address', $this->plugin_text_domain) . ': ' . $row['user_info']['address'] . "<br>" .
            (($row['user_info']['comment'] !== '') ?
                __('Comment', $this->plugin_text_domain) . ': ' . $row['user_info']['comment'] . "<br>" : '') .
            ((isset($row['user_info']['user'])) ?
                __('User', $this->plugin_text_domain) . ': ' . get_user_by('id', $row['user_info']['user'])->first_name . ' ' . get_user_by('id', $row['user_info']['user'])->last_name . "<br>" : '');
        if (strrpos($data_row['payer_info'], "<br>") == (strlen($data_row['payer_info']) - 4)):
            $data_row['payer_info'] = substr($data_row['payer_info'], 0, -4);
        endif;
        $data_row['gateway'] = $row['user_info']['gateway_name'];
        $data_row['post_id'] = $row['user_info']['post_id'];

        return $data_row;
    }

    /*
	 * Filter the table data based on the user search key
	 *
	 * @since 1.0.0
	 *
	 * @param array $table_data
	 * @param string $search_key
	 * @returns array
	 */
    public function filter_table_data($table_data, $search_key)
    {
        $filtered_table_data = array_values(array_filter($table_data, function ($row) use ($search_key) {
            foreach ($row as $row_val) {
                if (stripos($row_val, $search_key) !== false) {
                    return true;
                }
            }
            return false;
        }));

        return $filtered_table_data;

    }

    /**
     * Render a column when no column specific method exists.
     *
     * @param array $item
     * @param string $column_name
     *
     * @return mixed
     */
    public function column_default($item, $column_name)
    {

        return $item[$column_name];
    }

    /**
     * @param $item
     * @return string
     */
    protected function column_post_id($item)
    {
        return '<a href="' . get_permalink($item['post_id']) . '"><strong>' . get_the_title($item['post_id']) . '</strong></a>';
    }

    /**
     * @param $item
     * @return string
     */
    protected function column_payer_info($item)
    {
        return '<div class="mgw-set">
                    <a href="#">' . __('Details', $this->plugin_text_domain) . '<i style="' . ((is_rtl()) ? 'float: left;' : 'float: right;') . '"><strong>+</strong></i></a>
                    <div class="mgw-content">
                        <p>' . $item['payer_info'] . '</p>
                    </div>
                </div>';
    }

    protected function column_status($item)
    {
        if ($item['status'] === 'error'):
            return '<span class="dashicons dashicons-no-alt" style="color: red" title="' . __('Failed', $this->plugin_text_domain) . '"></span>';
        else:
            return '<span class="dashicons dashicons-yes" style="color: darkgreen" title="' . __('Success', $this->plugin_text_domain) . '"></span>';
        endif;
    }

    protected function column_log_details($item)
    {
        return '<a class="mgw-button-primary" href="' . add_query_arg(array('page' => $this->plugin_text_domain . '-log-details', 'pay_id' => $item['pay_id'], 'nonce' => wp_create_nonce($this->plugin_text_domain . '_' . $item['pay_id'])), admin_url('admin.php')) . '">' . __('Details', $this->plugin_text_domain) . '</a>';
    }
}