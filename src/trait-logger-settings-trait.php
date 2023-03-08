<?php
/**
 * For the Settings class to implement.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use Psr\Log\LogLevel;

/**
 * @see Logger_Settings_Interface
 */
trait Logger_Settings_Trait {

	/**
	 * @see LogLevel
	 *
	 * @var string
	 */
	public function get_log_level(): string {
		return get_option( $this->get_plugin_slug(). '_log_level', LogLevel::INFO );
	}

	public function get_plugin_name(): string {
		return get_plugins()[$this->get_plugin_basename()]['Name'];
	}

	public function get_plugin_slug(): string {
		return explode( '/', $this->get_plugin_basename() )[0];
	}

	public function get_plugin_basename(): string {
		return $this->plugin_basename;
	}

}
