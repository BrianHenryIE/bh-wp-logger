<?php
/**
 * The UI around the logs table.
 */

namespace BrianHenryIE\WP_Logger\admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;


class Logs_Page {

	/** @var LoggerInterface */
	protected $logger;

	/** @var Logger_Settings_Interface  */
	protected $settings;

	/** @var API_Interface  */
	protected $api;

	/**
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param LoggerInterface           $logger
	 */
	public function __construct( $api, $settings, $logger = null ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * @hooked admin_init
	 *
	 * Add a WordPress admin UI page, but without any menu linking to it.
	 */
	public function add_page() {

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
	 * Registered in @see add_page()
	 */
	public function display_page() {

		$logs_table = new Logs_Table( $this->api, $this->settings, $this->logger );
		$logs_table->prepare_items();

		update_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', time() );

		$logs_table->display();
	}

	/**
	 * @hooked admin_footer
	 */
	public function print_css() {

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
