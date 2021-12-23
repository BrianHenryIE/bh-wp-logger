<?php
/**
 * The UI around the logs table.
 *
 * TODO: Add "send to plugin developer" button.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class Logs_Page {

	use LoggerAwareTrait;

	/**
	 * The logger settings. i.e. what is the plugin slug this logger is for?
	 *
	 * @uses \BrianHenryIE\WP_Logger\API\Logger_Settings_Interface::get_plugin_slug()
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
	 * @param ?LoggerInterface          $logger The logger itself, for logging.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {

		$this->setLogger( $logger ?? new NullLogger() );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Add a WordPress admin UI page, but without any menu linking to it.
	 *
	 * @hooked admin_init
	 */
	public function add_page(): void {

		$logs_slug = "{$this->settings->get_plugin_slug()}-logs";

		add_submenu_page(
			'',
			__( 'Logs', 'bh-wp-logger' ),
			'logs',
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
	 * TODO: Allow filtering the output.
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

		$logs_table = new Logs_Table( $this->api, $this->settings, $this->logger );

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

		// Maybe should use set file?
		$logs_table->set_date( $chosen_date );
		$logs_table->prepare_items();

		update_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', time() );

		$logs_table->display();

		echo '</div>';
	}

	/**
	 * Register the stylesheets for the logs page.
	 * (colours the rows with the severity of the log message!).
	 *
	 * @hooked admin_enqueue_scripts
	 */
	public function enqueue_styles(): void {

		$handle = "{$this->settings->get_plugin_slug()}-logs";

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['page'] ) || $handle !== $_GET['page'] ) {
			return;
		}

		$css_file = plugin_dir_url( __FILE__ ) . '/css/bh-wp-logger.css';
		$version  = '1.0.0';

		wp_enqueue_style( $handle, $css_file, array(), $version, 'all' );
	}

}
