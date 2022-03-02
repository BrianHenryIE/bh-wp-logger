<?php
/**
 * Add an admin notice for new errors.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WPTRT\AdminNotices\Notices;

/**
 *
 *
 * @see https://github.com/WPTT/admin-notices
 */
class Admin_Notices extends Notices {

	use LoggerAwareTrait;

	/** @var Logger_Settings_Interface  */
	protected Logger_Settings_Interface $settings;

	/** @var API_Interface  */
	protected API_Interface $api;

	/**
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param ?LoggerInterface          $logger
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {

		$this->setLogger( $logger ?? new NullLogger() );
		$this->settings = $settings;
		$this->api      = $api;
	}

	protected function get_error_detail_option_name(): string {
		return $this->settings->get_plugin_slug() . '-recent-error-data';
	}

	/**
	 * The last error is stored in the option `plugin-slug-recent-error-data` as an array with `message` and `timestamp`.
	 *
	 * @see Admin_Notices::get_error_detail_option_name()
	 *
	 * @return ?array{message: string, timestamp: string}
	 */
	protected function get_last_error(): ?array {
		$last_error = get_option( $this->get_error_detail_option_name(), null );
		return $last_error;
	}

	/**
	 * Show a notice for recent errors in the logs.
	 *
	 * TODO: Do not show on plugin install page.
	 *
	 * hooked earlier than 10 because Notices::boot() also hooks a function on admin_init that needs to run after this.
	 *
	 * @hooked admin_init
	 */
	public function admin_notices(): void {

		$error_detail_option_name = $this->get_error_detail_option_name();

		// If we're on the logs page, don't show the admin notice linking to the logs page.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && $this->settings->get_plugin_slug() . '-logs' === sanitize_key( $_GET['page'] ) ) {
			delete_option( $error_detail_option_name );
			return;
		}

		$last_error = $this->get_last_error();

		$last_log_time       = $this->api->get_last_log_time();
		$last_logs_view_time = $this->api->get_last_logs_view_time();

		// TODO: This should be comparing $last_error time?
		if ( ! empty( $last_error ) && ( is_null( $last_logs_view_time ) || $last_log_time > $last_logs_view_time ) ) {

			$is_dismissed_option_name = "wptrt_notice_dismissed_{$this->settings->get_plugin_slug()}-recent-error";

			// wptrt_notice_dismissed_bh-wp-logger-test-plugin-recent-error

			$error_text = isset( $last_error['message'] ) ? trim( $last_error['message'] ) : '';
			$error_time = isset( $last_error['timestamp'] ) ? (int) $last_error['timestamp'] : '';

			$title   = '';
			$content = "<strong>{$this->settings->get_plugin_name()}</strong>. Error: ";

			if ( ! empty( $error_text ) ) {
				$content .= "\"{$error_text}\" ";
			}

			if ( ! empty( $error_time ) && is_numeric( $error_time ) ) {
				$content .= ' at ' . gmdate( 'Y-m-d\TH:i:s\Z', $error_time ) . ' UTC.';

				// wp_timezone();

				// Link to logs.
				$log_link = $this->api->get_log_url( gmdate( 'Y-m-d', $error_time ) );

			} else {
				$log_link = $this->api->get_log_url();
			}

			if ( ! empty( $log_link ) ) {
				$content .= ' <a href="' . $log_link . '">View Logs</a>.</p></div>';
			}

			// ID must be globally unique because it is the css id that will be used.
			$this->add(
				$this->settings->get_plugin_slug() . '-recent-error',
				$title,   // The title for this notice.
				$content, // The content for this notice.
				array(
					'scope' => 'global',
					'type'  => 'error',
				)
			);

			/**
			 * When the notice is dismissed, delete the error detail option (to stop the notice being recreated),
			 * and delete the saved dismissed flag (which would prevent it displaying when the next error occurs).
			 *
			 * @see update_option()
			 */
			$on_dismiss = function( $value, $old_value, $option ) use ( $error_detail_option_name ) {
				delete_option( $error_detail_option_name );
				delete_option( $option );
				return $old_value; // When new and old match, it short circuits.
			};
			add_filter( "pre_update_option_{$is_dismissed_option_name}", $on_dismiss, 10, 3 );

			// wptrt_notice_dismissed_bh-wp-logger-test-plugin-recent-error

		}

	}

}
