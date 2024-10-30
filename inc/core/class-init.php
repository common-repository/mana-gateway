<?php

namespace MANA_Gateway\Inc\Core;

use MANA_Gateway as NS;
use MANA_Gateway\Inc\Admin as Admin;
use MANA_Gateway\Inc\Frontend as Frontend;

/**
 * The core plugin class.
 * Defines internationalization, admin-specific hooks, and public-facing site hooks.
 *
 * @since      1.0.0
 *
 * @author     MANA
 */
class Init
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @var      Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_base_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_base_name The string used to uniquely identify this plugin.
     */
    protected $plugin_basename;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * The text domain of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $plugin_text_domain;

    /**
     * The prefix of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     */
    protected $plugin_prefix;

    /**
     * The prefix of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     */
    protected $plugin_base_dir;

    /**
     * Initialize and define the core functionality of the plugin.
     */
    public function __construct()
    {

        $this->plugin_name = NS\PLUGIN_NAME;
        $this->version = NS\PLUGIN_VERSION;
        $this->plugin_basename = NS\PLUGIN_BASENAME;
        $this->plugin_text_domain = NS\PLUGIN_TEXT_DOMAIN;
        $this->plugin_prefix = NS\PLUGIN_PREFIX;
        $this->plugin_base_dir = NS\PLUGIN_NAME_DIR;

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();
        $this->define_public_hooks();

        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $table_name = $wpdb->prefix . 'mana_gateway_options';
        $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                  id mediumint(9) NOT NULL AUTO_INCREMENT,
                  option_name varchar(191) NOT NULL,
                  option_value longtext NOT NULL,
                  PRIMARY KEY  (id),
                  UNIQUE KEY option_name (option_name) USING BTREE
                ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

    }

    /**
     * Loads the following required dependencies for this plugin.
     *
     * - Loader - Orchestrates the hooks of the plugin.
     * - Internationalization_I18n - Defines internationalization functionality.
     * - Admin - Defines all hooks for the admin area.
     * - Frontend - Defines all hooks for the public side of the site.
     *
     * @access    private
     */
    private function load_dependencies()
    {
        $this->loader = new Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Internationalization_I18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @access    private
     */
    private function set_locale()
    {

        $plugin_i18n = new Internationalization_I18n($this->plugin_text_domain);

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @access    private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Admin\Admin($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain(), $this->get_plugin_prefix(), $this->get_plugin_base_dir());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        /*
         * Additional Hooks go here
         *
         * e.g.
         *
         * //admin menu pages
         * $this->loader->add_action('admin_menu', $plugin_admin, 'add_plugin_admin_menu');
         *
         *  //plugin action links
         * $this->loader->add_filter( 'plugin_action_links_' . $this->plugin_basename, $plugin_admin, 'add_additional_action_link' );
         *
         */
        $this->loader->add_action('admin_menu', $plugin_admin, 'add_admin_menus');
        $this->loader->add_action('wp_ajax_' . $this->plugin_text_domain . '_toggle_gateway_status', $plugin_admin, 'toggle_gateway_status');
        $this->loader->add_action('wp_ajax_' . $this->plugin_text_domain . '_reorder_gateways', $plugin_admin, 'reorder_gateways');
        $this->loader->add_action('admin_post_' . $this->plugin_text_domain . '_save_settings', $plugin_admin, 'save_gateway_settings');
        $this->loader->add_action('publish_post', $plugin_admin, 'check_shortcode', 10, 2);
        $this->loader->add_action('transition_post_status', $plugin_admin, 'check_shortcode', 10, 3);
    }

    /**
     * Register all of the hooks related to the public-facing functionality
     * of the plugin.
     *
     * @access    private
     */
    private function define_public_hooks()
    {

        $plugin_public = new Frontend\Frontend($this->get_plugin_name(), $this->get_version(), $this->get_plugin_text_domain(), $this->get_plugin_prefix());

        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $plugin_public, 'enqueue_scripts');
        $this->loader->add_action('admin_post_' . $this->plugin_text_domain . '_pay', $plugin_public, 'pay');
        $this->loader->add_action('admin_post_nopriv_' . $this->plugin_text_domain . '_pay', $plugin_public, 'pay');
        $this->loader->add_action('admin_post_' . $this->plugin_text_domain . '_verify', $plugin_public, 'verify');
        $this->loader->add_action('admin_post_nopriv_' . $this->plugin_text_domain . '_verify', $plugin_public, 'verify');
        $this->loader->add_action('the_post', $plugin_public, 'check_status');
        add_shortcode('payment_form', array($plugin_public, 'payment_form_shortcode'));

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Loader    Orchestrates the hooks of the plugin.
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @since     1.0.0
     * @return    string    The version number of the plugin.
     */
    public function get_version()
    {
        return $this->version;
    }

    /**
     * Retrieve the text domain of the plugin.
     *
     * @since     1.0.0
     * @return    string    The text domain of the plugin.
     */
    public function get_plugin_text_domain()
    {
        return $this->plugin_text_domain;
    }

    public function get_plugin_prefix()
    {
        return $this->plugin_prefix;
    }

    public function get_plugin_base_dir()
    {
        return $this->plugin_base_dir;
    }

}
