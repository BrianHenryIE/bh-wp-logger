<?php
/**
 *
 */

namespace BrianHenryIE\WP_Logger;

use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WPTRT\AdminNotices\Notices;
use WC_Admin_Status;
use WC_Log_Levels;
use WC_Logger_Interface;
use WP_CLI;

/**
 *
 * @see WC_Logger
 * @see https://www.php-fig.org/psr/psr-3/
 */
class Logger extends AbstractLogger {

	/** @var Logger */
	protected static $instance;

	/** @var string The logfile name to be used. */
	protected static $source;

	/**
	 * A unique identifier that stays constant throughout the PHP request, i.e. to filter debug logs to a single customer experience.
	 *
	 * @var string
	 */
	protected static $session_id;

	/** @var string Minimum log level. To be overwritten by saved settings. */
	public static $min_level = LogLevel::NOTICE;

	/** @var WC_Logger_Interface|AbstractLogger The true logger, once available. */
	protected static $logger;

	/**
	 * @var Logger_Settings_Interface
	 */
	protected $settings;

	/**
	 * @param ?Logger_Settings_Interface $settings
	 *
	 * @return Logger
	 */
	public static function instance( $settings = null ): Logger {

		if ( is_null( self::$instance ) ) {
			 self::$instance = new Logger( $settings );
		}

		return self::$instance;
	}

	/**
	 * Logger constructor.
	 *
	 * @param ?Logger_Settings_Interface $settings
	 */
	protected function __construct( $settings = null ) {

		// Zero-config.
		$settings = $settings ?? new Logger_Settings();

		self::$source    = $settings->get_plugin_slug();
		self::$min_level = $settings->get_log_level();

		$this->settings = $settings;

		// TODO: Option to disable.
		add_action( 'admin_init', array( $this, 'admin_notices' ) );
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_footer', array( $this, 'print_css' ) );

		$plugin_basename = self::$source . '/' . self::$source . '.php';
		add_filter( "plugin_action_links_{$plugin_basename}", array( $this, 'display_plugin_action_links' ) );

		if ( 'none' === $settings->get_log_level() ) {
			self::$logger = new NullLogger();
			return;
		}

		if ( isset( $_COOKIE['PHPSESSID'] ) ) {
			self::$session_id = $_COOKIE['PHPSESSID'];
		} else {
			self::$session_id = time();
		}

		if ( defined( 'LOGGED_IN_COOKIE' ) && isset( $_COOKIE[ LOGGED_IN_COOKIE ] ) ) {
			$parts      = explode( '|', $_COOKIE[ LOGGED_IN_COOKIE ] );
			$user_login = $parts[0];
		}

		if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

			add_action(
				'woocommerce_loaded',
				function() use ( $settings ) {
					self::$logger = wc_get_logger();
					// TODO: Check this is not setting log level to debug for all plugins.
					// self::$logger->setLogLevelThreshold( $settings->get_log_level() );
				}
			);

		} else {

			$log_directory       = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logs';
			$log_level_threshold = $settings->get_log_level();

			// TODO: only if debug etc.
			$append_context = true;

			$log_format = '{date} {level} {message}';
			if ( $append_context ) {
				$log_format .= "\n{context}";
			}
			$append_context = false; // because we have already handled it here.

			$options = array(
				'extension'     => 'log',
				'prefix'        => "{$settings->get_plugin_slug()}-",
				'dateFormat'    => 'c', // @see WC_Log_Handler::format_time()
				'logFormat'     => $log_format,
				'appendContext' => $append_context,
			);

			self::$logger = new KLogger( $log_directory, $log_level_threshold, $options );

		}

