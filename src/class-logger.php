<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\API\Logger_Settings;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Includes\BH_WP_Logger;
use Psr\Log\LoggerInterface;

class Logger extends BH_WP_Logger implements LoggerInterface {

	/** @var Logger */
	protected static $instance;

	protected function __construct( $api, $settings ) {
		parent::__construct( $api, $settings );
	}

	/**
	 * @param ?Logger_Settings_Interface $settings
	 *
	 * @return Logger
	 */
	public static function instance( $settings = null ): LoggerInterface {

		if ( is_null( self::$instance ) ) {

			// Zero-config.
			$settings = $settings ?? new Logger_Settings();

			$api = new API( $settings );

			self::$instance = new self( $api, $settings );
		}

		return self::$instance;
	}
}
