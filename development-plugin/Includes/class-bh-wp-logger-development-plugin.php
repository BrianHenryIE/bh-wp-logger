<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * frontend-facing side of the site and the admin area.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WP_Logger_Test_Plugin
 * @subpackage BH_WP_Logger_Test_Plugin/includes
 */

namespace BH_WP_Logger_Test_Plugin\WP_Includes;

use BrianHenryIE\WP_Logger\Logger as BH_Logger;
use BH_WP_Logger_Test_Plugin\Admin\Admin;
use BH_WP_Logger_Test_Plugin\Admin\Admin_Ajax;
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
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @since    1.0.0
	 *
	 * @param LoggerInterface $logger The logger we're testing!
	 */
	public function __construct(
		protected $settings,
		protected BH_Logger $logger
	) {

		$this->set_locale();
		$this->define_admin_hooks();
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function set_locale() {

		$plugin_i18n = new I18n();

		add_action( 'plugins_loaded', array( $plugin_i18n, 'load_plugin_textdomain' ) );
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	protected function define_admin_hooks() {

		$plugin_admin = new Admin( $this->settings, $this->logger );

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $plugin_admin, 'add_page' ) );

		// Handle actions on the admin page.
		$admin_ajax = new Admin_Ajax( $this->logger );
		add_action( 'wp_ajax_log', array( $admin_ajax, 'handle_request' ) );
	}
}
