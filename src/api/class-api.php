<?php

namespace BrianHenryIE\WP_Logger\api;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WC_Admin_Status;
use function WP_CLI\Utils\normalize_path;

class API implements API_Interface {

	/** @var Logger_Settings_Interface */
	protected $settings;

	/** @var LoggerInterface     */
	protected $logger;


	/**
	 * @var string[] Common data for context. "state"?
	 */
	protected $common_context = array();


	public function __construct( $settings, $logger = null ) {
		$this->logger   = $logger ?? new NullLogger();
		$this->settings = $settings;
	}

	public function delete_old_logs(): void {

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


	public function get_log_file( $date = null ) {

		if ( class_exists( WC_Admin_Status::class ) ) {

			$logs_files = WC_Admin_Status::scan_log_files();

			$log_files_dir = WC_LOG_DIR;

		} else {

			$log_files_dir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs/' );

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
			if ( 1 === preg_match( '/' . $this->settings->get_plugin_slug() . '-(\d{4}-\d{2}-\d{2}).*/', $log_filename, $regex_matches ) ) {

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

	public function set_common_context( $key, $value ): void {
		$this->common_context[ $key ] = $value;
	}
}

