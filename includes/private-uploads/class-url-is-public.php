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

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * Filter the bh-wp-private-uploads admin-notice that is shown when the logs url is public.
 *
 * @see \BrianHenryIE\WP_Private_Uploads\Admin\Admin_Notices::admin_notices()
 */
class URL_Is_Public {

	/**
	 * Constructor.
	 *
	 * @param Logger_Settings_Interface $settings The logger settings, whose plugin slug identifies this logger's private-uploads instance.
	 */
	public function __construct(
		protected Logger_Settings_Interface $settings,
	) {
	}

	/**
	 * Change the warning message to say:
	 * "The logs directory is, and should not be, publicly accessible at the URL: %s. Please update your webserver configuration to block access to that folder."
	 *
	 * The filter fires for every private-uploads instance, so the message is only changed for this
	 * logger's own instance (whose plugin slug is the logger's plugin slug suffixed with `_logger`,
	 * {@see \BrianHenryIE\WP_Logger\Logger}).
	 *
	 * @hooked bh_wp_private_uploads_url_is_public_warning
	 * @see \BrianHenryIE\WP_Logger\WP_Includes\Plugin_Logger_Actions::add_private_uploads_hooks()
	 *
	 * @param string $message        The default message. Overridden for this logger's own instance.
	 * @param string $plugin_slug    The plugin slug of the private uploads instance whose URL is public.
	 * @param string $post_type_name The post type name of the private uploads instance whose URL is public.
	 * @param string $url            The publicly accessible URL.
	 *
	 * @return string
	 */
	public function change_warning_message( string $message, string $plugin_slug, string $post_type_name, string $url ): string {

		if ( $this->settings->get_plugin_slug() . '_logger' !== $plugin_slug ) {
			return $message;
		}

		return sprintf(
			/* translators: %s: The URL where the log files are accessible. */
			__(
				'The logs directory is, and should not be, publicly accessible at the URL: %s. Please update your webserver configuration to block access to that folder.',
				'bh-wp-logger'
			),
			$url
		);
	}
}
