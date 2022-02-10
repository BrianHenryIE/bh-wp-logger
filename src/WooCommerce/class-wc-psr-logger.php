<?php
/**
 * A PSR wrapper for WC_Logger.
 *
 * Attempts to run wc_get_logger(), if unavailable, tries again on `woocommerce_loaded` hook.
 * If a message is logged before then, it is queued for later.
 *
 * WooCommerce takes care of deleting old logs.
 * Log files are visible inside the WooCommerce logs viewer.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WooCommerce;

use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use WC_Log_Levels;
use WC_Logger_Interface;

/**
 * PSR LoggerInterface passing log messages to wc_logger().
 *
 * @see wc_get_logger()
 * @see \WC_Logger
 */
class WC_PSR_Logger implements LoggerInterface {
	use LoggerTrait;

	/**
	 * The entire settings object is preserved so a filter can be added to change the log level during runtime.
	 *
	 * @uses \BrianHenryIE\WP_Logger\API\Logger_Settings_Interface::get_log_level()
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * The wc_logger instance that takes care of saving the logs.
	 *
	 * @var WC_Logger_Interface|\WC_Logger|null
	 */
	protected ?WC_Logger_Interface $wc_logger = null;

	/**
	 * Wraps wc_logger in a PSR compatible logger.
	 *
	 * Attempts to instantiate a wc_logger immediately. if it is too early, adds an action to do so
	 * after WooCommerce has loaded.
	 *
	 * @param Logger_Settings_Interface $settings The logger settings to get the plugin slug and log level.
	 */
	public function __construct( Logger_Settings_Interface $settings ) {

		$this->settings = $settings;

		if ( ! did_action( 'woocommerce_loaded' ) ) {

			$instantiate_wc_logger = function (): void {
				$this->wc_logger = wc_get_logger();
			};
			add_action( 'woocommerce_loaded', $instantiate_wc_logger, 1 );
		} else {
			$this->wc_logger = wc_get_logger();
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * If woocommerce_loaded hook has not run, a closure is added and executed later to record the log.
	 *
	 * @see WC_Log_Levels
	 * @see \PSR\Log\LogLevel
	 *
	 * @param string  $level The severity.
	 * @param string  $message The human-readable message.
	 * @param mixed[] $context An array of data to be logged alongside the message.
	 *
	 * @return void
	 */
	public function log( $level, $message, array $context = array() ) {

		// If this is true, WooCommerce is known to be active, but we are before woocommerce_loaded.
		if ( ! isset( $this->wc_logger ) ) {

			// Re-run this same function after the logger has been initialized.
			$re_run_log = function() use ( $level, $message, $context ) {
				$this->log( $level, $message, $context );
			};
			add_action( 'woocommerce_loaded', $re_run_log, 2 );

			return;
		}

		$log_level = $this->settings->get_log_level();

		if ( WC_Log_Levels::get_level_severity( $level ) < WC_Log_Levels::get_level_severity( $log_level ) ) {
			return;
		}

		$context['source'] = $this->settings->get_plugin_slug();

		$this->wc_logger->$level( $message, $context );
	}
}
