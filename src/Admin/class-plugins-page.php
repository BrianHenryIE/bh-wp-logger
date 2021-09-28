<?php
/**
 * Changes on WordPress plugins.php.
 *
 * Adds a Logs link to the plugin's entry.
 *
 * @package BrianHenryIE\WP_Logger\Admin
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Class Plugins_Page
 */
class Plugins_Page {

	use LoggerAwareTrait;

	/**
	 * Needed for the plugin slug.
	 *
	 * @var Logger_Settings_Interface
	 */
	protected $settings;

	/**
	 * Needed for the log file path.
	 *
	 * @var API_Interface
	 */
	protected $api;

	/**
	 * Plugins_Page constructor.
	 *
	 * @param API_Interface             $api The logger's main functions.
	 * @param Logger_Settings_Interface $settings The logger settings.
	 * @param ?LoggerInterface          $logger The logger itself.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, ?LoggerInterface $logger = null ) {

		$this->setLogger( $logger ?? new NullLogger() );
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
	 * @hooked plugin_action_links_{$basename} via closure. The closure needed to also pass the basename.
	 *
	 * @param array<int|string, string>  $action_links The existing plugin links (usually "Deactivate").
	 * @param string                     $_plugin_basename The plugin's directory/filename.php.
	 * @param array<string, string|bool> $_plugin_data Associative array including PluginURI, slug, Author, Version. See `get_plugin_data()`.
	 * @param string                     $_context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                                                'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 *
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function display_plugin_action_links( array $action_links, string $_plugin_basename, $_plugin_data, $_context ): array {

		// Presumably the deactivate link.
		// When a plugin is "required" it does not have a deactivtae link.
		if( count( $action_links) > 0 ) {
			$deactivate_link = array_pop( $action_links );
		}

		$logs_link = $this->api->get_log_url();

		$last_log_time       = get_option( $this->settings->get_plugin_slug() . '-last-log-time', 0 );
		$last_logs_view_time = get_option( $this->settings->get_plugin_slug() . '-last-logs-view-time', 0 );

		if ( 0 !== $last_log_time
			&& $last_log_time > $last_logs_view_time ) {
			$action_links[] = '<b><a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a></b>';
		} else {
			$action_links[] = '<a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a>';
		}

		if( isset( $deactivate_link ) ) {
			$action_links[] = $deactivate_link;
		}

		return $action_links;
	}

}
