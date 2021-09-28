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
			null,
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
		echo $this->settings->get_plugin_name();
		echo '</h1>';

		$logs_table = new Logs_Table( $this->api, $this->settings, $this->logger );

		// Set date here?

		// Show a list of date to switch between dates.
		echo '<label for="log_date">Log date:</label>';
		echo '<select name="log_date" id="log_date">';
		$log_files = $this->api->get_log_files();

		$chosen_date = isset( $_GET['log_date'] ) ? $_GET['log_date'] : array_key_last( $log_files );

		// TODO: Allow filtering here to add external log files, e.g. from Authorize.net SDK.
		foreach ( $log_files as $date => $path ) {
			$date_formatted = $date;
			echo "<option value=\"{$date}\"";
			if ( $date === $chosen_date ) {
				echo ' selected';
			}
			echo ">{$date_formatted}</option>";
		}
		echo '</select>';

		echo "<button name=\"deleteButton\" id=\"deleteButton\"  data-date=\"{$chosen_date}\" class=\"button logs-page button-primary\">Delete {$chosen_date} logs</button>";
		echo '<button name="deleteAllButton" id="deleteAllButton" class="button logs-page button-secondary">Delete all logs</button>';

		// Maybe should use set file?
		$logs_table->set_date( $chosen_date );
		$logs_table->prepare_items();

		update_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', time() );

		$logs_table->display();

		echo '</div>';
	}

	/**
	 * Output the CSS for the table (colours the rows with the severity of the log message!).
	 *
	 * TODO: Is this the best place and best way to do this?
	 *
	 * @hooked admin_footer
	 */
	public function print_css(): void {

		if ( ! isset( $_GET['page'] ) || "{$this->settings->get_plugin_slug()}-logs" !== $_GET['page'] ) {
			return;
		}

		$css_file = __DIR__ . '/css/bh-wp-logger.css';

		if ( file_exists( $css_file ) ) {

			echo "\n<style>\n";

			echo file_get_contents( $css_file );

			echo "\n</style>\n";
		}
	}
}
