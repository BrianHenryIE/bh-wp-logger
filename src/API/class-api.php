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


	/**
	 * Deletes log files older than MONTH_IN_SECONDS.
	 *
	 * @used-by Cron::delete_old_logs()
	 */
	public function delete_old_logs(): void {

		$logs_dir = WP_CONTENT_DIR . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR;

		$existing_logs = glob( "{$logs_dir}{$this->settings->get_plugin_slug()}*.log" );

		// e.g. bh-wc-auto-purchase-easypost-2021-06-01.log
		foreach ( $existing_logs as $log_filename ) {

			if ( 1 === preg_match( '/^' . $this->settings->get_plugin_slug() . '-(\d{4}-\d{2}-\d{2})\.log$/', $log_filename, $output_array ) ) {
				if ( strtotime( $output_array[1] ) < time() - MONTH_IN_SECONDS ) {
					$this->logger->debug( 'deleting old log file ' . $logs_dir . $log_filename );
					unlink( $logs_dir . $log_filename );
				} elseif ( 0 === filesize( $logs_dir . $log_filename ) ) {
					$this->logger->debug( 'deleting empty log file ' . $logs_dir . $log_filename );
					unlink( $logs_dir . $log_filename );
				}
			}
		}

		// TODO: delete the last visited option if it's older than the most recent logs.
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

		if ( ( $this->settings instanceof WooCommerce_Logger_Interface ) && class_exists( WC_Admin_Status::class ) ) {

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

