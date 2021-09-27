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

namespace BH_WP_Logger_Test_Plugin\admin;

use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Logger as BH_Logger;
use Psr\Log\LoggerInterface;

/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    BH_WP_Logger_Test_Plugin
 * @subpackage BH_WP_Logger_Test_Plugin/admin
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Admin {

	protected Logger_Settings_Interface $logger_settings;

	protected BH_Logger $logger;

	public function __construct( Logger_Settings_Interface $logger_settings, BH_Logger $logger ) {
		$this->logger_settings = $logger_settings;
		$this->logger = $logger;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		wp_enqueue_style( 'bh-wp-logger-test-plugin', plugin_dir_url( __FILE__ ) . 'css/bh-wp-logger-test-plugin-admin.css', array(), time(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		$version = time();

		wp_enqueue_script( 'bh-wp-logger-test-plugin', plugin_dir_url( __FILE__ ) . 'js/bh-wp-logger-test-plugin-admin.js', array( 'jquery' ), $version, true );

	}


	/**
	 * @hooked admin_menu
	 *
	 * Add a WordPress admin UI page, but without any menu linking to it.
	 */
	public function add_page() {

		$icon_url = 'dashicons-text-page';

		add_menu_page(
			'Logs Test',
			'Logs Test',
			'manage_options',
			'logs-test',
			array( $this, 'display_page' ),
			$icon_url
		);

	}

	/**
	 * Registered in @see add_page()
	 */
	public function display_page() {

		$plugin_log_level = $this->logger_settings->get_log_level();

		$plugin_logger_api = $this->logger->get_api();

		$plugin_log_url = $plugin_logger_api->get_log_url();

		$wp_debug = defined( 'WP_DEBUG' ) && WP_DEBUG ? 'enabled' : 'disabled';
		$wp_debug_display = defined( 'WP_DEBUG_DISPLAY' ) && WP_DEBUG_DISPLAY ? 'enabled' : 'disabled';
		$wp_debug_log = 'disabled';
		if( defined( 'WP_DEBUG_LOG' ) ) {
			$wp_debug_log = true === WP_DEBUG_LOG ? 'enabled' : WP_DEBUG_LOG;
		}

		include wp_normalize_path( __DIR__ . '/partials/bh-wp-logger-test-plugin-admin-display.php' );
	}



}
