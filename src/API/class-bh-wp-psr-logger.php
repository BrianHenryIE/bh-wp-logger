<?php
/**
 * Facade over a real PSR logger.
 *
 * Uses the provided settings to determine which logger to use.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use WP_CLI;

/**
 * Functions to add context to logs and to record the time of logs.
 */
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

		$context = array_merge( $context, $this->get_common_context() );

		$settings_log_level = $this->settings->get_log_level();

		if ( LogLevel::ERROR === $level ) {

			$debug_backtrace            = $this->get_backtrace();
			$context['debug_backtrace'] = $debug_backtrace;

			// TODO: This could be useful on all logs.
			global $wp_current_filter;
			$context['filters'] = $wp_current_filter;

		} elseif ( LogLevel::WARNING === $level || LogLevel::DEBUG === $settings_log_level ) {
			$debug_backtrace            = $this->get_backtrace( 2 );
			$context['debug_backtrace'] = $debug_backtrace;

			global $wp_current_filter;
			$context['filters'] = $wp_current_filter;
		}

		if ( isset( $context['exception'] ) && $context['exception'] instanceof \Exception ) {
			$exception                    = $context['exception'];
			$exception_details            = array();
			$exception_details['class']   = get_class( $exception );
			$exception_details['message'] = $exception->getMessage();

			// TODO: Use reflection to log properties of Exception subclasses.

			$context['exception'] = $exception_details;
		}

		/**
		 * TODO: regex to replace email addresses with b**********e@gmail.com, credit card numbers etc.
		 * There's a PHP proposal for omitting info from logs.
		 *
		 * @see https://wiki.php.net/rfc/redact_parameters_in_back_traces
		 */

		$log_data         = array(
			'level'   => $level,
			'message' => $message,
			'context' => $context,
		);
		$settings         = $this->settings;
		$bh_wp_psr_logger = $this;

		/**
		 * Filter to modify the log data.
		 * Return null to cancel logging this message.
		 *
		 * @pararm array{level:string,message:string,context:array} $log_data
		 * @param Logger_Settings_Interface $settings
		 * @param BH_WP_PSR_Logger $bh_wp_psr_logger
		 */
		$log_data = apply_filters( $this->settings->get_plugin_slug() . '_bh_wp_logger_log', $log_data, $settings, $bh_wp_psr_logger );

		if ( empty( $log_data ) ) {
			return;
		}

		list( $level, $message, $context ) = array_values( $log_data );

		/**
		 * When WP CLI commands are appended with `--debug` or more specifically `--debug=plugin-slug` all messages will be output.
		 *
		 * @see https://wordpress.stackexchange.com/questions/226152/detect-if-wp-is-running-under-wp-cli
		 */
		if ( defined( 'WP_CLI' ) && WP_CLI ) {

			WP_CLI::debug( $message, $this->settings->get_plugin_slug() );

			switch ( $level ) {
				case LogLevel::DEBUG:
					// Addressed above. Allow WP_CLI to decide if it is printed.
					break;
				case LogLevel::INFO:
					WP_CLI::line( $message );
					break;
				case LogLevel::NOTICE:
					WP_CLI::line( WP_CLI::colorize( '%bNotice:%n  ' . $message ) );
					break;
				case LogLevel::WARNING:
					WP_CLI::line( WP_CLI::colorize( '%yWarning:%n ' . $message ) );
					break;
				case LogLevel::ERROR:
					WP_CLI::line( WP_CLI::colorize( '%rError:%n   ' . $message ) );
					break;
				default:
					WP_CLI::error_multi_line( array( ucfirst( $level ) . ': ' . $message ) );
			}
		}

		$this->logger->$level( $message, $context );

		// We store the last log time in a transient to avoid reading the file from disk. When a new log is written,
		// that transient is expired. TODO: We're deleting here on the assumption deleting is more performant than writing
		// the new value. This could also be run only in WordPress's 'shutdown' action.
		delete_transient( $this->get_last_log_time_transient_name() );
	}

}
