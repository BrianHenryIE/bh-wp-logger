<?php
/**
 * Automatically delete old log files.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Functions to register the cron job and handle the action to execute the job to delete old logs.
 */
class Cron {

	use LoggerAwareTrait;

	/**
	 * The logger settings are used to determine which plugin we're working with.
	 *
	 * @see Logger_Settings_Interface::get_plugin_slug()
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * The API instance will delete the old logs.
	 *
	 * @see API_Interface::delete_old_logs()
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Cron constructor.
	 *
	 * @param API_Interface             $api The logger's main functions.
	 * @param Logger_Settings_Interface $settings The logger settings.
	 * @param BH_WP_PSR_Logger          $logger The logger itself for logging.
	 */
	public function __construct( $api, $settings, BH_WP_PSR_Logger $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Schedule a daily cron job to delete old logs, just after midnight.
	 *
	 * Does not schedule the cleanup if it is a WooCommerce logger (since WooCommerce handles that itself).
	 *
	 * @hooked init
	 */
	public function register_cron_job(): void {

		/**
		 * Cast the logger to the logger facade so we can access the true logger itself.
		 *
		 * @var BH_WP_PSR_Logger $bh_wp_psr_logger
		 */
		$bh_wp_psr_logger = $this->logger;
		$logger           = $bh_wp_psr_logger->get_logger();

		if ( $logger instanceof WC_PSR_Logger ) {
			return;
		}

		$cron_hook = "delete_logs_{$this->settings->get_plugin_slug()}";

		if ( false !== wp_get_scheduled_event( $cron_hook ) ) {
			return;
		}

		wp_schedule_event( strtotime( 'tomorrow' ), 'daily', $cron_hook );

		$this->logger->debug( "Registered the `{$cron_hook}` cron job." );
	}

	/**
	 * Handle the cron job.
	 *
	 * @hooked delete_logs_{plugin-slug}
	 */
	public function delete_old_logs(): void {
		$action = current_action();
		$this->logger->debug( "Executing {$action} cron job." );

		$this->api->delete_old_logs();
	}

}
