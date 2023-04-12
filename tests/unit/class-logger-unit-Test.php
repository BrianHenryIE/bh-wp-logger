<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\WP_Includes\Plugin_Logger_Actions;
use Psr\Log\LoggerInterface;

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
			array( BH_WP_PSR_Logger::class, '__construct' ),
			function() {}
		);

		\Patchwork\redefine(
			array( Plugin_Logger_Actions::class, '__construct' ),
			function() {}
		);

		\WP_Mock::passthruFunction( 'wp_normalize_path' );
		\WP_Mock::userFunction(
			'get_plugins',
			array(
				'return' => array( 'test-logger/test-logger.php' => array() ),
			)
		);
		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'return' => 'test-logger/test-logger.php',
			)
		);
		\WP_Mock::userFunction(
			'get_option',
			array(
				'return_arg' => 1,
			)
		);

		$logger = Logger::instance();

		$this->assertInstanceOf( BH_WP_PSR_Logger::class, $logger );
		$this->assertInstanceOf( LoggerInterface::class, $logger );

	}

}
