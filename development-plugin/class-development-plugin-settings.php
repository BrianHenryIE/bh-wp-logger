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

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Trait;
use Psr\Log\LogLevel;

class Development_Plugin_Settings implements Logger_Settings_Interface {
	// }, WooCommerce_Logger_Settings_Interface {
		use Logger_Settings_Trait;

	public function get_log_level(): string {
		return LogLevel::DEBUG;
	}
	public function get_plugin_slug(): string {
		return 'bh-wp-logger-development-plugin';
	}
	public function get_plugin_basename(): string {
		return 'bh-wp-logger-development-plugin/bh-wp-logger-development-plugin.php';
	}
	public function get_plugin_name(): string {
		return 'BH WP Logger Test Plugin';
	}
}
