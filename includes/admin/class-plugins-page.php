<?php
/**
 * Changes on WordPress plugins.php.
 *
 * Adds a Logs link to the plugin's entry.
 * Formats that link <strong> if there are unviewed logs.
 *
 * e.g. /wp-admin/admin.php?page=bh-wp-logger-development-plugin-logs.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
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
	protected Logger_Settings_Interface $settings;

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
	 * Adds 'Logs' link to under the plugin name on plugins.php.
	 * Attempts to place it immediately before the deactivate link.
	 *
	 * @hooked plugin_action_links_{plugin basename}
	 * @see \WP_Plugins_List_Table::display_rows()
	 *
	 * @param array<int|string, string>  $action_links The existing plugin links (usually "Deactivate").
	 * @param string                     $_plugin_basename The plugin's directory/filename.php.
	 * @param array<string, string|bool> $_plugin_data Associative array including PluginURI, slug, Author, Version. See `get_plugin_data()`.
	 * @param string                     $_context     The plugin context. By default this can include 'all', 'active', 'inactive',
	 *                                                'recently_activated', 'upgrade', 'mustuse', 'dropins', and 'search'.
	 *
	 * @return array<int|string, string> The links to display below the plugin name on plugins.php.
	 */
	public function add_logs_action_link( array $action_links, string $_plugin_basename, $_plugin_data, $_context ): array {

		// Presumably the deactivate link.
		// When a plugin is "required" it does not have a deactivate link.
		if ( count( $action_links ) > 0 ) {
			$deactivate_link = array_pop( $action_links );
		}

		$logs_link = $this->api->get_log_url();

		$last_log_time       = $this->api->get_last_log_time();
		$last_logs_view_time = $this->api->get_last_logs_view_time();

		if ( ! is_null( $last_log_time ) && ! is_null( $last_logs_view_time )
			&& $last_log_time > $last_logs_view_time ) {
			$action_links[] = '<b><a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a></b>';
		} else {
			$action_links[] = '<a href="' . $logs_link . '">' . __( 'Logs', 'bh-wp-logger' ) . '</a>';
		}

		if ( isset( $deactivate_link ) ) {
			$action_links[] = $deactivate_link;
		}

		return $action_links;
	}
}
