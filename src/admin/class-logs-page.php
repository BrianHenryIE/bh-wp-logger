<?php
/**
 * The UI around the logs table.
 *
 * E.g. /wp-admin/admin.php?page=bh-wp-logger-test-plugin-logs.
 *
 * TODO: Add "send to plugin developer" button.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Functions for registering a "hidden menu" item, to add the wp-admin page to display the logs.
 */
class Logs_Page {

	use LoggerAwareTrait;

	/**
	 * The logger settings. i.e. what is the plugin slug this logger is for?
	 *
	 * @uses \BrianHenryIE\WP_Logger\Logger_Settings_Interface::get_plugin_slug()
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * Used to get the list of log files.
	 * Needed to instantiate the table.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Logs_Page constructor.
	 *
	 * @param API_Interface             $api The main functions of the logger.
	 * @param Logger_Settings_Interface $settings The configuration used to set up the logger.
	 * @param BH_WP_PSR_Logger          $logger The logger itself, for logging.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $logger = null ) {

		$this->setLogger( $logger ?? new NullLogger() );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add a WordPress admin UI page, but without any menu linking to it.
	 *
	 * @hooked admin_menu
	 *
	 * @see wp-admin/menu.php
	 */
	public function add_page(): void {

		$logs_slug  = "{$this->settings->get_plugin_slug()}-logs";
		$menu_title = 'Logs';

		$parent_slug = '';

		global $menu;
		foreach ( $menu as $menu_item ) {
			if ( stristr( $menu_item[0], 'logs' ) || stristr( $menu_item[2], 'logs' ) || stristr( $menu_item[3], 'logs' ) ) {
				$parent_slug = $menu_item[2];
				$menu_title  = $this->settings->get_plugin_name();
				break;
			}
		}

		add_submenu_page(
			$parent_slug,
			__( 'Logs', 'bh-wp-logger' ),
			$menu_title,
			'manage_options',
			$logs_slug,
			array( $this, 'display_page' )
		);
	}

	/**
	 * Display the page.
	 * Record the last visited time.
	 *
	 * Registered above.
	 *
	 * @see add_page()
	 */
	public function display_page(): void {

		echo '<div class="wrap">';

		echo '<h1>';
		echo esc_html( $this->settings->get_plugin_name() );
		echo '</h1>';

		$log_files = $this->api->get_log_files();

		if ( empty( $log_files ) ) {
			// This will occur e.g. immediately after deleting all logs.
			echo '<p>No logs to display.</p>';
			echo '</div>';
			return;
		}

		$logs_table = new Logs_List_Table( $this->api, $this->settings, $this->logger );

		// Set date here?

		// Show a list of date to switch between dates.
		echo '<label for="log_date">Log date:</label>';
		echo '<select name="log_date" id="log_date">';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$chosen_date = isset( $_GET['log_date'] ) ? sanitize_key( $_GET['log_date'] ) : array_key_last( $log_files );

		// TODO: Allow filtering here to add external log files, e.g. from Authorize.net SDK.
		foreach ( $log_files as $date => $path ) {
			$date_formatted = $date;
			echo '<option value="' . esc_attr( $date ) . '"';
			if ( $date === $chosen_date ) {
				echo ' selected';
			}
			echo '>' . esc_html( $date_formatted ) . '</option>';
		}
		echo '</select>';

		echo '<button name="deleteButton" id="deleteButton" data-date="' . esc_attr( $chosen_date ) . '" class="button logs-page button-primary">Delete ' . esc_html( $chosen_date ) . ' logs</button>';
		echo '<button name="deleteAllButton" id="deleteAllButton" class="button logs-page button-secondary">Delete all logs</button>';

		wp_nonce_field( 'bh-wp-logger-delete', 'delete_logs_wpnonce' );

		echo '<p>Current log level: <b>' . esc_html( ucfirst( $this->settings->get_log_level() ) ) . '</b></p>';

		// If this is in the logger's private-uploads directory, then it already should be accessible, but if it's in the wc-logs folder, it will not be.
		$download_url = wp_nonce_url( admin_url( 'admin.php?page=' . $this->settings->get_plugin_slug() . '&date=' . $date . '&download-log=true' ), 'bh-wp-logger-download' );
		$filepath     = $log_files[ $chosen_date ];
		$filename     = basename( $filepath );
		// TODO: Show file size here. Show number of entries.
		echo '<p>Displaying log file at <a href="' . esc_url( $download_url ) . '" download="' . esc_attr( $filename ) . '"><code>' . esc_html( $filepath ) . '</code></a></p>';

		// Maybe should use set file?
		$logs_table->set_date( $chosen_date );
		$logs_table->prepare_items();

		$this->api->set_last_logs_view_time();

		$logs_table->display();

		echo '</div>';
	}

	/**
	 * Enqueue the logs page javascript for changing date and deleting logs.
	 * Checks the plugin slug and only adds the script on the logs page for this plugin.
	 *
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_scripts(): void {

		$slug        = $this->settings->get_plugin_slug();
		$page_suffix = "_{$slug}-logs";

		$current_page = get_current_screen();

		/**
		 * `$current_page->id` will begin with `admin_page` or `$parent_slug` determined in `add_page()`.
		 *
		 * @see Logs_Page::add_page()
		 */
		if ( is_null( $current_page ) || substr( $current_page->id, -strlen( $page_suffix ) ) !== $page_suffix ) {
			return;
		}

		// This is the bh-wp-logger JavaScript version, not the plugin version.
		$version = '1.0.0';

		$js_path = realpath( __DIR__ . '/../../' ) . '/assets/bh-wp-logger-admin.js';
		$js_url  = plugin_dir_url( $js_path ) . 'bh-wp-logger-admin.js';

		wp_enqueue_script( 'bh-wp-logger-admin-logs-page-' . $slug, $js_url, array( 'jquery' ), $version, true );

		$renderjson_js_path = realpath( __DIR__ . '/../../' ) . '/assets/vendor/renderjson/renderjson.js';
		$renderjson_js_url  = plugin_dir_url( $renderjson_js_path ) . 'renderjson.js';

		wp_enqueue_script( 'renderjson', $renderjson_js_url, array(), '1.4', true );

		$colresizable_js_path = realpath( __DIR__ . '/../../' ) . '/assets/vendor/colresizable/colResizable-1.6.min.js';
		$colresizable_js_url  = plugin_dir_url( $colresizable_js_path ) . 'colResizable-1.6.min.js';

		wp_enqueue_script( 'colresizable', $colresizable_js_url, array(), '1.6', true );
	}

	/**
	 * Register the stylesheets for the logs page.
	 * (colours the rows with the severity of the log message!).
	 *
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_styles(): void {

		$slug        = $this->settings->get_plugin_slug();
		$page_suffix = "_{$slug}-logs";

		$current_page = get_current_screen();

		/**
		 * `$current_page->id` will begin with `admin_page` or `$parent_slug` determined in `add_page()`.
		 *
		 * @see Logs_Page::add_page()
		 */
		if ( is_null( $current_page ) || substr( $current_page->id, -strlen( $page_suffix ) ) !== $page_suffix ) {
			return;
		}

		$handle = "{$this->settings->get_plugin_slug()}-logs";

		$version = '1.0.0';

		$css_path = realpath( __DIR__ . '/../../' ) . '/assets/bh-wp-logger.css';
		$css_url  = plugin_dir_url( $css_path ) . 'bh-wp-logger.css';

		wp_enqueue_style( $handle, $css_url, array(), $version, 'all' );
	}

}
