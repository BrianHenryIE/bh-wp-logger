<?php
/**
 * Add an admin notice for new errors.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use DateTimeImmutable;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WPTRT\AdminNotices\Notices;

/**
 * @uses API_Interface::api->get_last_log_time()
 * @uses API_Interface::get_last_logs_view_time()
 * @uses API_Interface::get_log_url()
 * @uses Logger_Settings_Interface::get_plugin_slug()
 * @uses Logger_Settings_Interface::get_plugin_name()
 *
 * @see https://github.com/WPTT/admin-notices
 */
class Admin_Notices extends Notices {

	use LoggerAwareTrait;

	/**
	 * @param API_Interface             $api The main functions.
	 * @param Logger_Settings_Interface $settings The configured settings.
	 * @param LoggerInterface           $logger PSR logger for recording errors that happen within this class.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * The wp_option name that a/any recent error has been saved to.
	 *
	 * @see BH_WP_PSR_Logger::log()
	 */
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
		$last_error = get_option( $this->get_error_detail_option_name() );
		if ( is_array( $last_error )
			&& 2 === count( $last_error )
			&& isset( $last_error['message'] )
			&& isset( $last_error['timestamp'] )
			&& is_string( $last_error['message'] )
			&& is_string( $last_error['timestamp'] )
		) {
			return $last_error;
		}
		return null;
	}

	/**
	 * Show a notice for recent errors in the logs.
	 *
	 * TODO: Check file exists before linking to it.
	 *
	 * hooked earlier than 10 because Notices::boot() also hooks a function on admin_init that needs to run after this.
	 *
	 * @hooked admin_init
	 */
	public function admin_notices(): void {

		// We don't need to register the admin notice except to display it and to handle the dismiss button.
		if ( ! is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		// Don't show during plugin installs.
		/** @var string $pagenow */
		global $pagenow;
		if ( 'updater.php' === $pagenow ) {
			return;
		}

		// Check is the ajax request relevant.
		if ( wp_doing_ajax() ) {

			$action = 'wptrt_dismiss_notice_development-plugin-recent-error';
			if ( ! isset( $_POST['action'] )
				|| ! is_string( $_POST['action'] )
				|| 'wptrt_dismiss_notice' !== sanitize_key( wp_unslash( $_POST['action'] ) )
				|| false === check_admin_referer( $action, 'nonce' )
			) {

				return;
			}
		}

		$error_detail_option_name = $this->get_error_detail_option_name();

		// If we're on the logs page, don't show the admin notice linking to the logs page.
		/** @var string $plugin_page */
		global $plugin_page;
		if ( 'admin.php' === $pagenow && $this->settings->get_plugin_slug() . '-logs' === $plugin_page ) {
			delete_option( $error_detail_option_name );
			return;
		}

		$last_error = $this->get_last_error();

		$last_log_time       = $this->api->get_last_log_time();
		$last_logs_view_time = $this->api->get_last_logs_view_time();

		// TODO: This should be comparing $last_error time?
		if (
			! empty( $last_error )
			&&
			( is_null( $last_logs_view_time ) || $last_log_time > $last_logs_view_time )
		) {

			/**
			 * E.g. "wptrt_notice_dismissed_bh-wp-logger-development-plugin-recent-error".
			 */
			$is_dismissed_option_name = "wptrt_notice_dismissed_{$this->settings->get_plugin_slug()}-recent-error";

			$error_text = trim( $last_error['message'] );
			$error_time = (int) $last_error['timestamp'];

			$title   = '';
			$content = "<strong>{$this->settings->get_plugin_name()}</strong>. Error: \"{$error_text}\" ";

			$content .= ' at ' . gmdate( 'Y-m-d H:i:s', $error_time ) . ' UTC';

			if ( '+00:00' !== wp_timezone()->getName() ) {

				$content .= ' (';
				$content .= ( new DateTimeImmutable( "@{$error_time}" ) )->setTimezone( wp_timezone() )->format( 'Y-m-d H:i:s' );
				$content .= ' ' . wp_timezone()->getName() . ')';
			}

			$content .= ' – ' . human_time_diff( $error_time, time() ) . ' ago';

			$content .= '.';

				// Link to logs.
			$log_link = $this->api->get_log_url( gmdate( 'Y-m-d', $error_time ) );

			$content .= ' <a href="' . $log_link . '">View Logs</a>.</p></div>';

			// ID must be globally unique because it is the CSS id that will be used.
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
			$on_dismiss = function ( $value, $old_value, string $option_name ) use ( $error_detail_option_name ) {
				delete_option( $error_detail_option_name );
				delete_option( $option_name );
				return $old_value; // When new and old match, it short-circuits.
			};
			add_filter( "pre_update_option_{$is_dismissed_option_name}", $on_dismiss, 10, 3 );
		}
	}
}
