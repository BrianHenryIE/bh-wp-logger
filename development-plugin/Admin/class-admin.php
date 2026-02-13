<?php
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WP_Logger_Test_Plugin
 * @subpackage BH_WP_Logger_Test_Plugin/admin
 */

namespace BH_WP_Logger_Test_Plugin\Admin;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger as BH_Logger;
use BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 */
class Admin {

	public function __construct(
		protected Logger_Settings_Interface $logger_settings,
		protected BH_Logger $logger
	) {
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		$version = time();

		$url = WP_PLUGIN_URL . '/bh-wp-logger-development-plugin/Admin/css/bh-wp-logger-development-plugin-admin.css';

		wp_enqueue_style( 'bh-wp-logger-development-plugin', $url, array(), $version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$version = time();

		$url = WP_PLUGIN_URL . '/bh-wp-logger-development-plugin/Admin/js/bh-wp-logger-development-plugin-admin.js';

		wp_enqueue_script( 'bh-wp-logger-development-plugin', $url, array( 'jquery' ), $version, true );
	}

	/**
	 * Register the callback to the new page, adding the link in the admin menu.
	 *
	 * @hooked admin_menu
	 */
	public function add_page() {

		$icon_url = 'dashicons-text-page';

		add_menu_page(
			'Logs Test',
			'Logs Test',
			'manage_options',
			'logs-test',
			array( $this, 'display_page' ),
			$icon_url,
			2
		);
	}

	/**
	 * Registered in @see add_page()
	 */
	public function display_page() {

		$plugin_log_level = $this->logger_settings->get_log_level();

		$is_woocommerce_logger = $this->logger_settings instanceof WooCommerce_Logger_Settings_Interface ? 'yes' : 'no';

		/** @var API_Interface $plugin_logger_api */
		$plugin_logger_api = $this->logger;

		$log_files       = $plugin_logger_api->get_log_files();
		$plugin_log_file = array_pop( $log_files );

		$plugin_log_url = $plugin_logger_api->get_log_url();

		$wp_debug         = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'enabled' : 'disabled';
		$wp_debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'enabled' : 'disabled';
		$wp_debug_log     = 'disabled';
		if ( defined( 'WP_DEBUG_LOG' ) ) {
			$wp_debug_log = true === WP_DEBUG_LOG ? 'enabled' : WP_DEBUG_LOG;
		}

		include wp_normalize_path( __DIR__ . '/partials/bh-wp-logger-development-plugin-admin-display.php' );
	}
}
