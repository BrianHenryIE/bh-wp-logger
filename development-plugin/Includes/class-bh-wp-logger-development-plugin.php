<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BH_WP_Logger_Test_Plugin\WP_Includes;

use BrianHenryIE\WP_Logger\Logger as BH_Logger;
use BH_WP_Logger_Test_Plugin\Admin\Admin;
use BH_WP_Logger_Test_Plugin\Admin\Admin_Ajax;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * frontend-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    BH_WP_Logger_Test_Plugin
 * @subpackage BH_WP_Logger_Test_Plugin/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class BH_WP_Logger_Test_Plugin {

	/**
	 * Add actions and filters.
	 *
	 * @param Logger_Settings_Interface $settings The configuration the consuming plugin passed to bh-wp-logger for initialisation.
	 * @param BH_Logger|LoggerInterface $logger PSR3.
	 */
	public function __construct(
		protected Logger_Settings_Interface $settings,
		protected BH_Logger|LoggerInterface $logger
	) {
		$this->define_admin_hooks();
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 */
	protected function define_admin_hooks(): void {

		$plugin_admin = new Admin( $this->settings, $this->logger );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $plugin_admin, 'add_page' ) );

		// Handle actions on the admin page.
		$admin_ajax = new Admin_Ajax( $this->logger );
		add_action( 'wp_ajax_log', array( $admin_ajax, 'handle_request' ) );
	}
}
