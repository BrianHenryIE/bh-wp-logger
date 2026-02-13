<?php
/**
 * Adds link to Logs on the "Plugin updated successfully" page.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * Appends a Logs link to the "Return to plugins installer" link on the plugin update page.
 */
class Plugin_Installer {

	/**
	 * Constructor.
	 *
	 * @param Logger_Settings_Interface $settings The plugin settings.
	 */
	public function __construct(
		protected Logger_Settings_Interface $settings
	) {
	}

	/**
	 * Add the Logs page link to the existing links.
	 *
	 * @hooked install_plugin_complete_actions
	 * @see \Plugin_Installer_Skin::after()
	 *
	 * @param string[] $install_actions Array of plugin action links.
	 * @param object   $_api            Object containing WordPress.org API plugin data. Empty
	 *                                  for non-API installs, such as when a plugin is installed
	 *                                  via upload.
	 * @param string   $plugin_file     Path to the plugin file relative to the plugins directory.
	 *
	 * @return string[]
	 */
	public function add_logs_link( $install_actions, $_api, $plugin_file ): array {

		if ( $plugin_file !== $this->settings->get_plugin_basename() ) {
			return $install_actions;
		}

		$install_actions[] = '•';

		$logs_url          = admin_url( '/admin.php?page=' . $this->settings->get_plugin_slug() . '-logs' );
		$install_actions[] = '<a href="' . esc_url( $logs_url ) . '">Go to ' . esc_html( $this->settings->get_plugin_name() ) . ' logs</a>';

		return $install_actions;
	}
}
