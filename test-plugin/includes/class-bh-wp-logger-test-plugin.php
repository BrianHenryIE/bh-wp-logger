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

namespace BH_WP_Logger_Test_Plugin\includes;

use BH_WP_Logger_Test_Plugin\admin\Admin;
use BH_WP_Logger_Test_Plugin\admin\Admin_Ajax;

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

	protected $logger;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the frontend-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct( $logger ) {
		if ( defined( 'BH_WP_LOGGER_TEST_PLUGIN_VERSION' ) ) {
			$this->version = BH_WP_LOGGER_TEST_PLUGIN_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'bh-wp-logger-test-plugin';

		$this->logger = $logger;

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

		$plugin_admin = new Admin();

		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_styles' ) );
		add_action( 'admin_enqueue_scripts', array( $plugin_admin, 'enqueue_scripts' ) );

		add_action( 'admin_menu', array( $plugin_admin, 'add_page' ) );

		// Handle actions on the admin page.
		$admin_ajax = new Admin_Ajax( $this->logger );
		add_action( 'wp_ajax_log', array( $admin_ajax, 'handle_request' ) );

	}

}
