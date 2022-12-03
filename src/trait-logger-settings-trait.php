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
		return LogLevel::INFO;
	}

	public function get_plugin_name(): string {
		return $this->plugin_name;
	}

	public function get_plugin_slug(): string {
		return $this->plugin_slug;
	}

	public function get_plugin_basename(): string {
		return $this->plugin_basename;
	}

}
