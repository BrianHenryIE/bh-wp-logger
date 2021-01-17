<?php
/**
 * Catches PHP warning, deprecated etc. which are then checked to see if they were raised by the
 * plugin the logger is for, in which case they are logged to its log file.
 */

namespace BrianHenryIE\WP_Logger\api;

use BrianHenryIE\WP_Logger\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class PHP_Error_Handler {

	/** @var LoggerInterface */
	protected $logger;

	/** @var Logger_Settings_Interface */
	protected $settings;

	public function __construct( $settings, $logger = null ) {

		$this->logger   = $logger ?? new NullLogger();
		$this->settings = $settings;
	}

	/**
	 * Since this is hooked on plugins_loaded, it won't display errors that occur as plugins' constructors run.
	 *
	 * But since it's hooked on plugins_loaded (as distinct from just initializing), it allows other plugins to
	 * hook error handlers in before or after this one.
	 *
	 * @hooked plugins_loaded
	 */
	public function init() {

		// TODO: Don't subscribe to compiler and parse errors etc.

		$this->logger = Logger::instance();

		return set_error_handler(
			array( $this, 'admin_alert_errors' ),
			E_ALL
		);
	}

	/**
	 * The error handler itself.
	 *
	 * @see set_error_handler()
	 *
	 * @param int    $errno the error code (the level of the error raised, as an integer)
	 * @param string $errstr a string describing the error
	 * @param string $errfile the filename in which the error occurred
	 * @param int    $errline the line number in which the error occurred
	 *
	 * @return bool True if error had been handled and no more handling to do, false to pass the error on.
	 */
	public function admin_alert_errors( $errno, $errstr, $errfile = null, $errline = null ) {

		// TODO: Logic to check is the error releveant to this plugin.
		$plugin_error = false;

		if ( ! is_null( $errfile ) && strstr( $errfile, $this->settings->get_plugin_slug() ) ) {
			$plugin_error = true;
		}

		if ( ! is_null( $errfile ) ) {
			$plugin_helper = new Plugin_Helper();

			$error_plugin_dir = $plugin_helper->discover_plugin_basename( $errfile );
			$this_plugin_dir  = $this->settings->get_plugin_basename();

			if ( $error_plugin_dir === $this_plugin_dir ) {
				$plugin_error = true;
			}
		}

		if ( ! $plugin_error ) {
			/* Execute PHP internal error handler */
			return false;
		}

		// TODO: transients to stop logging the same thing repeatedly.

		// foreach ( $this->settings->get_debug_backtrace_regexes() as $regex ) {
		//
		// if ( 1 === preg_match( $regex, $errstr ) ) {
		// ob_start();
		// debug_print_backtrace();
		//
		// error_log( ob_get_clean() );
		// trigger_error( ob_get_clean() );
		//
		// }
		// }
		//
		// foreach ( $this->settings->get_ignore_regexes() as $regex ) {
		//
		// if ( 1 === preg_match( $regex, $errstr ) ) {
		//
		// return true;
		// }
		// }

		// TODO: Don't log repeated messages.

		// TODO: Add line numbers...
		$context = array(
			'file' => $errfile,
			'line' => $errline
		);

		$log_level = $this->errno_to_psr3( $errno );

		$this->logger->$log_level( $errstr, $context );

		/*
		 Don't execute PHP internal error handler */
		// TODO: return false for fatal etc.
		return true;
	}

	/**
	 * Maps PHP's error types to PSR-3's error levels.
	 *
	 * Some of these will never occur at runtime.
	 *
	 * @see trigger_error()
	 *
	 * @param int $errno
	 *
	 * @return string
	 */
	protected function errno_to_psr3( $errno ) {

		$errorType = array(
			E_ERROR             => LogLevel::ERROR,
			E_CORE_ERROR        => LogLevel::ERROR,
			E_COMPILE_ERROR     => LogLevel::ERROR,
			E_USER_ERROR        => LogLevel::ERROR, // User-generated error message  – trigger_error().
			E_RECOVERABLE_ERROR => LogLevel::ERROR,
			E_WARNING           => LogLevel::WARNING,
			E_CORE_WARNING      => LogLevel::WARNING, // Warnings (non-fatal errors) that occur during PHP's initial startup.
			E_COMPILE_WARNING   => LogLevel::WARNING, // Compile-time warnings.
			E_USER_WARNING      => LogLevel::WARNING, // User-generated warning message – trigger_error().
			E_NOTICE            => LogLevel::NOTICE,
			E_USER_NOTICE       => LogLevel::NOTICE,
			E_DEPRECATED        => LogLevel::DEBUG,
			E_USER_DEPRECATED   => LogLevel::DEBUG, // User-generated warning message – trigger_error().
			E_PARSE             => LogLevel::ERROR, // Compile-time parse errors.
		);

		if ( array_key_exists( $errno, $errorType ) ) {
			return $errorType[ $errno ];
		} else {
			return LogLevel::ERROR;
		}

	}
}
