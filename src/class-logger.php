<?php
/**
 * Instantiate the logger for your plugin.
 *
 * `$logger = \BrianHenryIE\WP_Logger\Logger::instance()`
 *
 * @see \BrianHenryIE\WP_Logger\API\Logger_Settings_Interface
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\API\Logger_Settings;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\API\Plugin_Helper;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\Includes\Plugin_Logger_Actions;
use Psr\Log\LoggerInterface;

/**
 * Wraps parent class in a singleton so it only needs to be configured once.
 */
class Logger extends BH_WP_PSR_Logger implements LoggerInterface {

	/**
	 * Singleton.
	 *
	 * @var Logger
	 */
	protected static Logger $instance;

	/**
	 * Initialize the logger and store the instance in the singleton variable.
	 * Settings are used when provided, inferred when null.
	 * Ideally settings should be provided the first time the logger is instantiated, then they do not need
	 * to be provided when accessing the singleton later on.
	 *
	 * @see Logger_Settings
	 * @see Plugin_Helper
	 *
	 * @param ?Logger_Settings_Interface $settings The loglevel, plugin name, slug, and basename.
	 *
	 * @return Logger
	 */
	public static function instance( ?Logger_Settings_Interface $settings = null ): LoggerInterface {

		if ( ! isset( self::$instance ) ) {

			// Zero-config.
			$settings = $settings ?? new Logger_Settings();

			$api = new API( $settings );

			self::$instance = new self( $api, $settings );

			// Add the hooks.
			new Plugin_Logger_Actions( $api, $settings, self::$instance );
		}

		return self::$instance;
	}

}
