<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @since             1.0.0
 * @package           MANA_Gateway
 *
 * @wordpress-plugin
 * Plugin Name:       MANA Gateway
 * Description:       This is a free extensible plugin to add online payment to your site!
 * Version:           1.0.0
 * Author:            MANA
 * Author URI:        https://profiles.wordpress.org/manaco/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       mana-gateway
 * Domain Path:       /languages
 * Domain Prefix:     mgw_
 */

namespace MANA_Gateway;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define Constants
 */

define( __NAMESPACE__ . '\NS', __NAMESPACE__ . '\\' );

define( NS . 'PLUGIN_NAME', 'mana-gateway' );

define( NS . 'PLUGIN_VERSION', '1.0.0' );

define( NS . 'PLUGIN_NAME_DIR', plugin_dir_path( __FILE__ ) );

define( NS . 'PLUGIN_NAME_URL', plugin_dir_url( __FILE__ ) );

define( NS . 'PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

define( NS . 'PLUGIN_TEXT_DOMAIN', 'mana-gateway' );

define( NS . 'PLUGIN_PREFIX', 'mgw_' );

/**
 * Autoload Classes
 */

require_once( PLUGIN_NAME_DIR . 'inc/libraries/autoloader.php' );

/**
 * Register Activation and Deactivation Hooks
 * This action is documented in inc/core/class-activator.php
 */

register_activation_hook( __FILE__, array( NS . 'Inc\Core\Activator', 'activate' ) );

/**
 * The code that runs during plugin deactivation.
 * This action is documented inc/core/class-deactivator.php
 */

register_deactivation_hook( __FILE__, array( NS . 'Inc\Core\Deactivator', 'deactivate' ) );


/**
 * Plugin Singleton Container
 *
 * Maintains a single copy of the plugin app object
 *
 * @since    1.0.0
 */
class MANA_Gateway {

	/**
	 * The instance of the plugin.
	 *
	 * @since    1.0.0
	 * @var      Init $init Instance of the plugin.
	 */
	private static $init;
	/**
	 * Loads the plugin
	 *
	 * @access    public
	 */
	public static function init() {

		if ( null === self::$init ) {
			self::$init = new Inc\Core\Init();
			self::$init->run();
		}

		return self::$init;
	}

}

/**
 * Begins execution of the plugin
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * Also returns copy of the app object so 3rd party developers
 * can interact with the plugin's hooks contained within.
 **/
function MANA_Gateway_init() {
		return MANA_Gateway::init();
}

$min_php = '5.6.0';

// Check the minimum required PHP version and run the plugin.
if ( version_compare( PHP_VERSION, $min_php, '>=' ) ) {
		MANA_Gateway_init();
}
