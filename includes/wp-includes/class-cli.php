<?php
/**
 * Add CLI command for deleting logs.
 *
 * Handy for resetting state between a plugin's E2E tests.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Trait;
use Exception;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_CLI;

/**
 * `wp {$cli_base} licence get-status`
 */
class CLI {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param API_Interface $api The main API class where the functionality is implemented.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * Register the WP-CLI commands.
	 *
	 * Use `null` to disable CLI commands.
	 * The settings trait uses the plugin slug as the default CLI base.
	 *
	 * @see Logger_Settings_Trait::get_cli_base()
	 */
	public function register_commands(): void {

		$cli_base = $this->settings->get_cli_base();

		if ( is_null( $cli_base ) ) {
			return;
		}

		try {
			WP_CLI::add_command( "{$cli_base} logger delete-all", array( $this, 'delete_all_logs' ) );
		} catch ( Exception $e ) {
			$this->logger->error(
				'Failed to register WP CLI commands: ' . $e->getMessage(),
				array( 'exception' => $e )
			);
		}
	}

	/**
	 * Delete all logs.
	 *
	 * ## EXAMPLES
	 *
	 *   # Delete all logs for the plugin.
	 *   $ wp $cli_base logger delete-all
	 *   Success: Deleted 12 log files.
	 *
	 * @param string[]             $args The unlabelled command line arguments.
	 * @param array<string,string> $assoc_args The labelled command line arguments.
	 *
	 * @see API_Interface::get_licence_details()
	 */
	public function delete_all_logs( array $args, array $assoc_args ): void {

		try {
			$result = $this->api->delete_all_logs();
		} catch ( Exception $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		WP_CLI::success( 'Deleted ' . count( $result['deleted_files'] ) . ' log files.' );
	}
}
