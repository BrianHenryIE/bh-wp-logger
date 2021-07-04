<?php

namespace BrianHenryIE\WP_Logger\API;

use Psr\Log\LogLevel;

interface Logger_Settings_Interface {

	/**
	 * @see LogLevel
	 *
	 * @return string
	 */
	public function get_log_level(): string;

	/**
	 * Plugin name for use by the logger in friendly messages printed to WordPress admin UI.
	 *
	 * @see Logger
	 *
	 * @return string
	 */
	public function get_plugin_name(): string;

	/**
	 * The plugin slug is used by the logger in file and URL paths.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string;

	/**
	 * The plugin basename is used by the logger to add the plugins page action link.
	 * (and maybe for PHP errors)
	 *
	 * @see Logger
	 *
	 * @return string
	 */
	public function get_plugin_basename(): string;

}
