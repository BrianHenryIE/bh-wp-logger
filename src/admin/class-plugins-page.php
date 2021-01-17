<?php

namespace BrianHenryIE\WP_Logger\admin;

use BrianHenryIE\WP_Logger\api\API_Interface;
use BrianHenryIE\WP_Logger\api\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;

class Plugins_Page {

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
	 * Adds 'Logs' link to the most recent logs in the WooCommerce logs page.
	 *
	 * Hooked early presuming other changes will be prepended, i.e.
	 * Other Links | Logs | Deactivate
	 *
	 * @hooked plugin_action_links_{plugin basename}
	 * @see \WP_Plugins_List_Table::display_rows()
	 *
	 * @param string[] $links The links that will be shown below the plugin name on plugins.php.
	 *
	 * @return string[]
	 */
	public function display_plugin_action_links( $links ) {

		$plugin_links = array();
		$logs_link    = $this->api->get_log_url();
		if ( ! is_null( $logs_link ) ) {
			$plugin_links[] = '<a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a>';
		}

		return array_merge( $plugin_links, $links );
	}

}
