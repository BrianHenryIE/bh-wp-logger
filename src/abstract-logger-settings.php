<?php
/**
 * To provide defaults.
 */

namespace BrianHenryIE\WP_Logger;

use Psr\Log\LogLevel;

abstract class Logger_Settings_Abstract implements Logger_Settings_Interface {

	protected $plugin_slug;

	public function __construct( $plugin_slug = null ) {
		$this->plugin_slug = $plugin_slug;
	}

	abstract public function get_plugin_slug(): string;

	public function get_log_level(): string {
		return LogLevel::NOTICE;
	}
}
