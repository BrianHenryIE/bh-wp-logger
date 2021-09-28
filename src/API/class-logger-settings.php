<?php
/**
 * To provide defaults.
 */

namespace BrianHenryIE\WP_Logger\API;

use Psr\Log\LogLevel;

class Logger_Settings implements Logger_Settings_Interface {

	/**
	 * get_plugins() with added `basename` key.
	 *
	 * @see get_plugin_data()
	 *
	 * @var array|null
	 */
	protected $plugin_data;

	public function __construct( $plugin_slug = null ) {

		if ( ! is_null( $plugin_slug ) ) {
			$this->plugin_data = ( new Plugin_Helper() )->get_plugin_data_from_slug( $plugin_slug );
		} else {
			$this->plugin_data = ( new Plugin_Helper() )->discover_plugin_data();
		}
	}

	public function get_log_level(): string {
		return LogLevel::NOTICE;
	}

	public function get_plugin_slug(): string {
		if ( is_null( $this->plugin_data ) ) {
			$this->plugin_data = ( new Plugin_Helper() )->discover_plugin_data();
		}
		return $this->plugin_data['TextDomain'];
	}

	public function get_plugin_name(): string {
		return $this->plugin_data['Name'];
	}

	public function get_plugin_basename(): string {
		return $this->plugin_data['basename'];
	}

}
