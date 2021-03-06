<?php
/**
 * Functions to augment WC_Log_Handler.
 *
 * @see WC_Log_Handler
 */

namespace BrianHenryIE\WP_Logger\WooCommerce;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WC_Log_Levels;

class Log_Handler {

	use LoggerAwareTrait;

	/** @var Logger_Settings_Interface  */
	protected $settings;

	/** @var API_Interface  */
	protected $api;

	/**
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param LoggerInterface           $logger
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $logger = null ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * The standard WooCommerce logger does not record the $context.
	 *
	 * Add context when min log level is Debug, and for Errors and worse.
	 *
	 * @hooked woocommerce_format_log_entry
	 *
	 * @see \WC_Log_Handler::format_entry()
	 *
	 * @param string $entry The log entry already built by WooCommerce.
	 * @param array  $log_data_array {
	 *  Information used to create the log entry.
	 *
	 *  @type int    $timestamp Log timestamp.
	 *  @type string $level     emergency|alert|critical|error|warning|notice|info|debug.
	 *  @type string $message   Log message.
	 *  @type array  $context   Extraneous information that does not fit well in a string.
	 * }
	 *
	 * @return string
	 */
	public function add_context_to_logs( string $entry, array $log_data_array ): string {

		// Only act on logs for this plugin.
		if ( ! isset( $log_data_array['context']['source'] ) || $log_data_array['context']['source'] !== $this->settings->get_plugin_slug() ) {
			return $entry;
		}

		// Always record the context when it's an error, or when loglevel is DEBUG.
		if ( ! ( WC_Log_Levels::get_level_severity( $log_data_array['level'] ) >= WC_Log_Levels::ERROR
			|| WC_Log_Levels::DEBUG === $this->settings->get_log_level() ) ) {
			return $entry;
		}

		$context = array_merge( $this->api->get_common_context(), $log_data_array['context'] );

		unset( $context['source'] );

		// TODO: regex to replace email addresses with b**********e@gmail.com, credit card numbers etc.
		return $entry . "\n" . wp_json_encode( $context );
	}

}
