<?php
/**
 * A PSR wrapper for WC_Logger.
 *
 * Attempts to run wc_get_logger(), if unavailable, tries again on `woocommerce_loaded` hook.
 * If a message is logged before then, it is queued for later.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WooCommerce;

use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use WC_Logger_Interface;

class WC_PSR_Logger implements LoggerInterface {
	use LoggerTrait;

	protected string $plugin_slug;

	protected ?WC_Logger_Interface $wc_logger = null;

	public function __construct( string $plugin_slug ) {

		$this->plugin_slug = $plugin_slug;

		if ( ! did_action( 'woocommerce_loaded' ) ) {

			$instantiate_wc_logger = function (): void {
				$this->wc_logger = wc_get_logger();
			};
			add_action( 'woocommerce_loaded', $instantiate_wc_logger, 1 );
		} else {
			$this->wc_logger = wc_get_logger();
		}
	}

	/**
	 * Logs with an arbitrary level.
	 *
	 * If woocommerce_loaded hook has not run, a closure is added and executed later to record the log.
	 *
	 * @see WC_Log_Levels
	 * @see \PSR\Log\LogLevel
	 *
	 * @param mixed        $level The severity.
	 * @param string       $message The human-readable message.
	 * @param array<mixed> $context An array of data to be logged alongside the message.
	 *
	 * @return void
	 *
	 * @throws \Psr\Log\InvalidArgumentException
	 */
	public function log( $level, $message, array $context = array() ) {

		// If this is true, WooCommerce is known to be active, but we are before woocommerce_loaded.
		if ( ! isset( $this->wc_logger ) ) {

			// Re-run this same function after the logger has been initialized.
			$re_run_log = function() use ( $level, $message, $context ) {
				$this->log( $level, $message, $context );
			};
			add_action( 'woocommerce_loaded', $re_run_log, 2 );

			return;
		}

		$context['source'] = $this->plugin_slug;

		$this->wc_logger->$level( $message, $context );
	}
}
