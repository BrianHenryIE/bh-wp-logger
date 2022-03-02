<?php
/**
 * Handle the test errors we'll create.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BH_WP_Logger_Test_Plugin\Admin;

use Psr\Log\LoggerInterface;

/**
 * phpcs:disable WordPress.PHP.DevelopmentFunctions
 *
 * Class Admin_Ajax
 *
 * @package BH_WP_Logger_Test_Plugin\admin
 */
class Admin_Ajax {

	/**
	 * Admin_Ajax constructor.
	 *
	 * @param LoggerInterface $logger
	 */
	public function __construct( $logger ) {
		$this->logger = $logger;
	}

	/**
	 * @hooked wp_ajax_log
	 */
	public function handle_request() {

		$result            = array();
		$result['error']   = array();
		$result['success'] = array();

		// if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'logs-test' ) ) {
		//
		// $result['error']['nonce-failed'] ='<em>Nonce verification failed.</em> Try reloading the page.';
		//
		// TODO: Should not return HTTP status 200? ... 403.
		// wp_send_json( $result, 403 );
		//
		// }

		// Validate input.

		if ( ! isset( $_POST['log-test-action'] ) || empty( $_POST['log-test-action'] ) ) {
			$result['error']['missing-log-test-action'] = 'Missing log-test-action parameter.';

		} else {
			$log_test_action = wp_unslash( $_POST['log-test-action'] );

			$message = isset( $_POST['message'] ) ? esc_html( wp_unslash( $_POST['message'] ) ) : null;
			$context = isset( $_POST['context'] ) ? explode( ',', esc_html( wp_unslash( $_POST['context'] ) ) ) : array();

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
				case 'uncaught-exception':
					throw new \Exception( 'log test exception' );
				case 'delete-transients':
					global $wpdb;
					$result = $wpdb->query( 'DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_%"' );
					$result = array();
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