		add_filter( 'woocommerce_format_log_entry', array( $this, 'add_context_to_logs' ), 10, 2 );

	}

	public function log( $level, $message, $context = array() ) {

		if ( class_exists( WP_CLI::class ) ) {
			// TODO WP_CLI::log is "encouraged", but was using an uninitialized logger variable when running in tests.
			WP_CLI::line( $message );
		}

		$context['source']     = self::$source;
		$context['session_id'] = self::$session_id;

		// If this is true, WooCommerce is know to be active, but not yet loaded.
		if ( ! isset( self::$logger ) ) {

			// Re-run this same function after the logger has been initialized.
			$re_run_log = function() use ( $level, $message, $context ) {
				// $context['enqueued'] = true; // TODO: to stop duplicate (thus out of order) WP_CLI logs.
				$this->log( $level, $message, $context );
			};
			add_action( 'woocommerce_loaded', $re_run_log, 11 );

			return;
		}

		self::$logger->log( $level, $message, $context );

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
			self::$source . '-recent-error-data',
			array(
				'message'   => $message,
				'timestamp' => time(),
			)
		);

		$debug_backtrace            = debug_backtrace( null, 2 );
		$context['debug_backtrace'] = $debug_backtrace;

		parent::error( $message, $context );
	}


	/**
	 * The standard WooCommerce logger does not record the $context.
	 *
	 * Add context when min log level is Debug, for Errors and worse, and when WP_DEBUG is true.
	 *
	 * @hooked woocommerce_format_log_entry
	 *
	 * @see \WC_Log_Handler::format_entry()
	 *
	 * @param string $entry The log entry already built by WooCommerce.
	 * @param array  $log_data_array {
	 *  Information used to create the log entry.
	 *
	 *  @type int    $timestamp Log timestamp.
	 *  @type string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 *  @type string $message   Log message.
	 *  @type array  $context   Extraneous information that does not fit well in a string.
	 * }
	 *
	 * @return string
	 */
	public static function add_context_to_logs( $entry, $log_data_array ) {

		// Only act on logs for this plugin.
		if ( ! isset( $log_data_array['context']['source'] ) || $log_data_array['context']['source'] !== self::$source ) {
			return $entry;
		}

		if ( ! ( WC_Log_Levels::get_level_severity( $log_data_array['level'] ) >= WC_Log_Levels::ERROR
				 || WC_Log_Levels::DEBUG === self::$min_level
				 || ( defined( WP_DEBUG ) && WP_DEBUG ) ) ) {
			return $entry;
		}

		$context = $log_data_array['context'];

		unset( $context['source'] );

		return $entry . "\n" . wp_json_encode( $context );
	}

	/**
	 * Get the WordPress admin link to the logfile.
	 *
	 * @param string|null $date A date string Y-m-d or null to get the most recent.
	 *
	 * @return string|null
	 */
	public static function get_log_url( $date = null ) {

		$query_args = array(
			'page' => self::$source . '-logs',
		// 'tab'  => 'logs',
		);

		if ( ! empty( $chosen_log_filename ) ) {
			$query_args['log_file'] = $chosen_log_filename;
		}

		$logs_url = add_query_arg( $query_args, admin_url( 'admin.php' ) );

		return $logs_url;

		if ( class_exists( WC_Admin_Status::class ) ) {
			$logs = WC_Admin_Status::scan_log_files();

			$chosen_log_filename = '';
			$newest_log_filetime = 0;

			foreach ( $logs as $log_filename ) {
				$regex_matches = array();
				if ( 1 === preg_match( '/' . self::$source . '-(\d{4}-\d{2}-\d{2}).*/', $log_filename, $regex_matches ) ) {

					if ( ! is_null( $date ) && $regex_matches[1] === $date ) {
						$chosen_log_filename = $log_filename;
						break;
					}

					$log_datetime = date_create_from_format( 'Y-m-d', $regex_matches[1] );
					$log_unixtime = $log_datetime->format( 'U' );

					if ( $log_unixtime > $newest_log_filetime ) {
						$newest_log_filetime = $log_unixtime;
						$chosen_log_filename = $log_filename;
					}
				}
			}

			$query_args = array(
				'page' => 'wc-status',
				'tab'  => 'logs',
			);

			if ( ! empty( $chosen_log_filename ) ) {
				$query_args['log_file'] = $chosen_log_filename;
			}

			$logs_url = add_query_arg( $query_args, admin_url( 'admin.php' ) );

			return $logs_url;
		}

		return null;
	}

	public static function get_log_file( $date = null ) {

		if ( class_exists( WC_Admin_Status::class ) ) {

			$logs_files = WC_Admin_Status::scan_log_files();

			$log_files_dir = WC_LOG_DIR;

		} else {

			$log_files_dir = WP_CONTENT_DIR . '/uploads/logs/';

			$files      = scandir( $log_files_dir );
			$logs_files = array();

			if ( ! empty( $files ) ) {
				foreach ( $files as $key => $value ) {
					if ( ! in_array( $value, array( '.', '..' ), true ) ) {
						if ( ! is_dir( $value ) && strstr( $value, '.log' ) ) {
							$logs_files[ sanitize_title( $value ) ] = $value;
						}
					}
				}
			}
		}

		$chosen_log_filename = '';
		$newest_log_filetime = 0;

		foreach ( $logs_files as $log_filename ) {
			$regex_matches = array();
			if ( 1 === preg_match( '/' . self::$source . '-(\d{4}-\d{2}-\d{2}).*/', $log_filename, $regex_matches ) ) {

				if ( ! is_null( $date ) && $regex_matches[1] === $date ) {
					$chosen_log_filename = $log_filename;
					break;
				}

				$log_datetime = date_create_from_format( 'Y-m-d', $regex_matches[1] );
				$log_unixtime = $log_datetime->format( 'U' );

				if ( $log_unixtime > $newest_log_filetime ) {
					$newest_log_filetime = $log_unixtime;
					$chosen_log_filename = $log_filename;
				}
			}
		}

		$logs_file = $log_files_dir . $chosen_log_filename;

		return $logs_file;
	}


	/**
	 * Show a notice for recent errors in the logs.
	 *
	 * TODO: Do not show on plugin install page.
	 *
	 * @hooked admin_init
	 */
	public function admin_notices() {

		$notices = new Notices();

		$error_detail_option_name = self::$source . '-recent-error-data';

		$last_error = get_option( $error_detail_option_name );

		if ( false !== $last_error ) {

			$is_dismissed_option_name = self::$source . '_recent_error';
			delete_option( $is_dismissed_option_name );

			$error_text = isset( $last_error['message'] ) ? trim( $last_error['message'] ) : '';
			$error_time = isset( $last_error['timestamp'] ) ? $last_error['timestamp'] : '';

			$title   = false;
			$content = "<strong>{$this->settings->get_plugin_name()}</strong>. Error: ";

			if ( ! empty( $error_text ) ) {
				$content .= "\"{$error_text}\" ";
			}

			if ( ! empty( $error_time ) && is_int( $error_time ) ) {
				$content .= ' at ' . gmdate( 'Y-m-d\TH:i:s\Z', $error_time ) . ' UTC.';
				// Link to logs.
				$log_link = self::get_log_url( gmdate( 'Y-m-d', $error_time ) );

			} else {
				$log_link = self::get_log_url();
			}

			if ( ! is_null( $log_link ) ) {
				$content .= ' <a href="' . $log_link . '">View Logs</a>.</p></div>';
			}

			// ID must be globally unique because it is the css id that will be used.
			$notices->add(
				self::$source . 'recent-error',
				$title,   // The title for this notice.
				$content, // The content for this notice.
				array(
					'scope'         => 'global',
					'type'          => 'error',
					'option_prefix' => self::$source,
				)
			);

			/**
			 * When the notice is dismissed, delete the error detail option (to stop the notice being recreated),
			 * and delete the saved dismissed flag (which would prevent it displaying when the next error occurs).
			 *
			 * @see update_option()
			 */
			$on_dismiss = function( $old_value, $value, $option ) use ( $error_detail_option_name, $is_dismissed_option_name ) {
				error_log( 'Should be deleting ' . $error_detail_option_name );
				delete_option( $error_detail_option_name );
				delete_option( $option );
			};
			add_action( "update_option_{$is_dismissed_option_name}", $on_dismiss, 10, 3 );

		}

		$notices->boot();
	}


	/**
	 * Adds 'Logs' link to the most recent logs in the WooCommerce logs page.
	 *
	 * Hooked early presuming other changes will be prepended, i.e.
	 * Other Links | Logs | Deactivate
	 *
	 * @hooked plugin_action_links_{plugin basename}
	 * @see \WP_Plugins_List_Table::display_rows()
	 *
	 * @param string[] $links The links that will be shown below the plugin name on plugins.php.
	 *
	 * @return string[]
	 */
	public function display_plugin_action_links( $links ) {

		$plugin_links = array();
		$logs_link    = self::get_log_url();
		if ( ! is_null( $logs_link ) ) {
			$plugin_links[] = '<a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}


	/**
	 * @hooked admin_init
	 *
	 * Add a WordPress admin UI page, but without any menu linking to it.
	 */
	public function add_page() {

		$logs_slug = "{$this->settings->get_plugin_slug()}-logs";

		add_submenu_page(
			null,
			__( 'Logs', 'bh-wp-logger' ),
			'logs',
			'manage_options',
			$logs_slug,
			array( $this, 'display_page' )
		);

	}

	/**
	 * Registered in @see add_page()
	 */
	public function display_page() {

		$logs_table = new Logs_Table( $this );

		$logs_table->prepare_items();
		$logs_table->display();
	}

	/**
	 * @hooked admin_footer
	 */
	public function print_css() {

		if ( ! isset( $_GET['page'] ) || "{$this->settings->get_plugin_slug()}-logs" !== $_GET['page'] ) {
			return;
		}

		$css_file = __DIR__ . '/bh-wp-logger.css';

		if ( file_exists( $css_file ) ) {

			echo "\n<style>\n";

			echo file_get_contents( $css_file );

			echo "\n</style>\n";
		}

	}
}
