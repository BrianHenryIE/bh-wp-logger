<?php
/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://example.com
 * @since             1.0.0
 * @package           BH_WP_Logger_Test_Plugin
 *
 * @wordpress-plugin
 * Plugin Name:       BH WP Logger Test Plugin
 * Plugin URI:        http://github.com/username/bh-wp-logger-test-plugin/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Brian Henry
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-logger-test-plugin
 * Domain Path:       /languages
 */

namespace BH_WP_Logger_Test_Plugin;

use BrianHenryIE\WP_Logger\API\Logger_Settings;
use BrianHenryIE\WP_Logger\Logger;

use BH_WP_Logger_Test_Plugin\includes\BH_WP_Logger_Test_Plugin;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once plugin_dir_path( __FILE__ ) . 'autoload.php';

/**
 * Current plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'BH_WP_LOGGER_TEST_PLUGIN_VERSION', '1.0.0' );

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function instantiate_bh_wp_logger_test_plugin() {

	$logger_settings = new class( 'bh-wp-logger-test-plugin' ) extends Logger_Settings {
		public function get_log_level(): string {
			return 'debug';
		}
	};

	$logger = Logger::instance( $logger_settings );

	$plugin = new BH_WP_Logger_Test_Plugin( $logger_settings, $logger );

}
instantiate_bh_wp_logger_test_plugin();
