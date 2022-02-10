<?php
/**
 *
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Includes\Cron;
use BrianHenryIE\WP_Logger\WooCommerce\Log_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WP_CLI;

class BH_WP_PSR_Logger implements LoggerInterface {
	use LoggerTrait;

	/** @var LoggerInterface The true logger. */
	protected LoggerInterface $real_logger;

	protected API_Interface $api;

	protected Logger_Settings_Interface $settings;

	/**
	 * If log level is 'none', use NullLogger.
	 * If Settings is WooCommerce_Logger_Interface use WC_Logger.
	 * Otherwise use KLogger.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings ) {
		$this->settings = $settings;
		$this->api      = $api;

		// This comes after the links are added, so past logs can be accessed after logging is disabled.
		if ( 'none' === $this->settings->get_log_level() ) {
			$this->real_logger = new NullLogger();
			return;
		}

		// If Settings says this is a WooCommerce plugin, and WooCommerce is active, use WC_Logger.
		// Does not use `is_plugin_active()` because "Call to undefined function" error (maybe an admin function).
		if ( $this->settings instanceof WooCommerce_Logger_Interface
			 && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			$this->real_logger = new WC_PSR_Logger( $settings->get_plugin_slug() );

			// Add context to WooCommerce logs.
			$wc_log_handler = new Log_Handler( $api, $settings );
			add_filter( 'woocommerce_format_log_entry', array( $wc_log_handler, 'add_context_to_logs' ), 10, 2 );

			// TODO: What's the log file name when it's a wc-log?

		} else {

			$log_directory       = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs' );
			$log_level_threshold = $settings->get_log_level();

			/**
			 * Add the `{context}` template string,
			 * then provide `'appendContext' => false` to Klogger (since it is already takes care of).
			 *
			 * @see \Katzgrau\KLogger\Logger::formatMessage()
			 */
			$log_format = '{date} {level} {message}';
			if ( LogLevel::DEBUG === $settings->get_log_level() ) {
				$log_format .= "\n{context}";
			}

			/**
			 * `c` is chosen to match WooCommerce's choice.
			 *
			 * @see WC_Log_Handler::format_time()
			 */
			$options = array(
				'extension'     => 'log',
				'prefix'        => "{$settings->get_plugin_slug()}-",
				'dateFormat'    => 'c',
				'logFormat'     => $log_format,
				'appendContext' => false,
			);

			$this->real_logger = new KLogger( $log_directory, $log_level_threshold, $options );

		}

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

		update_option(
			$this->settings->get_plugin_slug() . '-recent-error-data',
			array(
				'message'   => $message,
				'timestamp' => time(),
			)
		);

		$debug_backtrace            = $this->api->get_backtrace();
		$context['debug_backtrace'] = $debug_backtrace;

		// TODO: This could be useful on all logs. And the backtrace of filters rather than just the current one.
		global $wp_current_filter;
		$context['filters'] = $wp_current_filter;

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

		if ( class_exists( WP_CLI::class ) ) {
			// TODO WP_CLI::log is "encouraged", but was using an uninitialized logger variable when running in tests.
			// TODO: Add "debug" etc at the beginning to distinguish from regular CLI output.
			// maybe colorize it.
			WP_CLI::line( $message );
		}

		$context = array_merge( $context, $this->api->get_common_context() );

		// TODO: regex to replace email addresses with b**********e@gmail.com, credit card numbers etc.

		$this->real_logger->$level( $message, $context );

		update_option( $this->settings->get_plugin_slug() . '-last-log-time', time() );
	}

	/**
	 * Make the main functions available, e.g. get_log_url().
	 *
	 * @return API_Interface
	 */
	public function get_api(): API_Interface {
			return $this->api;
	}



	public function asd(): void {
		// In order to filter the logs to one request.
		$this->api->set_common_context( 'request', time() );

		// TODO: Does WooCommerce have a session id?
		if ( isset( $_COOKIE['PHPSESSID'] ) ) {
			$this->api->set_common_context( 'setssion_id', wp_unslash( $_COOKIE['PHPSESSID'] ) );
		} else {
			$this->api->set_common_context( 'setssion_id', time() );
		}
		// wp_parse_auth_cookie() ?
		// woocommerce session...

		// Add the user id to all log contexts.
		// TODO: distinguish between logged out users and system (e.g. cron) "requests".
		$current_user_id = get_current_user_id();
		if ( 0 !== $current_user_id ) {
			$this->api->set_common_context( 'user_id', $current_user_id );
		}

		$settings = $this->settings;
		/**
		 * A filter which can be run to find which plugins have a logger instantiated.
		 * Added in particular so loggers can be created for arbitrary plugins to capture their PHP errors, i.e.
		 * we want to know which plugins already have a logger so we don't interfere unnecessarily.
		 */
		add_filter(
			'bh-wp-loggers',
			function ( $loggers ) use ( $settings ) {

				// TODO: Maybe a version number here?
				$value             = array();
				$value['settings'] = $settings;
				$value['logger']   = $this;

				$loggers[ $settings->get_plugin_slug() ] = $value;

				return $loggers;
			}
		);

		add_filter(
			"bh-wp-loggers-{$settings->get_plugin_slug()}",
			function () {
				return $this;
			}
		);
	}

}
