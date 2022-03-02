<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Settings_Interface;
use Codeception\Stub\Expected;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger
 */
class BH_WP_PSR_Logger_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When the log level is regular (notice, info), and the level of the error being logged is 'error', the backtrace
	 * should always be recorded.
	 */
	public function test_error_log_always_includes_backtrace(): void {

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new BH_WP_PSR_Logger( $setttings );

		$logger = new class() extends ColorLogger {
			public $context = array();
			public function log( $level, $message, array $context = array() ) {
				$this->context = $context;
				parent::log( $level, $message, $context );
			}
		};

		$sut->setLogger( $logger );

		$sut->error( 'error log message' );

		$this->assertArrayHasKey( 'debug_backtrace', $logger->context );

	}



}
