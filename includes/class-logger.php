<?php
/**
 * Instantiate the logger for your plugin.
 *
 * `$logger = \BrianHenryIE\WP_Logger\Logger::instance()`
 * better:
 * `$logger = \BrianHenryIE\WP_Logger\Logger::instance( $settings )`
 *
 * @see \BrianHenryIE\WP_Logger\Logger_Settings_Interface
 * @see \BrianHenryIE\WP_Logger\Logger_Settings_Trait
 * @see \BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WC_Logger\Log_Context_Handler;
use BrianHenryIE\WC_Logger\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API\Remove_WP_Hooks_Processor;
use BrianHenryIE\WP_Logger\WP_Includes\Plugin_Logger_Actions;
use BrianHenryIE\WP_Private_Uploads\BH_WP_Private_Uploads_Hooks;
use BrianHenryIE\WP_Private_Uploads\Private_Uploads_Settings_Interface;
use BrianHenryIE\WP_Private_Uploads\Private_Uploads_Settings_Trait;
use BrianHenryIE\WP_Private_Uploads\Private_Uploads;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\PsrHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Processor\PsrLogMessageProcessor;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Wraps parent class in a singleton so it only needs to be configured once.
 */
class Logger extends BH_WP_PSR_Logger implements API_Interface, LoggerInterface {

	/**
	 * Singleton.
	 *
	 * @var Logger
	 */
	protected static Logger $instance;

	/**
	 * Initialize the logger and store the instance in the singleton variable.
	 * Settings are used when provided, inferred when null.
	 * Ideally settings should be provided the first time the logger is instantiated, then they do not need
	 * to be provided when accessing the singleton later on.
	 *
	 * @see Logger_Settings
	 * @see Plugins
	 *
	 * @param ?Logger_Settings_Interface $settings The loglevel, plugin name, slug, and basename.
	 *
	 * @return LoggerInterface Ideally a {@see \BrianHenryIE\WP_Logger\Logger} but `NullLogger` sometimes.
	 */
	public static function instance( ?Logger_Settings_Interface $settings = null ): LoggerInterface {

		if ( ! isset( self::$instance ) ) {

			// Zero-config.
			$settings ??= new class() implements Logger_Settings_Interface {
				use Logger_Settings_Trait;
			};

			// TODO: This is wrong, the directory must be assumed to contain files and be kept private.
			if ( 'none' === $settings->get_log_level() ) {
				return new NullLogger();
			}

			$logger = new self( $settings );

			self::$instance = $logger;

			// Add the hooks.
			new Plugin_Logger_Actions( self::$instance, $settings, self::$instance );
		}

		return self::$instance;
	}

	/**
	 * If log level is 'none', use NullLogger.
	 * If Settings is WooCommerce_Logger_Settings_Interface use WC_Logger, otherwise use KLogger.
	 *
	 * @param Logger_Settings_Interface $settings Basic settings required for the logger.
	 */
	public function __construct( Logger_Settings_Interface $settings ) {

		/**
		 * We are not using {@see is_plugin_active()} here because "Call to undefined function" error (it may be an admin function).
		 *
		 * @param string $plugin_basename The main plugin file's path relative to WP_PLUGIN_DIR.
		 */
		$is_plugin_active = function ( string $plugin_basename ): bool {
			/** @var array<int,string> $active_plugins */
			$active_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins', array() ) );
			return in_array( $plugin_basename, $active_plugins, true );
		};

		if ( $settings instanceof WooCommerce_Logger_Settings_Interface && $is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

			$logger = new WC_PSR_Logger( $settings );

			// Add context to WooCommerce logs.
			$wc_log_handler = new Log_Context_Handler( $settings );
			add_filter( 'woocommerce_format_log_entry', array( $wc_log_handler, 'add_context_to_logs' ), 10, 2 );

			// TODO: What's the log file name when it's a wc-log?

		} else {

			$log_directory       = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs' );
			$log_level_threshold = $settings->get_log_level();

			/**
			 * Add the `{context}` template string,
			 * then provide `'appendContext' => false` to Lineformatter (since it is already takes care of).
			 *
			 * @see LineFormatter::SIMPLE_FORMAT
			 * "[%datetime%] %channel%.%level_name%: %message% %context% %extra%\n";
			 */
			$log_format = "%datetime% %level_name% %message%\n%context%\n";

			// /path/to/.../wp-content/uploads/logs/bh-wp-logger-test-plugin-2024-04-20.log
			$logfile = sprintf(
				'%s/%s-%s.log',
				$log_directory,
				$settings->get_plugin_slug(),
				gmdate( 'Y-m-d' )
			);

			/**
			 * `c` is chosen to match WooCommerce's choice.
			 *
			 * ISO8601: "2004-02-12T15:19:21+00:00".
			 *
			 * @see WC_Log_Handler::format_time()
			 */
			$formatter = new LineFormatter(
				format: $log_format,
				dateFormat: 'c',
				allowInlineLineBreaks: true,
				ignoreEmptyContextAndExtra: true,
				includeStacktraces: false
			);

			$logger  = new \Monolog\Logger( $settings->get_plugin_slug() );
			$handler = new StreamHandler( $logfile, $log_level_threshold );
			$handler->setFormatter( $formatter );
			$logger->pushHandler( $handler );
			$logger->pushProcessor( new PsrLogMessageProcessor() );
			// Backtrace frames can reference `WP_Hook` objects whose callbacks would bloat the log file.
			$logger->pushProcessor( new Remove_WP_Hooks_Processor() );

			// Make the logs directory inaccessible to the public.
			$private_uploads_settings = new class( $settings ) implements Private_Uploads_Settings_Interface {
				use Private_Uploads_Settings_Trait;

				/**
				 * Constructor.
				 *
				 * @param Logger_Settings_Interface $logger_settings The plugin logger settings, whose plugin slug we need.
				 */
				public function __construct(
					/**
					 * The settings provided for the logger. We need the plugin slug as a uid for the private uploads instance.
					 */
					protected Logger_Settings_Interface $logger_settings
				) {
				}

				/**
				 * This is used as a unique id for the Private Uploads instance.
				 */
				public function get_plugin_slug(): string {
					return $this->logger_settings->get_plugin_slug() . '_logger';
				}

				/**
				 * Use wp-content/uploads/logs as the logs directory.
				 */
				public function get_uploads_subdirectory_name(): string {
					return 'logs';
				}

				/**
				 * Do not register a post type for the plugin's logs.
				 */
				public function get_post_type_name(): string {
					return '';
				}
			};

			// Mute debug logs from library: the handler drops records below INFO.
			$private_uploads_logger = new \Monolog\Logger( $settings->get_plugin_slug() . '-private-uploads' );
			$private_uploads_logger->pushHandler( new PsrHandler( $logger, \Monolog\Logger::INFO ) );

			// Don't use the Private_Uploads singleton in case the parent plugin also needs it.
			$private_uploads_api = new Private_Uploads( $private_uploads_settings, $private_uploads_logger );
			new BH_WP_Private_Uploads_Hooks( $private_uploads_api, $private_uploads_settings, $private_uploads_logger );
		}

		parent::__construct( $settings, $logger );
	}
}
