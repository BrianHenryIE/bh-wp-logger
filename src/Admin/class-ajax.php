<?php
/**
 * Handle AJAX requests from the log table page.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

class AJAX {

	/**
	 * Settings describing the plugin this logger is for.
	 *
	 * @uses Logger_Settings_Interface::get_plugin_slug()
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * The plugin's main functions.
	 *
	 * @uses \BrianHenryIE\WP_Logger\API\API_Interface::delete_log()
	 * @uses \BrianHenryIE\WP_Logger\API\API_Interface::delete_all_logs()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * AJAX constructor.
	 *
	 * @param API_Interface             $api Implementation of the plugin's main functions.
	 * @param Logger_Settings_Interface $settings The current settings for the logger.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings ) {
		$this->api      = $api;
		$this->settings = $settings;
	}

	/**
	 * Delete a single log file.
	 *
	 * @hooked wp_ajax_bh_wp_logger_logs_delete
	 */
	public function delete(): void {

		// bh-wp-logger could be hooked for many plugins.
		if ( ! isset( $_POST['plugin_slug'] ) || $this->settings->get_plugin_slug() !== $_POST['plugin_slug'] ) {
			return;
		}

		if ( ! isset( $_POST['date_to_delete'] ) ) {
			return;
		}

		$ymd_date = $_POST['date_to_delete'];

		$result = $this->api->delete_log( $ymd_date );

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}

	}

	/**
	 * Delete all log files for this plugin.
	 *
	 * @hooked wp_ajax_bh_wp_logger_logs_delete_all
	 */
	public function delete_all(): void {

		// bh-wp-logger could be hooked for many plugins.
		if ( ! isset( $_POST['plugin_slug'] ) || $this->settings->get_plugin_slug() !== $_POST['plugin_slug'] ) {
			return;
		}

		$result = $this->api->delete_all_logs();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}

	}

}
