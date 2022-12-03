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
	 * Used to get the log file filepath for the requested date.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Used to check the request is for a log file from this plugin.
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * Constructor.
	 *
	 * @param API_Interface             $api The logger's main functions.
	 * @param Logger_Settings_Interface $settings The logger settings.
	 * @param LoggerInterface           $logger The logger itself for logging.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Check is this a request to download a log.
	 * Return quietly if not.
	 * Fail hard if nonce is incorrect.
	 * Return if required parameters missing.
	 * Return if plugin slug does not match or if date is malformed.
	 * Invoke `send_private_file()` to download the file.
	 *
	 * This is really only needed when WooCommerce logger is being used because it store the log files in
	 * `/uploads/wc-logs` which has a `.htaccess` preventing downloads.
	 *
	 * @hooked init
	 */
	public function maybe_download_log(): void {

		if ( ! isset( $_GET['download-log'] ) ) {
			return;
		}

		if ( false === check_admin_referer( 'bh-wp-logger-download' ) ) {
			$this->logger->warning( 'Bad nonce when downloading log.' );
			wp_die();
			return; // Needed for tests. @phpstan-ignore-line.
		}

		if ( 'true' !== sanitize_text_field( wp_unslash( $_GET['download-log'] ) ) ) {
			return;
		}

		if ( ! isset( $_GET['page'] ) || ! isset( $_GET['date'] ) ) {
			return;
		}

		$page = sanitize_text_field( wp_unslash( $_GET['page'] ) );

		if ( 0 !== strpos( $page, $this->settings->get_plugin_slug() ) ) {
			return;
		}

		$date = sanitize_text_field( wp_unslash( $_GET['date'] ) );

		if ( 1 !== preg_match( '/\d{4}-\d{2}-\d{2}/', $date ) ) {
			return;
		}

		$files = $this->api->get_log_files( $date );

		if ( ! isset( $files[ $date ] ) ) {
			return;
		}

		$file = $files[ $date ];

		$this->send_private_file( $file );

	}

	/**
	 * Set the correct headers, send the file, die.
	 *
	 * @param string $filepath The requested filename.
	 *
	 * @see Serve_Private_File::send_private_file()
	 *
	 * Nonce was checked above.
	 * phpcs:disable WordPress.Security.NonceVerification.Recommended
	 */
	protected function send_private_file( string $filepath ): void {

		// Add the mimetype header.
		$mime     = wp_check_filetype( $filepath );  // This function just looks at the extension.
		$mimetype = $mime['type'];
		if ( ! $mimetype && function_exists( 'mime_content_type' ) ) {

			$mimetype = mime_content_type( $filepath );  // Use ext-fileinfo to look inside the file.
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
		$client_etag              = isset( $_REQUEST['HTTP_IF_NONE_MATCH'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['HTTP_IF_NONE_MATCH'] ) ) ) : '';
		$client_if_mod_since      = isset( $_REQUEST['HTTP_IF_MODIFIED_SINCE'] ) ? trim( sanitize_text_field( wp_unslash( $_REQUEST['HTTP_IF_MODIFIED_SINCE'] ) ) ) : '';
		$client_if_mod_since_unix = strtotime( $client_if_mod_since );

		if ( $etag === $client_etag || $last_modified_unix <= $client_if_mod_since_unix ) {
			// Return 'not modified' header.
			status_header( 304 );
			die();
		}

		// If we made it this far, just serve the file.
		status_header( 200 );
		// (WP_Filesystem is only loaded for admin requests, not applicable here).
		// phpcs:disable WordPress.WP.AlternativeFunctions.file_system_read_readfile
		readfile( $filepath );
		die();

	}

}
