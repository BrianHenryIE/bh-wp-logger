<?php
/**
 *
 *
 * @package    BrianHenryIE\WP_Plugin_Logger
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\WP_Logger\Admin\Admin;
use BrianHenryIE\WP_Logger\Admin\Admin_Notices;
use BrianHenryIE\WP_Logger\Admin\AJAX;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\API\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\Log_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WC_Logger_Interface;
use WP_CLI;

/**
 *
 * @see WC_Logger
 * @see https://www.php-fig.org/psr/psr-3/
 */
class BH_WP_Logger extends AbstractLogger {

	/** @var WC_Logger_Interface|LoggerInterface The true logger, once available. */
	protected $logger;

	protected Logger_Settings_Interface $settings;

	protected API_Interface $api;

	/**
	 * Logger constructor.
	 *
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings ) {

		$this->settings = $settings;
		$this->api      = $api;

		// TODO: Option to disable.

		$logs_page = new Logs_Page( $api, $settings );
		add_action( 'admin_menu', array( $logs_page, 'add_page' ) );
		add_action( 'admin_footer', array( $logs_page, 'print_css' ) );

		$admin = new Admin( $settings );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );

		$ajax = new AJAX( $api, $settings );
		add_action( 'wp_ajax_bh_wp_logger_logs_delete', array( $ajax, 'delete' ) );
		add_action( 'wp_ajax_bh_wp_logger_logs_delete_all', array( $ajax, 'delete_all' ) );

		$admin_notices = new Admin_Notices( $api, $settings );
		add_action( 'admin_init', array( $admin_notices, 'admin_notices' ) );

		// TODO: This is not always correct.
		$plugins_page = new Plugins_Page( $api, $settings );
		$hook         = "plugin_action_links_{$this->settings->get_plugin_basename()}";
		add_filter( $hook, array( $plugins_page, 'display_plugin_action_links' ), 10, 4 );

		$php_error_handler = new PHP_Error_Handler( $api, $settings, $this );
		add_action( 'plugins_loaded', array( $php_error_handler, 'init' ), 2 );

		// This comes after the links are added, so past logs can be accessed after logging is disabled.
		if ( 'none' === $settings->get_log_level() ) {
			$this->logger = new NullLogger();
			return;
		}

		// In order to filter the logs to one request.
		$this->api->set_common_context( 'request', time() );

		// TODO: Does WooCommerce have a session id?
		if ( isset( $_COOKIE['PHPSESSID'] ) ) {
			$this->api->set_common_context( 'setssion_id', $_COOKIE['PHPSESSID'] );
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

		// If Settings says this is a WooCommerce plugin, and WooCommerce is active, use WC_Logger.
		// Does not use `is_plugin_active()` because "Call to undefined function" error.
		if ( $this->settings instanceof WooCommerce_Logger_Interface
			 && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ), true ) ) {

			/**
			 * `wc_get_logger()` gets a global logger, so instantiate one for this plugin so its log leve can be set
			 * independently.
			 *
			 * PSR log level strings conveniently match WooCommerce log level threshold string.
			 *
			 * @see WC_Log_Levels
			 */
			$instantiate_wc_logger = function() use ( $settings ): void {
				$log_level_threshold = $settings->get_log_level();
				$this->logger        = new \WC_Logger( null, $log_level_threshold );
			};
			add_action( 'plugins_loaded', $instantiate_wc_logger, 1 );

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

			$options = array(
				'extension'     => 'log',
				'prefix'        => "{$settings->get_plugin_slug()}-",
				'dateFormat'    => 'c', // @see WC_Log_Handler::format_time()
				'logFormat'     => $log_format,
				'appendContext' => false,
			);

			$this->logger = new KLogger( $log_directory, $log_level_threshold, $options );

			// Schedule a job to clean up logs. (WooCommerce would do this automatically).
			$cron = new Cron( $api, $settings );
			add_action( 'init', array( $cron, 'register_cron_job' ) );
			add_action( 'delete_logs_' . $settings->get_plugin_slug(), array( $cron, 'delete_old_logs' ) );

		}

		/**
		 * A filter which can be run to find which plugins have a logger instantiated.
		 * Added in particular so loggers can be created for arbitrary plugins to capture their PHP errors, i.e.
		 * we want to know which plugins already have a logger so we don't interfere unnecessarily.
		 */
		add_filter(
			'bh-wp-loggers',
			function( $loggers ) use ( $settings ) {

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
			function() {
				return $this;
			}
		);

		$functions = new Functions( $api, $settings, $this );

		add_action( 'deprecated_function_run', array( $functions, 'log_deprecated_functions_only_once_per_day' ), 10, 3 );
		add_action( 'deprecated_argument_run', array( $functions, 'log_deprecated_arguments_only_once_per_day' ), 10, 3 );
		add_action( 'doing_it_wrong_run', array( $functions, 'log_doing_it_wrong_only_once_per_day' ), 10, 3 );
		add_action( 'deprecated_hook_run', array( $functions, 'log_deprecated_hook_only_once_per_day' ), 10, 4 );

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

		// If this is true, WooCommerce is known to be active, but we are before plugins_loaded.
		if ( ! isset( $this->logger ) ) {

			// Re-run this same function after the logger has been initialized.
			$re_run_log = function() use ( $level, $message, $context ) {
				// $context['enqueued'] = true; // TODO: to stop duplicate (thus out of order) WP_CLI logs.
				$this->log( $level, $message, $context );
			};
			add_action( 'plugins_loaded', $re_run_log, 2 );

			return;
		}

		if ( $this->settings instanceof WooCommerce_Logger_Interface ) {
			$context['source'] = $this->settings->get_plugin_slug();
		}

		$this->logger->log( $level, $message, $context );

		update_option( $this->settings->get_plugin_slug() . '-last-log-time', time() );
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
	 * Make the main functions available, e.g. get_log_url().
	 *
	 * @return API_Interface
	 */
	public function get_api(): API_Interface {
		return $this->api;
	}
}
