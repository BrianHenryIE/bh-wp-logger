<?php

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
