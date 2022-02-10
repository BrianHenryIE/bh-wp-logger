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
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
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

class BH_WP_PSR_Logger implements LoggerInterface {
	use LoggerTrait;
	use LoggerAwareTrait; // To allow swapping out the logger at runtime.

	/**
	 * The true logger.
	 *
	 * @var LoggerInterface $logger
	 */
	protected $logger;

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
			$this->logger = new NullLogger();
			return;
		}

		// If Settings says this is a WooCommerce plugin, and WooCommerce is active, use WC_Logger.
		// Does not use `is_plugin_active()` because "Call to undefined function" error (maybe an admin function).
		if ( $this->settings instanceof WooCommerce_Logger_Interface
			 && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			$this->logger = new WC_PSR_Logger( $settings );

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

			$this->logger = new KLogger( $log_directory, $log_level_threshold, $options );

			// Make the logs directory inaccessible to the public.
			$private_uploads_settings = new class( $settings ) implements Private_Uploads_Settings_Interface {
				use Private_Uploads_Settings_Trait;

				protected Logger_Settings_Interface $logger_settings;

				public function __construct( Logger_Settings_Interface $logger_settings ) {
					$this->logger_settings = $logger_settings;
				}

				public function get_plugin_slug(): string {
					return $this->logger_settings->get_plugin_slug() . '_logger';
				}

				/**
				 * Use wp-content/uploads/logs as the logs directory.
				 */
				public function get_uploads_subdirectory_name(): string {
					return 'logs';
				}
			};

			// Don't use the Private_Uploads singleton in case the parent plugin also needs it.
			$private_uploads = new Private_Uploads( $private_uploads_settings, $this );
			new BH_WP_Private_Uploads( $private_uploads, $private_uploads_settings, $this );

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

		$this->logger->$level( $message, $context );

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

	/**
	 * Return the true (proxied) logger.
	 */
	public function get_logger(): LoggerInterface {
		return $this->logger;
	}

}
