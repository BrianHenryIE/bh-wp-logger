<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;
use WC_Admin_Status;


class API implements API_Interface {

	use LoggerAwareTrait;

	/** @var Logger_Settings_Interface */
	protected $settings;

	/**
	 *
	 * TODO: IS getmypid() reliable?
	 *
	 * @see https://stackoverflow.com/questions/10404979/get-unique-worker-thread-process-request-id-in-php
	 *
	 * @var string[] Common data for context. "state"?
	 */
	protected $common_context = array();



	public function __construct( Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	public function get_common_context(): array {
		return $this->common_context;
	}


	/**
	 * Get the WordPress admin link to the log UI at a date.
	 *
	 * @param string|null $date A date string Y-m-d or null to get the most recent.
	 *
	 * @return string|null
	 */
	public function get_log_url( $date = null ): string {

		$query_args = array(
			'page' => $this->settings->get_plugin_slug() . '-logs',
			// 'tab'  => 'logs',
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

		if ( ( $this->settings instanceof WooCommerce_Logger_Interface ) && class_exists( WC_Admin_Status::class ) ) {

			// TODO use this
//			$logs_files = WC_Admin_Status::scan_log_files();

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
							$logs_files[ $regex_matches[1] ] = $log_files_dir . $filename;

							if ( ! is_null( $date ) && $regex_matches[1] === $date ) {
								return array( $date => realpath( $log_files_dir . $filename ) );
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

		if ( isset( $log_filepaths_by_date[ $ymd_date ] ) ) {
			unlink( $log_filepaths_by_date[ $ymd_date ] );
			$result['success'] = true;
		} else {
			$result['success'] = false;
			$result['message'] = 'Log file not found for date: ' . $ymd_date;
		}

		return $result;
	}

	/**
	 * Delete all logs for this plugin.
	 *
	 * @used-by Logs_Page
	 *
	 * @return array{success:bool, message?:string}
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

		if ( empty( $failed_to_delete ) ) {
			$result['success']       = true;
			$result['deleted_files'] = $deleted_files;
		} else {
			$result['success']          = false;
			$result['deleted_files']    = $deleted_files;
			$result['failed_to_delete'] = $failed_to_delete;
		}

		return $result;
	}

	/**
	 * Deletes log files older than MONTH_IN_SECONDS.
	 * Deletes empty log files.
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


	public function set_common_context( $key, $value ): void {
		$this->common_context[ $key ] = $value;
	}

	/**
	 * Loops through the debug backtrace until it finds a folder with wp-content/plugins as its parent.
	 *
	 * @return ?string
	 */
	public function determine_plugin_slug_from_backtrace(): ?string {

		$backtrace = Backtrace::create()->offset( 2 );

		$capture_first_string_after_slash_in_plugins_dir = '/' . str_replace( DIRECTORY_SEPARATOR, '\\' . DIRECTORY_SEPARATOR, WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' ) . '/';

		// TODO: We probably only care about the following couple (after the two skipped).
		// TODO: What about WooCommerce (any plugin-in-plugin...) where WooCommerce maybe raised the issues, but it's due to another plugin's code.
		$frames = $backtrace->frames();

		foreach ( $frames as $frame ) {

			if ( 1 === preg_match( $capture_first_string_after_slash_in_plugins_dir, $frame->file, $output_array ) ) {

				$slug = $output_array[1];

				return $slug;
			}
		}

		return null;
	}

	/**
	 *
	 * Get the backtrace and skips:
	 * * function calls from inside this file
	 * * call_user_func_array()
	 * * function calls from other plugins using this same library (using filename).
	 *
	 * @return Frame[]
	 */
	public function get_backtrace(): array {

		$starting_from_frame_closure = function( Frame $frame ): bool {
			if ( __FILE__ === $frame->file
				|| 'call_user_func_array' === $frame->method
			// || ( substr( $frame->file, -strlen( 'class-php-error-handler.php' ) ) === basename( 'class-php-error-handler.php' ) )
				|| basename( $frame->file ) === 'class-php-error-handler.php'
			) {
				return false;
			}
			return true;
		};

		return Backtrace::create()->withArguments()->startingFromFrame( $starting_from_frame_closure )->frames();

	}


}

