<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger
 */
class BH_WP_PSR_Logger_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * When an exception is passed in the context, it normally just gets logged as `{}`, so let's instead log the
	 * exception type and message, and use reflection to get its properties.
	 *
	 * @covers ::log
	 */
	public function test_exception(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new BH_WP_PSR_Logger( $settings, $logger );

		$exception = new \Exception( 'Exception message', 123 );

		\WP_Mock::userFunction(
			'update_option'
		);

		\WP_Mock::userFunction(
			'delete_transient'
		);

		$sut->error( 'Error', array( 'exception' => $exception ) );

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$logged_exception = $logger->recordsByLevel['error'][0]['context']['exception'];

		$this->assertArrayHasKey( 'class', $logged_exception );

		$this->assertArrayHasKey( 'message', $logged_exception );
		$this->assertEquals( 'Exception message', $logged_exception['message'] );

		$this->assertArrayHasKey( 'properties', $logged_exception );
		$this->assertEquals( 123, $logged_exception['properties']['code'] );
	}
}
