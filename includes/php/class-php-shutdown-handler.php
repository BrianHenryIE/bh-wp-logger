<?php
/**
 * When PHP is shutting down, check for any errors, log if appropriate.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\PHP;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Listens for errors from register_shutdown_function and logs them to the PSR logger if they are from this plugin.
 *
 * @see register_shutdown_function()
 */
class PHP_Shutdown_Handler {

	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param API_Interface             $api The main logger functions. Used to determine is the error related to this plugin.
	 * @param Logger_Settings_Interface $settings The logger settings. Not used.
	 * @param LoggerInterface           $logger A PSR logger.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * This should maybe be run immediately rather than hooked. It _is_ hooked to enable it to be unhooked.
	 *
	 * @hooked plugins_loaded
	 */
	public function init(): void {
		register_shutdown_function( array( $this, 'handle' ) );
	}

	/**
	 * The handler itself. Check is the error related to this plugin, then logs an error to the PSR logger.
	 */
	public function handle(): void {

		/**
		 * The error from PHP.
		 *
		 * @var ?array{type:int, message:string, file:string, line:int} $error
		 */
		$error = error_get_last();

		if ( empty( $error ) ) {
			return;
		}

		if ( ! $this->api->is_file_from_plugin( $error['file'] ) ) {
			return;
		}

		// "Clears the most recent errors, making it unable to be retrieved with error_get_last().".
		error_clear_last();

		$this->logger->error( $error['message'], $error );
	}
}
