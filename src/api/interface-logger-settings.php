<?php

namespace BrianHenryIE\WP_Logger\api;

use Psr\Log\LogLevel;

interface Logger_Settings_Interface {

	/**
	 * @see LogLevel
	 *
	 * @return string
	 */
	public function get_log_level(): string;

	/**
	 * For friendly display.
	 *
	 * @return string
	 */
	public function get_plugin_name(): string;

	/**
	 * For filenames and URLs.
	 *
	 * @return string
	 */
	public function get_plugin_slug(): string;

	public function get_plugin_basename(): string;

}
