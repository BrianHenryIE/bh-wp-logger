<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API\Logger_Settings;
use BrianHenryIE\WP_Logger\WP_Includes\Plugin_Logger_Actions;
use BrianHenryIE\WP_Logger\WP_Includes\Plugins;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Logger
 */
class Logger_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * @covers ::instance
	 */
	public function test_instantiate(): void {

		\Patchwork\redefine(
			array( Logger_Settings::class, '__construct' ),
			function() {}
		);
		\Patchwork\redefine(
			array( Logger_Settings::class, 'get_log_level' ),
			function() {
				return 'none';
			}
		);

		\Patchwork\redefine(
			array( Plugins::class, '__construct' ),
			function() {}
		);

		\Patchwork\redefine(
			array( BH_WP_PSR_Logger::class, '__construct' ),
			function() {}
		);

		\Patchwork\redefine(
			array( Plugin_Logger_Actions::class, '__construct' ),
			function() {}
		);

		$logger = Logger::instance();

		$this->assertInstanceOf( BH_WP_PSR_Logger::class, $logger );
		$this->assertInstanceOf( LoggerInterface::class, $logger );

	}

}
