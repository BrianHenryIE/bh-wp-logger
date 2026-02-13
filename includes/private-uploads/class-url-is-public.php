<?php
/**
 * This logger library uses bh-wp-private-uploads library to ensure the logs directory is not publicly accessible.
 * i.e. it automatically creates a .htaccess on Apache servers, and shows an admin notice warning for Nginx.
 *
 * This class customises the warning message.
 *
 * This is not relevant when WooCommerce logger is in use.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Private_Uploads;

/**
 * Filter the bh-wp-private-uploads admin-notice that is shown when the logs url is public.
 *
 * @see \BrianHenryIE\WP_Private_Uploads\Admin\Admin_Notices::admin_notices()
 */
class URL_Is_Public {

	/**
	 * Change the warning message to say:
	 * "The logs directory is, and should not be, publicly accessible at the URL: %s. Please update your webserver configuration to block access to that folder."
	 *
	 * @hooked bh_wp_private_uploads_url_is_public_warning_{$this->settings->get_plugin_slug()}._logger
	 *
	 * @param string $message The default message.
	 * @param string $url The publicly accessible URL.
	 *
	 * @return string
	 */
	public function change_warning_message( string $message, string $url ): string {

		/* translators: %s: The URL where the log files are accessible. */
		$new_message = sprintf( __( 'The logs directory is, and should not be, publicly accessible at the URL: %s. Please update your webserver configuration to block access to that folder.', 'bh-wp-logger' ), $url );

		return $new_message;
	}
}
