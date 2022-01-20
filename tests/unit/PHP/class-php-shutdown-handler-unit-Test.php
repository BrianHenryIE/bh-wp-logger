<?php

namespace BrianHenryIE\WP_Logger\PHP;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use function Patchwork\redefine;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\PHP\PHP_Shutdown_Handler
 */
class PHP_Shutdown_Handler_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_constructor(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new PHP_Shutdown_Handler( $api, $settings, $logger );

		$this->assertInstanceOf( PHP_Shutdown_Handler::class, $sut );
	}

	/**
	 * @covers ::init
	 */
	public function test_register_handler(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new PHP_Shutdown_Handler( $api, $settings, $logger );

		$e = null;
		try {
			$sut->init();
		} catch ( \Exception $exception ) {
			$this->fail();
		}

		$this->assertNull( $e );
	}

	/**
	 * @covers ::handle
	 */
	public function test_error(): void {

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_file_from_plugin' => true,
			)
		);
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new PHP_Shutdown_Handler( $api, $settings, $logger );

		\Patchwork\redefine(
			'error_get_last',
			function() {
				return array(
					'file'    => __FILE__,
					'message' => 'error message',
				);
			}
		);

		$sut->handle();

		$this->assertTrue( $logger->hasErrorThatContains( 'error message' ) );
	}

	/**
	 * @covers ::handle
	 */
	public function test_no_error(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new PHP_Shutdown_Handler( $api, $settings, $logger );

		\Patchwork\redefine(
			'error_get_last',
			function() {
				return null;
			}
		);

		$sut->handle();

		$this->assertFalse( $logger->hasErrorThatContains( 'error message' ) );
	}


	/**
	 * @covers ::handle
	 */
	public function test_error_wrong_file(): void {

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_file_from_plugin' => false,
			)
		);
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$sut = new PHP_Shutdown_Handler( $api, $settings, $logger );

		\Patchwork\redefine(
			'error_get_last',
			function() {
				return array(
					'file'    => __FILE__,
					'message' => 'error message',
				);
			}
		);

		$sut->handle();

		$this->assertFalse( $logger->hasErrorThatContains( 'error message' ) );
	}

}
