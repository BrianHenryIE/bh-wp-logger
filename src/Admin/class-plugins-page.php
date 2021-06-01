<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
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
	 * Attempts to place it immediately before the deactivate link.
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

		// Presumably the deactivate link.
		$deactivate_link = array_pop( $links );

		$logs_link = $this->api->get_log_url();
		if ( ! is_null( $logs_link ) ) {

			$last_log_time       = get_option( $this->settings->get_plugin_slug() . '-last-log-time', 0 );
			$last_logs_view_time = get_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', 0 );

			if ( 0 !== $last_log_time
				&& $last_log_time > $last_logs_view_time ) {
				$links[] = '<b><a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a></b>';
			} else {
				$links[] = '<a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a>';
			}
		}

		$links[] = $deactivate_link;

		return $links;
	}

}
