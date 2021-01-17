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

	protected $plugin_name = 'bh-wp-logger-test-plugin';

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/bh-wp-logger-test-plugin-admin.css', array(), time(), 'all' );

	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {

		/**
		 * This function is provided for demonstration purposes only.
		 *
		 * An instance of this class should be passed to the run() function
		 * defined in Loader as all of the hooks are defined
		 * in that particular class.
		 *
		 * The Loader will then create the relationship
		 * between the defined hooks and the functions defined in this
		 * class.
		 */

		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/bh-wp-logger-test-plugin-admin.js', array( 'jquery' ), time(), false );

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

		include wp_normalize_path( __DIR__ . '/partials/bh-wp-logger-test-plugin-admin-display.php' );
	}


}
