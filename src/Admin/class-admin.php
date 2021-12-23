<?php
/**
 * Enqueue the logs page javascript for changing date and deleting logs.
 * Checks the plugin slug and only adds the script on the logs page for this plugin.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

/**
 * Enqueue (register) the JavaScript with WordPress to add to the page.
 *
 * @see wp_enqueue_script
 */
class Admin {

	/**
	 * The logger settings,
	 *
	 * @uses Logger_Settings_Interface::get_plugin_slug()
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * Admin constructor.
	 *
	 * @param Logger_Settings_Interface $settings Settings describing the plugin this logger is for.
	 */
	public function __construct( Logger_Settings_Interface $settings ) {

		$this->settings = $settings;
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @hooked admin_enqueue_scripts
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {

		$slug = $this->settings->get_plugin_slug();

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || sanitize_key( $_GET['page'] ) !== $slug . '-logs' ) {
			return;
		}

		// This is the bh-wp-logger version, not the plugin version.
		$version = '1.0.0';

		$url = plugin_dir_url( __FILE__ ) . 'js/bh-wp-logger-admin.js';
		wp_enqueue_script( 'bh-wp-logger-admin-logs-page-' . $slug, $url, array( 'jquery' ), $version, true );
	}

}
