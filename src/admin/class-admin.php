<?php
/**
 * Adds a
 */

namespace BrianHenryIE\WP_Logger\admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use WPTRT\AdminNotices\Notices;

class Admin {

	/** @var LoggerInterface */
	protected LoggerInterface $logger;

	/** @var Logger_Settings_Interface  */
	protected Logger_Settings_Interface $settings;

	/** @var API_Interface  */
	protected API_Interface $api;

	/**
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param ?LoggerInterface           $logger
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {

		$this->logger   = $logger ?? new NullLogger();
		$this->settings = $settings;
		$this->api      = $api;

		$this->boot_notices();
	}

	protected Notices $notices;

    /**
     *
     */
	public function boot_notices() {

	    // Don't add this unless we're on an admin screen or handling an ajax request.
	    if( ! is_admin() && ( ! defined('DOING_AJAX') || ! DOING_AJAX ) ) {
	        return;
        }

        $this->notices = new Notices();
        $this->notices->boot();
    }

	/**
	 * Show a notice for recent errors in the logs.
	 *
	 * TODO: Do not show on plugin install page.
	 *
	 * @hooked admin_init
	 */
	public function admin_notices() {

		$error_detail_option_name = $this->settings->get_plugin_slug() . '-recent-error-data';

		// If we're on the logs page, don't show the admin notice linking to the logs page.
		if ( isset( $_GET['page'] ) && $this->settings->get_plugin_slug() . '-logs' === $_GET['page'] ) {
			delete_option( $error_detail_option_name );
			return;
		}

		$last_error = get_option( $error_detail_option_name );

		$last_log_time       = get_option( $this->settings->get_plugin_slug() . '-last-log-time', 0 );
		$last_logs_view_time = get_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', 0 );

		if ( false !== $last_error && ( $last_log_time > $last_logs_view_time ) ) {

			$is_dismissed_option_name = "wptrt_notice_dismissed_{$this->settings->get_plugin_slug()}-recent-error";

			// wptrt_notice_dismissed_bh-wp-logger-test-plugin-recent-error

			$error_text = isset( $last_error['message'] ) ? trim( $last_error['message'] ) : '';
			$error_time = isset( $last_error['timestamp'] ) ? $last_error['timestamp'] : '';

			$title   = false;
			$content = "<strong>{$this->settings->get_plugin_name()}</strong>. Error: ";

			if ( ! empty( $error_text ) ) {
				$content .= "\"{$error_text}\" ";
			}

			if ( ! empty( $error_time ) && is_int( $error_time ) ) {
				$content .= ' at ' . gmdate( 'Y-m-d\TH:i:s\Z', $error_time ) . ' UTC.';
				// Link to logs.
				$log_link = $this->api->get_log_url( gmdate( 'Y-m-d', $error_time ) );

			} else {
				$log_link = $this->api->get_log_url();
			}

			if ( ! is_null( $log_link ) ) {
				$content .= ' <a href="' . $log_link . '">View Logs</a>.</p></div>';
			}

			// ID must be globally unique because it is the css id that will be used.
			$this->notices->add(
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
			$on_dismiss = function( $value, $old_value, $option ) use ( $error_detail_option_name, $is_dismissed_option_name ) {
				error_log( 'Should be deleting ' . $error_detail_option_name );
				delete_option( $error_detail_option_name );
				delete_option( $option );
				return $old_value; // When new and old match, it short circuits.
			};
			add_filter( "pre_update_option_{$is_dismissed_option_name}", $on_dismiss, 10, 3 );

			// wptrt_notice_dismissed_bh-wp-logger-test-plugin-recent-error

		}

	}

}
