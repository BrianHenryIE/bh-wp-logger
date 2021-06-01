<?php
/**
 * Automatically delete old log files.
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

class Cron {

	/** @var Logger_Settings_Interface */
	protected $settings;

	/** @var API_Interface */
	protected $api;

	/**
	 * Schedule a daily cron job just after midnight.
	 *
	 * TODO: Don't enable when WooCommerce is active – it cleans up its logs automatically.
	 */
	public function register_cron_job() {

		$cron_hook = "{$this->settings->get_plugin_slug()}-logs-cleanup";

		if ( false !== wp_get_scheduled_event( $cron_hook ) ) {
			// Already scheduled.
			return;
		}

		wp_schedule_event( strtotime( 'tomorrow' ), 'daily', $cron_hook );
	}

	/**
	 * Handle the cron job
	 *
	 * @hooked plugin-slug-logs-cleanup
	 */
	public function delete_old_logs() {

		$this->api->delete_old_logs();
	}

}
