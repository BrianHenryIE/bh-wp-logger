<?php
/**
 *
 */

namespace BrianHenryIE\WP_Logger\includes;

use BrianHenryIE\WP_Logger\includes\Functions;
use BrianHenryIE\WP_Logger\admin\Admin;
use BrianHenryIE\WP_Logger\admin\Logs_Page;
use BrianHenryIE\WP_Logger\admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\API\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\woocommerce\Log_Handler;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\AbstractLogger;
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

	/** @var WC_Logger_Interface|AbstractLogger The true logger, once available. */
	protected $logger;

	/**
	 * @var Logger_Settings_Interface
	 */
	protected $settings;

	/**
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * Logger constructor.
	 *
	 * TODO: This uses WC_Logger for all plugins, not just WooCommerce related ones.
	 *
	 * @param API_Interface              $api
	 * @param ?Logger_Settings_Interface $settings
	 */
	public function __construct( $api, $settings ) {

		$this->settings = $settings;
		$this->api      = $api;

		// TODO: Option to disable.

		$logs_page = new Logs_Page( $api, $settings );
		add_action( 'admin_menu', array( $logs_page, 'add_page' ) );
		add_action( 'admin_footer', array( $logs_page, 'print_css' ) );

		$admin = new Admin( $api, $settings );
		add_action( 'admin_init', array( $admin, 'admin_notices' ) );

		// TODO: This is not always correct.
		$plugins_page = new Plugins_Page( $api, $settings );
		$hook         = "plugin_action_links_{$this->settings->get_plugin_basename()}";
		add_filter( $hook, array( $plugins_page, 'display_plugin_action_links' ) );

		$php_error_handler = new PHP_Error_Handler( $settings, $this );
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
		$current_user_id = get_current_user_id();
		if ( 0 !== $current_user_id ) {
			$this->api->set_common_context( 'user_id', $current_user_id );
		}

		if ( false && in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			add_action(
				'plugins_loaded',
				function() use ( $settings ) {
					$this->logger = wc_get_logger();
					// TODO: Check this is not setting log level to debug for all plugins.
					// self::$logger->setLogLevelThreshold( $settings->get_log_level() );
				},
				1
			);

			$wc_log_handler = new Log_Handler( $api, $settings );
			add_filter( 'woocommerce_format_log_entry', array( $wc_log_handler, 'add_context_to_logs' ), 10, 2 );

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

		}

		/**
		 * A filter which can be run to find which plugins have a plugin instantiated.
		 * Added in particular so loggers can be created for arbitrary plugins to capture their PHP errors, i.e.
		 * we want to know which plugins already have a logger so we don't interfere unnecessarily.
		 */
		add_filter(
			'bh-wp-loggers',
			function( $loggers ) use ( $settings ) {
				// TODO: what should be returned?
				$value           = (array) $settings;
				$value['logger'] = $this;

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

	public function log( $level, $message, $context = array() ) {

		if ( class_exists( WP_CLI::class ) ) {
			// TODO WP_CLI::log is "encouraged", but was using an uninitialized logger variable when running in tests.
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

		$this->logger->log( $level, $message, $context );

		update_option( $this->settings->get_plugin_slug() . '-last-log-time', time() );
	}

	/**
	 * When an error is being logged, record the time of the last error, so later, an admin notice can be displayed,
	 * to inform them of the new problem.
	 *
	 * TODO: include a link to the log url so the last file with an error will be linked, rather than the most recent log file.
	 *
	 * @param string $message
	 * @param array  $context
	 */
	public function error( $message, $context = array() ) {

		update_option(
			$this->settings->get_plugin_slug() . '-recent-error-data',
			array(
				'message'   => $message,
				'timestamp' => time(),
			)
		);

		$debug_backtrace            = debug_backtrace( null, 2 );
		$context['debug_backtrace'] = $debug_backtrace;

		global $wp_current_filter;
		$context['filters'] = $wp_current_filter;

		$this->log( LogLevel::ERROR, $message, $context );
	}
}
