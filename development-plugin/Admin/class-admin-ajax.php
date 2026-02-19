<?php
/**
 * Handle the test errors we'll create.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BH_WP_Logger_Test_Plugin\Admin;

use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use wpdb;

/**
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * Class Admin_Ajax
 *
 * @package BH_WP_Logger_Test_Plugin\admin
 */
class Admin_Ajax {
	use LoggerAwareTrait;

	/**
	 * Admin_Ajax constructor.
	 *
	 * @param LoggerInterface $logger PSR logger.
	 */
	public function __construct(
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * @hooked wp_ajax_log
	 *
	 * @throws Exception When `missing-log-test-action` = "uncaught-exception" in order to test the behaviour when the exception is not caught.
	 */
	public function handle_request(): void {

		$result            = array();
		$result['error']   = array();
		$result['success'] = array();

		check_ajax_referer( 'logs-test' );

		if ( empty( $_POST['log-test-action'] ) ) {
			$result['error']['missing-log-test-action'] = 'Missing log-test-action parameter.';

		} else {
			$log_test_action = sanitize_text_field( wp_unslash( $_POST['log-test-action'] ) );

			$message = isset( $_POST['message'] ) ? esc_html( sanitize_text_field( wp_unslash( $_POST['message'] ) ) ) : null;
			$context = isset( $_POST['context'] ) ? explode( ',', esc_html( sanitize_text_field( wp_unslash( $_POST['context'] ) ) ) ) : array();

			switch ( $log_test_action ) {
				case 'debug-message':
					$this->logger->debug( $message ?? 'log test debug message', $context );
					break;
				case 'info-message':
					$this->logger->info( $message ?? 'log test info message', $context );
					break;
				case 'notice-message':
					$this->logger->notice( $message ?? 'log test notice message', $context );
					break;
				case 'warning-message':
					$this->logger->warning( $message ?? 'log test warning message', $context );
					break;
				case 'error-message':
					$this->logger->error( $message ?? 'log test error message', $context );
					break;
				case 'deprecated-php':
					trigger_error( 'log test deprecated php', E_USER_DEPRECATED );
					break;
				case 'notice-php':
					trigger_error( 'log test notice php', E_USER_NOTICE );
					break;
				case 'warning-php':
					trigger_error( 'log test warning php', E_USER_WARNING );
					break;
				case 'error-php':
					trigger_error( 'log test error php', E_USER_ERROR );
					break;
				case 'doing_it_wrong_run-wordpress':
					_doing_it_wrong( 'is_allowed_dir', 'The "$dir" argument must be a non-empty string.', '6.2.0' );
					break;
				case 'deprecated_function_run-wordpress':
					_deprecated_function( 'tinymce_include', '2.1.0', 'wp_editor()' );
					break;
				case 'deprecated_argument_run-wordpress':
					_deprecated_argument( 'wp_editor()', '3.9.0', 'TinyMCE editor IDs cannot have brackets.' );
					break;
				case 'deprecated_hook_run-wordpress':
					_deprecated_hook( 'hook_name', 'version', 'replacement', 'message' );
					break;
				case 'uncaught-exception':
					throw new Exception( 'log test exception' );
				case 'delete-transients':
					/** @var wpdb $wpdb */
					global $wpdb;
					/**
					 * No sense caching a DELETE.
					 * There is no wpdb method we could use instead.
					 *
					 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
					 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
					 */
					$delete_query_result = $wpdb->query( 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_%"' );
					if ( false === $delete_query_result ) {
						wp_send_json_error(
							array(
								'error' => 'DELETE SQL query failed',
							),
							500
						);
					}

					$result['success']['deleted_transient_count'] = $delete_query_result;
					break;
				default:
					$result['error']['unknown-log-test-action'] = 'Unknown log-test-action parameter.';
					break;
			}
		}

		if ( ! empty( $result['error'] ) ) {
			wp_send_json( $result, 400 );
		}
		wp_send_json( $result );
	}
}
