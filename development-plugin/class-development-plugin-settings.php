<?php

namespace BH_WP_Logger_Test_Plugin;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Trait;
use Psr\Log\LogLevel;

/**
 * We should also test `WooCommerce_Logger_Settings_Interface`.
 */
class Development_Plugin_Settings implements Logger_Settings_Interface {
		use Logger_Settings_Trait;

	/**
	 * Debug for testing.
	 */
	public function get_log_level(): string {
		return LogLevel::DEBUG;
	}

	/**
	 * Should this be allowed to be specified? When fixing issues in the past, I've renamed directories to plugin-name-2
	 * etc. then activated them again and they've lived with their new "slug".
	 */
	public function get_plugin_slug(): string {
		return 'development-plugin';
	}

	/**
	 * Symlinks with Docker and symlinks inside symlinks are a problem for WordPress that no sane webserver has to
	 * deal with, but we do!
	 */
	public function get_plugin_basename(): string {
		return 'development-plugin/development-plugin.php';
	}

	/**
	 * Save a few processor cycles by defining the related plugin name rather than inferring it.
	 */
	public function get_plugin_name(): string {
		return 'BH WP Logger Test Plugin';
	}
}
