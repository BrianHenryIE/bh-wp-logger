<?php
/**
 * Functions to augment WC_Log_Handler.
 *
 * @see WC_Log_Handler
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WooCommerce;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Log_Levels;

/**
 * Filters `woocommerce_format_log_entry` to print the context.
 */
class Log_Handler {

	use LoggerAwareTrait;

	/**
	 * The logger settings.
	 *
	 * @uses \BrianHenryIE\WP_Logger\API\Logger_Settings_Interface::get_plugin_slug()
	 * @uses \BrianHenryIE\WP_Logger\API\Logger_Settings_Interface::get_log_level()
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * Functions for managing logs and adding context.
	 *
	 * @uses \BrianHenryIE\WP_Logger\API\API_Interface::get_common_context()
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Constructor
	 *
	 * @param API_Interface             $api Functions for additional context.
	 * @param Logger_Settings_Interface $settings The log level and plugin-slug this logger is for.
	 * @param LoggerInterface           $logger A PSR logger (not used).
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $logger ) {

		$this->setLogger( $logger );
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * The standard WooCommerce logger does not record the $context.
	 *
	 * Add context when min log level is Debug, and for Errors and worse.
	 *
	 * @hooked woocommerce_format_log_entry
	 * @see \WC_Log_Handler::format_entry()
	 *
	 * @param string                                                            $entry The log entry already built by WooCommerce.
	 * @param array{timestamp:int, level:string, message:string, context:array} $log_data_array The log level, message, context and timestamp in an array.
	 *
	 * @return string
	 */
	public function add_context_to_logs( string $entry, array $log_data_array ): string {

		// Only act on logs for this plugin.
		if ( ! isset( $log_data_array['context']['source'] ) || $this->settings->get_plugin_slug() !== $log_data_array['context']['source'] ) {
			return $entry;
		}

		$log_level = $this->settings->get_log_level();

		// Always record the context when it's an error, or when loglevel is DEBUG.
		if ( WC_Log_Levels::get_level_severity( $log_data_array['level'] ) < WC_Log_Levels::get_level_severity( WC_Log_Levels::ERROR )
			&& WC_Log_Levels::DEBUG !== $log_level ) {
			return $entry;
		}

		$context = $log_data_array['context'];

		// The plugin slug.
		unset( $context['source'] );

		return $entry . "\n" . wp_json_encode( $context );
	}

}
