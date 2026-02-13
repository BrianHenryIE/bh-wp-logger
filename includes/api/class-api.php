<?php
/**
 * The main functions of the logger.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WC_Logger\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\Admin\Logs_List_Table;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger;
use BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * BH_WP_PSR_Logger extends this, then Logger extends that.
 *
 * @see BH_WP_PSR_Logger
 * @see Logger
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	const CACHE_GROUP_KEY = 'bh-wp-logger';

	/**
	 *
	 * TODO: IS getmypid() reliable?
	 * TODO: Add current user id.
	 *
	 * @see https://stackoverflow.com/questions/10404979/get-unique-worker-thread-process-request-id-in-php
	 *
	 * @var string[] Common data for context. "state"?
	 */
	protected $common_context = array();

	/**
	 * Instantiate the API class with settings provided by the plugin.
	 *
	 * BH_WP_PSR_Logger needs and API instance and API needs a PSR logger instance if it is to log. LoggerAwareTrait
	 * allows setting the logger after instantiation.
	 *
	 * @param Logger_Settings_Interface $settings The settings provided by the plugin to instantiate the logger. Needed for the plugin slug to link correctly.
	 * @param ?LoggerInterface          $logger A PSR logger, presumably later a BH_WP_PSR_Logger.
	 */
	public function __construct(
		protected Logger_Settings_Interface $settings,
		?LoggerInterface $logger = null
	) {
		$this->setLogger( $logger ?? new NullLogger() );
	}

	/**
	 * Array of data to add to every log entry.
	 * Resets on each new pageload (request).
	 *
	 * @return string[]
	 */
	public function get_common_context(): array {
		return $this->common_context;
	}

	/**
	 * Set a value in the common context.
	 *
	 * @param string $key Descriptive key.
	 * @param mixed  $value Will be parsed to JSON later.
	 *
	 * @return void
	 */
	public function set_common_context( string $key, $value ): void {
		$this->common_context[ $key ] = $value;
	}

	/**
	 * Get the WordPress admin link to the log UI at a date.
	 *
	 * @param ?string $date A date string Y-m-d or null to get the most recent.
	 *
	 * @return string
	 */
	public function get_log_url( ?string $date = null ): string {

		$query_args = array(
			'page' => $this->settings->get_plugin_slug() . '-logs',
		);

		if ( ! empty( $date ) ) {
			$query_args['date'] = $date;
		}

		$logs_url = admin_url( add_query_arg( $query_args, 'admin.php' ) );

		return $logs_url;
	}

	/**
	 * Scan the logs files dir for the latest log file, or the log file matching the supplied date.
	 *
	 * TODO: Test the regex. It seems to be pulling in all files that match a date?
	 *
	 * @param ?string $date In 'Y-m-d' format. e.g. '2021-09-16'.
	 *
	 * @return array<string, string> Y-m-d index with path as the value.
	 */
	public function get_log_files( ?string $date = null ): array {

		// TODO: Replace WC_LOG_DIR check with the plugin_active check that is used overall.
		if ( ( $this->settings instanceof WooCommerce_Logger_Settings_Interface ) && defined( 'WC_LOG_DIR' ) ) {

			$log_files_dir = WC_LOG_DIR;

		} else {

			$log_files_dir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs/' );
		}

		$files      = scandir( $log_files_dir );
		$logs_files = array();

		if ( ! empty( $files ) ) {
			foreach ( $files as $filename ) {
				if ( ! in_array( $filename, array( '.', '..' ), true ) ) {

					if ( ! is_dir( $filename ) && strstr( $filename, '.log' ) ) {

						if ( 1 === preg_match( '/^' . $this->settings->get_plugin_slug() . '-(\d{4}-\d{2}-\d{2}).*/', $filename, $regex_matches ) ) {
							$logs_files[ "{$regex_matches[1]}" ] = $log_files_dir . $filename;

							if ( ! is_null( $date ) && $regex_matches[1] === $date ) {
								$path     = $log_files_dir . $filename;
								$realpath = realpath( $path );
								return array( $date => false === $realpath ? $path : $realpath );
							}
						}
					}
				}
			}
		}

		ksort( $logs_files );

		return $logs_files;
	}

	/**
	 * Delete a specific date's log file.
	 *
	 * @param string $ymd_date The date formatted Y-m-d, e.g. 2021-09-27.
	 *
	 * @used-by Logs_Page
	 *
	 * @return array{success:bool, message?:string}
	 */
	public function delete_log( string $ymd_date ): array {

		$result = array();

		$log_filepaths_by_date = $this->get_log_files();

		if ( ! isset( $log_filepaths_by_date[ $ymd_date ] ) ) {
			$result['success'] = false;
			$message           = 'Log file not found for date: ' . $ymd_date;
			$result['message'] = $message;
			$this->logger->warning( $message );
			return $result;
		}

		unlink( $log_filepaths_by_date[ $ymd_date ] );
		$result['success'] = true;
		$message           = 'Logfile deleted at ' . $log_filepaths_by_date[ $ymd_date ];
		$result['message'] = $message;
		$this->logger->info( $message, array( 'logfile' => $log_filepaths_by_date[ $ymd_date ] ) );

		return $result;
	}

	/**
	 * Delete all logs for this plugin.
	 *
	 * @used-by Logs_Page
	 *
	 * @return array{success:bool, deleted_files:array<string>, failed_to_delete:array<string>}
	 */
	public function delete_all_logs(): array {

		$result = array();

		$deleted_files    = array();
		$failed_to_delete = array();

		$log_filepaths_by_date = $this->get_log_files();

		foreach ( $log_filepaths_by_date as $date => $log_filepath ) {
			$deleted = unlink( $log_filepath );
			if ( $deleted ) {
				$deleted_files[ $date ] = $log_filepath;
			} else {
				$failed_to_delete[ $date ] = $log_filepath;
			}
		}

		$result['deleted_files']    = $deleted_files;
		$result['failed_to_delete'] = $failed_to_delete;
		$result['success']          = empty( $failed_to_delete );

		return $result;
	}

	/**
	 * Deletes log files older than MONTH_IN_SECONDS.
	 * Deletes empty log files.
	 *
	 * TODO: Do not use for WooCommerce_Logger_Interface, because WooCommerce handles deleting logs itself.
	 *
	 * @used-by Cron::delete_old_logs()
	 */
	public function delete_old_logs(): void {

		$existing_logs = $this->get_log_files();

		foreach ( $existing_logs as $date => $log_filepath ) {

			if ( strtotime( $date ) < time() - MONTH_IN_SECONDS ) {
				$this->logger->debug( 'deleting old log file ' . $log_filepath );
				unlink( $log_filepath );
			} elseif ( 0 === filesize( $log_filepath ) ) {
				$this->logger->debug( 'deleting empty log file ' . $log_filepath );
				unlink( $log_filepath );
			}
		}

		// TODO: delete the last visited option if it's older than the most recent logs.
	}


	/**
	 * Get the backtrace and skips:
	 * * function calls from inside this file
	 * * call_user_func_array()
	 * * function calls from other plugins using this same library (using filename).
	 *
	 * TODO: Check is WordPress's own backtrace a good replacement for Spatie.
	 *
	 * @param ?string $source_hash A unique identifier for the source of the log entry. Used to cache the backtrace. The backtrace will not be cached if this is absent.
	 * @param ?int    $steps The number of backtrace entries to return.
	 *
	 * @return array<array{file?:string,line?:int,function:string,class:string,type:string,args:array<mixed>}>
	 */
	public function get_backtrace( ?string $source_hash = null, ?int $steps = null ): array {

		if ( ! empty( $source_hash ) ) {
			$backtrace_cached = $this->get_cached_backtrace( $source_hash );

			if ( is_array( $backtrace_cached ) ) {
				return $backtrace_cached;
			}
		}

		// This is critical to the library.
		// phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_debug_backtrace
		$backtrace = debug_backtrace();

		$ignore_starting_frame = function ( array $frame ): bool {
			switch ( true ) {
				case isset( $frame['file'] ) && __FILE__ === $frame['file']:
				case 'call_user_func_array' === $frame['function']:
				case isset( $frame['file'] ) && basename( $frame['file'] ) === 'class-php-error-handler.php':
				case isset( $frame['file'] ) && basename( $frame['file'] ) === 'class-functions.php':
				case isset( $frame['file'] ) && false !== stripos( $frame['file'], 'bh-wp-logger/includes' ):
				case isset( $frame['file'] ) && false !== stripos( $frame['file'], 'psr/log/Psr/Log/' ):
				case isset( $frame['file'] ) && str_contains( $frame['file'], 'php-http/logger-plugin' ):
					return true;
				default:
					return false;
			}
		};

		foreach ( $backtrace as $index => $frame ) {
			if ( $ignore_starting_frame( $frame ) ) {
				unset( $backtrace[ $index ] );
			} else {
				break;
			}
		}

		if ( ! is_null( $source_hash ) ) {
			$this->set_cached_backtrace( $source_hash, $backtrace );
		}

		return $backtrace;
	}

	/**
	 * Save the backtrace to a global cache.
	 *
	 * @see PHP_Error_Handler::is_related_error()
	 *
	 * If other plugins are using bh-wp-logger, they can use this cache to avoid rerunning the backtrace.
	 *
	 * @see debug_backtrace()
	 *
	 * @param string                                                                                          $source_hash A unique identifier for backtrace – `implode(func_get_args())` of the error handler is used.
	 * @param array<array{file?:string,line?:int,function:string,class:string,type:string,args:array<mixed>}> $backtrace The PHP backtrace, presumably filtered to remove irrelevant frames.
	 */
	protected function set_cached_backtrace( string $source_hash, array $backtrace ): void {
		if ( ! isset( $GLOBALS['bh_wp_logger_cache'] ) ) {
			$GLOBALS['bh_wp_logger_cache'] = array();
		}
		$source_hash                                   = sanitize_key( $source_hash );
		$GLOBALS['bh_wp_logger_cache'][ $source_hash ] = $backtrace;
	}

	/**
	 * Check if the backtrace for this error already been run.
	 *
	 * @param string $source_hash A unique identifier for backtrace.
	 *
	 * @return ?array<array{file?:string,line?:int,function:string,class:string,type:string,args:array<mixed>}>
	 */
	protected function get_cached_backtrace( string $source_hash ): ?array {
		$source_hash = sanitize_key( $source_hash );
		return isset( $GLOBALS['bh_wp_logger_cache'], $GLOBALS['bh_wp_logger_cache'][ $source_hash ] )
			? $GLOBALS['bh_wp_logger_cache'][ $source_hash ]
			: null;
	}

	/**
	 * Checks each file in the backtrace and if it contains WP_PLUGINS_DIR/plugin-slug then return true.
	 *
	 * @param ?string $source_hash A unique identifier for the source of the log entry.
	 */
	public function is_backtrace_contains_plugin( ?string $source_hash = null ): bool {

		$frames = $this->get_backtrace( $source_hash, null );

		$is_file_from_plugin = false;

		foreach ( $frames as $frame ) {

			if ( isset( $frame['file'] ) && $this->is_file_from_plugin( $frame['file'] ) ) {
				$is_file_from_plugin = true;
				break;
			}
		}

		return $is_file_from_plugin;
	}

	/**
	 * Given a filepath, tries to determine if this file is part of this plugin.
	 *
	 * TODO: Be consistent about meanings of plugin-slug and plugin basename.
	 * TODO: Remove code duplication with Plugins class.
	 *
	 * @see Plugins::get_plugin_data_from_slug()
	 *
	 * @param string $filepath Path to the file to be checked.
	 */
	public function is_file_from_plugin( string $filepath ): bool {
		return str_starts_with( plugin_basename( realpath( $filepath ) ), $this->settings->get_plugin_slug() );
	}

	/**
	 * Get the name of the plugin-specific transient which indicates the last log message time.
	 *
	 * The value is used on plugins.php to show if new logs have been recorded.
	 * A transient is used to avoid re-calculating it from reading the log files from disk.
	 *
	 * @return string
	 */
	public function get_last_log_time_transient_name(): string {
		return $this->settings->get_plugin_slug() . '-last-log-time';
	}

	/**
	 * Used on plugins.php to highlight the logs link if there are new logs since they were last viewed.
	 *
	 * Read the log file backwards from the last character. Each time a newline character is found, check the buffer
	 * to see was there a data at the beginning of the line. Return the first date found.
	 *
	 * @see https://stackoverflow.com/a/15017711/336146
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_last_log_time(): ?DateTimeInterface {

		$transient_name = $this->get_last_log_time_transient_name();

		$transient_value = get_transient( $transient_name );

		if ( ! empty( $transient_value ) ) {
			return new DateTimeImmutable( $transient_value, new DateTimeZone( 'UTC' ) );
		}

		$log_files = $this->get_log_files();

		$reverse_chronological_log_files = array_reverse( $log_files );

		foreach ( $reverse_chronological_log_files as $last_log_file_path ) {

			$file_pointer = fopen( $last_log_file_path, 'r' );

			if ( false === $file_pointer ) {
				$this->logger->warning( "Failed opening log file at {$last_log_file_path}." );
				continue;
			}

			$offset_position = - 2;

			$current_line = '';

			while ( - 1 !== fseek( $file_pointer, $offset_position, SEEK_END ) ) {
				$character = fgetc( $file_pointer );
				if ( PHP_EOL === $character ) {

					if ( 1 === preg_match( '/^(?P<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.{1}\d{2}:\d{2})\s/im', $current_line, $output_array ) ) {
						set_transient( $transient_name, $output_array['time'], DAY_IN_SECONDS );
						// Log time will always be UTC.
						return new DateTimeImmutable( $output_array['time'], new DateTimeZone( 'UTC' ) );
					}

					$current_line = '';
				} else {
					$current_line = $character . $current_line;
				}
				--$offset_position;
			}
		}

		return null;
	}

	/**
	 * Used on plugins.php to highlight the logs link if there are new logs since they were last viewed.
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_last_logs_view_time(): ?DateTimeInterface {

		$option_name                    = $this->settings->get_plugin_slug() . '-last-logs-view-time';
		$last_log_view_time_atom_string = get_option( $option_name );
		$last_log_view_time_datetime    = DateTimeImmutable::createFromFormat( DateTimeInterface::ATOM, $last_log_view_time_atom_string, new DateTimeZone( 'UTC' ) );

		if ( false === $last_log_view_time_datetime ) {
			delete_option( $option_name );
			return null;
		}

		return $last_log_view_time_datetime;
	}

	/**
	 * Record the last time the logs were viewed in order to determine if admin notices should or should not be displayed.
	 * i.e. "mark read".
	 *
	 * @used-by Logs_Page
	 *
	 * @param ?DateTimeInterface $date_time A time to set, defaults to "now".
	 *
	 * @return void
	 */
	public function set_last_logs_view_time( ?DateTimeInterface $date_time = null ): void {
		$option_name = $this->settings->get_plugin_slug() . '-last-logs-view-time';

		if ( is_null( $date_time ) ) {
			try {
				$date_time = new DateTimeImmutable( 'now', new DateTimeZone( 'UTC' ) );
			} catch ( Exception ) {
				// This will never happen.
				return;
			}
		}

		$atom_time_string = $date_time->format( DateTimeInterface::ATOM );

		update_option( $option_name, $atom_time_string );
	}

	/**
	 * Given the path to a log file, this returns an array of arrays – an array of log entries, each of which is an
	 * array containing the time, level, message and context.
	 *
	 * @used-by API::get_last_log_time()
	 * @used-by Logs_List_Table::get_data()
	 *
	 * @param string $filepath Path to the log file to read.
	 *
	 * @return array<array{time:string,datetime:DateTime|null,level:string,message:string,context:\stdClass|null}>
	 */
	public function parse_log( string $filepath ): array {

		$file_lines = file( $filepath );

		if ( false === $file_lines ) {
			// Failed to read file.
			return array();
		}

		$entries = array();

		if ( $this->logger instanceof WC_PSR_Logger ) {
			$pattern = '/^(?P<time>[^\s]*)\s(?P<level>\w*)\s(?P<message>.*?)\sCONTEXT:\s(?P<context>.*)/';
		} else {
			$pattern = '/(?P<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.{1}\d{2}:\d{2})\s(?P<level>\w*)\s(?P<message>.*)/im';
		}

		// TODO: This will fail if the first line does not parse.
		foreach ( $file_lines as $input_line ) {
			$output_array = array();
			if ( 1 === preg_match( $pattern, $input_line, $output_array ) ) {
				$entries[] = array(
					'line_one_parsed' => $output_array,
					'lines'           => array( $output_array['context'] ?? '' ),
				);
			} else {
				$entries[ count( $entries ) - 1 ]['lines'][] = $input_line;
			}
		}

		$data = array_map( array( $this, 'log_lines_to_entry' ), $entries );

		return $data;
	}

	/**
	 * Given the set of lines that constitutes a single log entry, this parses them into the array of time, level, message, context.
	 *
	 * @used-by API::parse_log()
	 *
	 * @param array{line_one_parsed:array{time:string,level:string,message:string}, lines:string[]} $input_lines A single log entries as a set of lines.
	 *
	 * @return array{time:string,datetime:DateTime|null,level:string,message:string,context:\stdClass|null}
	 */
	protected function log_lines_to_entry( array $input_lines ): array {

		$entry = array();

		$time_string = $input_lines['line_one_parsed']['time'];
		$str_time    = strtotime( $time_string );
		// E.g. "2020-10-23T17:39:36+00:00".
		$datetime = DateTime::createFromFormat( 'U', "{$str_time}" ) ?: null;

		$level = $input_lines['line_one_parsed']['level'];

		$message = $input_lines['line_one_parsed']['message'];

		// Assume all lines that do not begin with a date should be joined together as context object.
		$context = json_decode( implode( PHP_EOL, $input_lines['lines'] ) );
		if ( is_null( $context ) ) {
			foreach ( $input_lines['lines'] as $input_line ) {
				// This is a bug but I'm not going to fix it until I see the problem exist.
				// What happens if there is multiple lines that for some reason are valid JSON? Data will be lost in display.
				$context = json_decode( $input_line );
				if ( is_null( $context ) ) {
					$message .= $input_line;
				}
			}
		}

		if ( ! is_null( $context ) && isset( $context->source ) ) {
			unset( $context->source );
		}

		$entry['time']     = $time_string;
		$entry['datetime'] = $datetime;
		$entry['level']    = $level;
		$entry['message']  = $message;
		$entry['context']  = $context;

		return $entry;
	}
}
