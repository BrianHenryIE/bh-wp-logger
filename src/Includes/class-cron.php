<?php
/**
 * Automatically delete old log files.
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

class Cron {

    use LoggerAwareTrait;

	/** @var Logger_Settings_Interface */
	protected $settings;

	/** @var API_Interface */
	protected $api;

    /**
     * @param API_Interface             $api
     * @param Logger_Settings_Interface $settings
     * @param LoggerInterface           $logger
     */
    public function __construct( $api, $settings, $logger = null ) {

        $this->logger   = $logger;
        $this->settings = $settings;
        $this->api      = $api;
    }

	/**
	 * Schedule a daily cron job just after midnight.
     *
     * @hooked plugins_loaded
	 *
	 * TODO: Don't enable when WooCommerce is active – it cleans up its logs automatically.
	 */
	public function register_cron_job() {

		$cron_hook = "delete_logs_{$this->settings->get_plugin_slug()}";

		if ( false !== wp_get_scheduled_event( $cron_hook ) ) {
			// Already scheduled.
			return;
		}

		wp_schedule_event( strtotime( 'tomorrow' ), 'daily', $cron_hook );
	}

	/**
	 * Handle the cron job.
	 *
	 * @hooked delete-logs-plugin-slug
	 */
	public function delete_old_logs() {

		$this->api->delete_old_logs();
	}

}
