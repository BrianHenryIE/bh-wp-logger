<?php
/**
 * Facade over a real PSR logger.
 *
 * Uses the provided settings to determine which logger to use.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\WooCommerce\Log_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Settings_Interface;
use BrianHenryIE\WP_Private_Uploads\API\Private_Uploads_Settings_Interface;
use BrianHenryIE\WP_Private_Uploads\API\Private_Uploads_Settings_Trait;
use BrianHenryIE\WP_Private_Uploads\Includes\BH_WP_Private_Uploads;
use BrianHenryIE\WP_Private_Uploads\Private_Uploads;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WP_CLI;

class BH_WP_PSR_Logger extends API implements LoggerInterface {
	use LoggerTrait;
	use LoggerAwareTrait; // To allow swapping out the logger at runtime.

	/**
	 * Return the true (proxied) logger.
	 */
	public function get_logger(): LoggerInterface {
		return $this->logger;
	}

	/**
	 * When an error is being logged, record the time of the last error, so later, an admin notice can be displayed,
	 * to inform them of the new problem.
	 *
	 * TODO: include a link to the log url so the last file with an error will be linked, rather than the most recent log file.
	 *
	 * @param string               $message The message to be logged.
	 * @param array<string, mixed> $context Data to record the system state at the time of the log.
	 */
	public function error( $message, $context = array() ) {

		// TODO: If log level is none, this will display an admin notice, but it should not.
		update_option(
			$this->settings->get_plugin_slug() . '-recent-error-data',
			array(
				'message'   => $message,
				'timestamp' => time(),
			)
		);

		$this->log( LogLevel::ERROR, $message, $context );
	}


	/**
	 * The last function in this plugin before the actual logging is delegated to KLogger/WC_Logger...
	 * * If WP_CLI is available, log to console.
	 * * If logger is not available (presumably WC_Logger not yet initialized), enqueue the log to retry on plugins_loaded.
	 * * Set WC_Logger 'source'.
	 * * Execute the actual logging command.
	 * * Record in wp_options the time of the last log.
	 *
	 * TODO: Add a filter on level.
	 *
	 * @see LogLevel
	 *
	 * @param string                   $level The log severity.
	 * @param string                   $message The message to log.
	 * @param array<int|string, mixed> $context Additional information to be logged (not saved at all log levels).
	 */
	public function log( $level, $message, $context = array() ) {

		/**
		 * TODO: Consider a WP CLI flag ~`--verbose` to turn on console logging.
		 *
		 * @see https://wordpress.stackexchange.com/questions/226152/detect-if-wp-is-running-under-wp-cli
		 */
		if ( false && defined( 'WP_CLI' ) && WP_CLI ) {

			// TODO WP_CLI::log is "encouraged", but was using an uninitialized logger variable when running in tests.
			// TODO: Add "debug" etc at the beginning to distinguish from regular CLI output.
			// maybe colorize it.
			WP_CLI::line( $message );
		}

		$context = array_merge( $context, $this->get_common_context() );

		$settings_log_level = $this->settings->get_log_level();

		if ( LogLevel::ERROR === $level ) {

			$debug_backtrace            = $this->get_backtrace();
			$context['debug_backtrace'] = $debug_backtrace;

			// TODO: This could be useful on all logs.
			global $wp_current_filter;
			$context['filters'] = $wp_current_filter;

		} elseif ( LogLevel::DEBUG === $settings_log_level ) {
			$debug_backtrace            = $this->get_backtrace( 2 );
			$context['debug_backtrace'] = $debug_backtrace;

			global $wp_current_filter;
			$context['filters'] = $wp_current_filter;
		}

		/**
		 * TODO: regex to replace email addresses with b**********e@gmail.com, credit card numbers etc.
		 * There's a PHP proposal for omitting info from logs.
		 *
		 * @see https://wiki.php.net/rfc/redact_parameters_in_back_traces
		 */

		$this->logger->$level( $message, $context );

		// When plugins.php is loaded, the logs are parsed to determine the time of the last log
		// and compared to the saved wp_option that says the last time the logs were viewed, then
		// the logs link is <b> if it is sooner. The time of the last log is saved in a transient
		// to avoid parsing the files on each load of plugins.php. When a log is written, this
		// transient is deleted. Hopefully this is the most efficient way.
		// TODO:
		// delete_transient( 'last-log-time ')
	}

}
