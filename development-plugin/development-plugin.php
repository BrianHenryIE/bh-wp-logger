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
 * Plugin URI:        http://github.com/username/bh-wp-logger-development-plugin/
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Brian Henry
 * Author URI:        http://example.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       bh-wp-logger-development-plugin
 * Domain Path:       /languages
 */

namespace BH_WP_Logger_Test_Plugin;

use Alley_Interactive\Autoloader\Autoloader;
use BH_WP_Logger_Test_Plugin\WP_Includes\BH_WP_Logger_Test_Plugin;
use BrianHenryIE\WP_Logger\Logger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Trait;
use BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;
use Psr\Log\LogLevel;

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/../vendor/autoload.php';

Autoloader::generate(
	__NAMESPACE__,
	__DIR__,
)->register();

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

	$logger_settings = new Development_Plugin_Settings();

	$logger = Logger::instance( $logger_settings );

	$plugin = new BH_WP_Logger_Test_Plugin( $logger_settings, $logger );

	return $plugin;
}
$GLOBALS['bh_wp_logger_test_plugin'] = instantiate_bh_wp_logger_test_plugin();

/**
 * Pass in a closure to be executed, so the backtrace will contain the plugin.
 * For integration tests.
 *
 * @param $closure
 *
 * @return void
 */
function run_closure_in_plugin( $closure ) {
	$closure();
}

add_filter(
	'plugins_url',
	fn( $url ) => str_replace( 'Users/brianhenry/Sites', 'bh-wp-logger-development-plugin/vendor/brianhenryie', $url )
);
