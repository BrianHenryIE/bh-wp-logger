<?php
/**
 * The main functions of the logger.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Admin\Logs_List_Table;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WP_Includes\Plugins;
use BrianHenryIE\WP_Logger\Logger;
use BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;
use DateTime;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use stdClass;

/**
 * BH_WP_PSR_Logger extends this, then Logger extends that.
 *
 * @see BH_WP_PSR_Logger
 * @see Logger
 */
class API implements API_Interface {

	use LoggerAwareTrait;

	/**
	 * Needed for the plugin slug to link correctly.
	 *
	 * @uses Logger_Settings_Interface::get_plugin_slug()
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

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
	 * @param Logger_Settings_Interface $settings The settings provided by the plugin to instantiate the logger.
	 * @param ?LoggerInterface          $logger A PSR logger, presumably later a BH_WP_PSR_Logger.
	 */
	public function __construct( Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {
		$this->setLogger( $logger ?? new NullLogger() );
		$this->settings = $settings;
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
	 * @return array{success:bool, message?:string, deleted_files?:array<string>, failed_to_delete?:array<string>}
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
	 * @param ?int $steps The number of backtrace entries to return. e.g. with debug enabled, we don't need the full backtrace for every log entry.
	 *
	 * @return Frame[]
	 */
	public function get_backtrace( ?int $steps = null ): array {

		$starting_from_frame_closure = function( Frame $frame ): bool {
			if ( __FILE__ === $frame->file
				|| 'call_user_func_array' === $frame->method
				|| basename( $frame->file ) === 'class-php-error-handler.php'
				|| basename( $frame->file ) === 'class-functions.php'
				|| false !== strpos( $frame->file, 'bh-wp-logger/src/API' )
				|| false !== strpos( $frame->file, 'psr/log/Psr/Log/' )
				|| false !== strpos( $frame->file, 'php-http/logger-plugin' )
			) {
				return false;
			}
			return true;
		};

		return Backtrace::create()->withArguments()->startingFromFrame( $starting_from_frame_closure )->limit( $steps ?? 0 )->frames();
	}

	/**
	 * Checks each file in the backtrace and if it contains WP_PLUGINS_DIR/plugin-slug then return true.
	 *
	 * @return bool
	 */
	public function is_backtrace_contains_plugin(): bool {

		$frames = $this->get_backtrace();

		foreach ( $frames as $frame ) {

			if ( $this->is_file_from_plugin( $frame->file ) ) {
				return true;
			}
		}

		return false;
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
	 *
	 * @return bool
	 */
	public function is_file_from_plugin( string $filepath ): bool {

		$capture_first_string_after_slash_in_plugins_dir = '/' . str_replace( DIRECTORY_SEPARATOR, '\\' . DIRECTORY_SEPARATOR, WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' ) . '/';

		if ( 1 === preg_match( $capture_first_string_after_slash_in_plugins_dir, $filepath, $output_array ) ) {

			$slug = $output_array[1];

			if ( $this->settings->get_plugin_slug() === $slug ) {
				return true;
			}
		}

		$plugin_dir_realpath = realpath( WP_PLUGIN_DIR . '/' . explode( '/', $this->settings->get_plugin_basename() )[0] );
		if ( false !== $plugin_dir_realpath && false !== strpos( $filepath, $plugin_dir_realpath ) ) {
			return true;
		}

		return false;
	}

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
	 * TODO: NB Add transient.
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

			$offset_position = - 2;

			$current_line = '';

			while ( - 1 !== fseek( $file_pointer, $offset_position, SEEK_END ) ) {
				$character = fgetc( $file_pointer );
				if ( PHP_EOL === $character ) {

					if ( 1 === preg_match( '/^(?P<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.{1}\d{2}:\d{2})\s/im', $current_line, $output_array ) ) {
						set_transient( $transient_name, $output_array['time'], DAY_IN_SECONDS );
						return new DateTimeImmutable( $output_array['time'], new DateTimeZone( 'UTC' ) );
					}

					$current_line = '';
				} else {
					$current_line = $character . $current_line;
				}
				$offset_position --;
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
			} catch ( \Exception $exception ) {
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
	 * @return array<array{time:string,datetime:DateTime|null,level:string,message:string,context:stdClass|null}>
	 */
	public function parse_log( string $filepath ): array {

		$file_lines = file( $filepath );

		if ( false === $file_lines ) {
			// Failed to read file.
			return array();
		}

		$entries = array();

		// TODO: This will fail if the first line does not parse.
		foreach ( $file_lines as $input_line ) {

			$output_array = array();
			if ( 1 === preg_match( '/(?P<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.{1}\d{2}:\d{2})\s(?P<level>\w*)\s(?P<message>.*)/im', $input_line, $output_array ) ) {
				$entries[] = array(
					'line_one_parsed' => $output_array,
					'lines'           => array(),
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
	 * @return array{time:string,datetime:DateTime|null,level:string,message:string,context:stdClass|null}
	 */
	protected function log_lines_to_entry( array $input_lines ): array {

		$entry = array();

		$time_string = $input_lines['line_one_parsed']['time'];
		$str_time    = strtotime( $time_string );
		// E.g. "2020-10-23T17:39:36+00:00".
		$datetime = DateTime::createFromFormat( 'U', "{$str_time}" );
		if ( false === $datetime ) {
			$datetime = null; }

		$level = $input_lines['line_one_parsed']['level'];

		$message = $input_lines['line_one_parsed']['message'];

		$context = null;

		foreach ( $input_lines['lines'] as $input_line ) {
			$context = json_decode( $input_line );
			if ( is_null( $context ) ) {
				$message .= $input_line;
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

