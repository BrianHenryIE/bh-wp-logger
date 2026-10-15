<?php
/**
 * Before any UI loads, check if the request is for a log file download.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Private_Uploads\Frontend\Serve_Private_File;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;

/**
 * Hook into init, return gracefully if this is not a log download request.
 */
class Init {
	use LoggerAwareTrait;

	/**
	 * Constructor.
	 *
	 * @param API_Interface             $api The logger's main functions, used to get the log file filepath for the requested date.
	 * @param Logger_Settings_Interface $settings The logger settings. Used to check the request is for a log file from this plugin.
	 * @param LoggerInterface           $logger The logger itself for logging.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * Check is this a request to download a log.
	 * Return quietly if not, or if it is for another plugin's log.
	 * Fail hard if the nonce is incorrect or the user cannot manage options.
	 * Return if the date is malformed or there is no log for that date.
	 * Invoke `send_private_file()` to download the file.
	 *
	 * The plugin slug is checked before the nonce and capability: bh-wp-logger could be hooked for many plugins,
	 * each with its own instance of this class, and only the one the request is for should respond to it.
	 *
	 * This is really only needed when WooCommerce logger is being used because it stores the log files in
	 * `/uploads/wc-logs` which has a `.htaccess` preventing downloads.
	 *
	 * @hooked init
	 */
	public function maybe_download_log(): void {

		if ( ! isset( $_GET['download-log'] ) || ! is_string( $_GET['download-log'] )
			|| 'true' !== sanitize_text_field( wp_unslash( $_GET['download-log'] ) )
		) {
			return;
		}

		if (
			! isset( $_GET['page'] ) || ! isset( $_GET['date'] )
			|| ! is_string( $_GET['page'] ) || ! is_string( $_GET['date'] )
		) {
			return;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

		// Match exactly: one plugin's slug may be a prefix of another's.
		if ( $this->settings->get_plugin_slug() . '-logs' !== $page ) {
			return;
		}

		if ( ! isset( $_GET['_wpnonce'] ) || ! is_string( $_GET['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'bh-wp-logger-download' )
		) {
			$this->logger->warning( 'Bad nonce when downloading log.' );
			wp_die( 'Invalid nonce.', 403 );
		} elseif ( ! current_user_can( 'manage_options' ) ) {
			$this->logger->warning( 'User without manage_options capability tried to download log.' );
			wp_die( 'Not permitted.', 403 );
		} else {
			$this->download_log_for_date( sanitize_text_field( wp_unslash( $_GET['date'] ) ) );
		}
	}

	/**
	 * Send the log file for the date, if the date is well-formed and a log file exists for it.
	 *
	 * @param string $date The requested date, expected in `Y-m-d` format.
	 */
	protected function download_log_for_date( string $date ): void {

		if ( 1 !== preg_match( '/^\d{4}-\d{2}-\d{2}$/', $date ) ) {
			return;
		}

		$files = $this->api->get_log_files( $date );

		if ( ! isset( $files[ $date ] ) ) {
			return;
		}

		$this->send_private_file( $files[ $date ] );
	}

	/**
	 * Set the correct headers, send the file, die.
	 *
	 * @param string $filepath The requested filename.
	 *
	 * @see Serve_Private_File::send_private_file()
	 *
	 * Nonce was checked above.
	 *
	 * phpcs:disable WordPress.Security.NonceVerification.Recommended
	 */
	protected function send_private_file( string $filepath ): void {

		// Add the mimetype header.
		$mime     = wp_check_filetype( $filepath );  // This function just looks at the extension.
		$mimetype = $mime['type'];
		if ( ! $mimetype && function_exists( 'mime_content_type' ) ) {

			$mimetype = mime_content_type( $filepath );  // Use `ext-fileinfo` to look inside the file.
		}
		if ( ! $mimetype ) {
			$mimetype = 'application/octet-stream';
		}

		header( 'Content-type: ' . $mimetype ); // always send this.
		header( 'Content-Disposition: attachment; filename="' . basename( $filepath ) . '"' );

		// Add timing headers.
		$date_format        = 'D, d M Y H:i:s T';  // RFC2616 date format for HTTP.
		$last_modified_unix = (int) filemtime( $filepath );
		$last_modified      = gmdate( $date_format, $last_modified_unix );
		$etag               = md5( $last_modified );
		header( "Last-Modified: $last_modified" );
		header( 'ETag: "' . $etag . '"' );
		header( 'Expires: ' . gmdate( $date_format, time() + HOUR_IN_SECONDS ) ); // an arbitrary hour from now.

		// Support for caching.
		$client_etag              = isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && is_string( $_SERVER['HTTP_IF_NONE_MATCH'] )
			? trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) ) : '';
		$client_if_mod_since      = isset( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) && is_string( $_SERVER['HTTP_IF_MODIFIED_SINCE'] )
			? trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_MODIFIED_SINCE'] ) ) ) : '';
		$client_if_mod_since_unix = strtotime( $client_if_mod_since );

		if ( $etag === $client_etag || $last_modified_unix <= $client_if_mod_since_unix ) {
			// Return 'not modified' header.
			status_header( 304 );
			die();
		}

		// If we made it this far, just serve the file.
		status_header( 200 );

		/**
		 * We could use {@see \WP_Filesystem_Direct::get_contents()} but streaming the file prevents memory issues.
		 *
		 * phpcs:disable WordPress.WP.AlternativeFunctions.file_system_operations_readfile
		 */
		readfile( $filepath );

		die();
	}
}
