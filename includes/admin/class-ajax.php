<?php
/**
 * Handle AJAX requests from the log table page (or custom settings page).
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * Handle delete and delete-all actions.
 *
 * @uses API_Interface::delete_log()
 * @uses API_Interface::delete_all_logs()
 * @uses Logger_Settings_Interface::get_plugin_slug()
 */
class AJAX {

	/**
	 * AJAX constructor.
	 *
	 * @param API_Interface             $api Implementation of the plugin's main functions.
	 * @param Logger_Settings_Interface $settings The current settings for the logger / Settings describing the plugin this logger is for.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings
	) {
	}

	/**
	 * Delete a single log file.
	 *
	 * Request body should contain:
	 * * `action` "bh_wp_logger_logs_delete".
	 * * `date_to_delete` in `Y-m-d` format, e.g. 2022-03-02.
	 * * `plugin_slug` containing the slug used in settings.
	 * * `_wpnonce` with action `bh-wp-logger-delete`.
	 *
	 * Response format will be:
	 * array{success: bool, message: ?string}.
	 *
	 * @hooked wp_ajax_bh_wp_logger_logs_delete
	 *
	 * @uses API_Interface::delete_log()
	 */
	public function delete(): void {

		if ( ! $this->is_request_for_this_plugin() ) {
			return;
		}

		$error = $this->get_authorization_error();

		if ( ! is_null( $error ) ) {
			wp_send_json_error( array( 'message' => $error ), 403 );
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in get_authorization_error().
		} elseif ( ! isset( $_POST['date_to_delete'] ) || ! is_string( $_POST['date_to_delete'] ) ) {
			wp_send_json_error( array( 'message' => 'Missing date_to_delete.' ), 400 );
		} else {
			// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Verified in get_authorization_error().
			$this->send_result( $this->api->delete_log( sanitize_key( $_POST['date_to_delete'] ) ) );
		}
	}

	/**
	 * Delete all log files for this plugin.
	 *
	 * Request body should contain:
	 * * `action` "bh_wp_logger_logs_delete_all".
	 * * `plugin_slug` containing the slug used in settings.
	 * * `_wpnonce` with action "bh-wp-logger-delete".
	 *
	 * Response format will be:
	 * array{success: bool, message: ?string}.
	 *
	 * @hooked wp_ajax_bh_wp_logger_logs_delete_all
	 *
	 * @uses API_Interface::delete_all_logs()
	 */
	public function delete_all(): void {

		if ( ! $this->is_request_for_this_plugin() ) {
			return;
		}

		$error = $this->get_authorization_error();

		if ( ! is_null( $error ) ) {
			wp_send_json_error( array( 'message' => $error ), 403 );
		} else {
			$this->send_result( $this->api->delete_all_logs() );
		}
	}

	/**
	 * Is this request for this plugin's logs?
	 *
	 * The bh-wp-logger library could be hooked for many plugins, each with its own instance of this class handling the same
	 * AJAX action. Check the slug first, so handlers for other plugins return quietly and leave the request to
	 * the plugin it is for.
	 */
	protected function is_request_for_this_plugin(): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing -- Only used to choose which plugin's handler responds.
		return isset( $_POST['plugin_slug'] ) && is_string( $_POST['plugin_slug'] )
			// phpcs:ignore WordPress.Security.NonceVerification.Missing
			&& $this->settings->get_plugin_slug() === sanitize_key( $_POST['plugin_slug'] );
	}

	/**
	 * Verify the nonce and that the user can manage options (the capability required to view the logs page).
	 *
	 * The request is known to be for this plugin, so the caller responds with this error rather than returning silently.
	 *
	 * @return ?string Error message, or null when the request is authorized.
	 */
	protected function get_authorization_error(): ?string {

		if ( ! isset( $_POST['_wpnonce'] ) || ! is_string( $_POST['_wpnonce'] )
			|| ! wp_verify_nonce( sanitize_key( $_POST['_wpnonce'] ), 'bh-wp-logger-delete' )
		) {
			return 'Invalid nonce.';
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return 'Not permitted.';
		}

		return null;
	}

	/**
	 * Send the API result as a JSON success or error response.
	 *
	 * @param array{success:bool} $result The result of deleting the log file(s).
	 */
	protected function send_result( array $result ): void {
		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result );
		}
	}
}
