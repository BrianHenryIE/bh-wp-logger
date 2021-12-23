<?php
/**
 * When PHP is shutting down, check for any errors, log if appropriate.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * @see register_shutdown_function()
 */
class PHP_Shutdown_Handler {

	use LoggerAwareTrait;

	protected API_Interface $api;

	protected Logger_Settings_Interface $settings;

	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $logger ) {
		$this->api      = $api;
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	public function init(): void {
		register_shutdown_function( array( $this, 'handle' ) );
	}

	/**
	 *
	 * @see WP_Fatal_Error_Handler::handle()
	 *
	 * @return void
	 */
	public function handle(): void {
		$error = error_get_last();

		if ( empty( $error ) ) {
			return;
		}

		if ( ! $this->api->is_file_from_plugin( $error['file'] ) ) {
			return;
		}

		$this->logger->error( $error['message'], $error );
	}
}
