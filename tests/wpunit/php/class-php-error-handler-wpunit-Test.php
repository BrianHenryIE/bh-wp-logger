<?php

namespace BrianHenryIE\WP_Logger\PHP;

use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WPUnit_Testcase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler
 */
class PHP_Error_Handler_WPUnit_Test extends WPUnit_Testcase {

	/**
	 * PHPUnit's error handler was throwing an exception when it wasn't wanted.
	 */
	protected function setUp(): void {
		parent::setUp();

		do {
			$previous_handler = set_error_handler( null );
		} while ( null !== $previous_handler );
	}


	protected function tearDown(): void {
		parent::tearDown();

		do {
			$previous_handler = set_error_handler( null );
		} while ( null !== $previous_handler );
	}

	/**
	 * Verify construction does _not_ change PHP's error handler.
	 *
	 * The actual error handler is set in the PHP_Error_Handler::init() function.
	 *
	 * @covers ::init
	 */
	public function test_construct(): void {

		$api = $this->makeEmpty( API::class );

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new NullLogger();

		$my_handler = function ( int $errno, string $errstr, ?string $errfile = null, ?bool $errline = null ): bool {
			return false;
		};

		set_error_handler( $my_handler );

		new PHP_Error_Handler( $api, $settings, $logger );

		$my_handler_2 = function ( int $errno, string $errstr, ?string $errfile = null, ?bool $errline = null ): bool {
			return false;
		};

		/** @var array{0:object, 1:string} $previous */
		$previous = set_error_handler( $my_handler_2 );

		$this->assertEquals( $my_handler, $previous );
	}

	/**
	 * Verify init does change PHP's error handler
	 *
	 * @covers ::init
	 */
	public function test_init(): void {

		$api = $this->makeEmpty( API::class );

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new NullLogger();

		$my_handler = function ( int $errno, string $errstr, ?string $errfile = null, ?int $errline = null ): bool {
			return false;
		};

		set_error_handler( $my_handler );

		$sut = new PHP_Error_Handler( $api, $settings, $logger );

		$sut->init();

		$my_handler_2 = function ( int $errno, string $errstr, ?string $errfile = null, ?int $errline = null ) {};

		/** @var array{0:object, 1:string} $previous */
		$previous = set_error_handler( $my_handler_2 );

		$this->assertNotEquals( $my_handler, $previous );

		$callable_instance    = $previous[0];
		$callable_method_name = $previous[1];

		$this->assertInstanceOf( PHP_Error_Handler::class, $callable_instance );
		$this->assertEquals( 'plugin_error_handler', $callable_method_name );
	}

	/**
	 * Check the previous error handler is stored as an instance variable.
	 *
	 * @covers ::init
	 */
	public function test_stores_previous_handler(): void {

		$api = $this->makeEmpty( API::class );

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new NullLogger();

		$my_handler = function ( int $errno, string $errstr, string $errfile, int $errline ): bool {
			return false;
		};

		set_error_handler( $my_handler );

		$sut = new PHP_Error_Handler( $api, $settings, $logger );
		$sut->init();

		$reflector          = new \ReflectionClass( $sut );
		$reflector_property = $reflector->getProperty( 'previous_error_handler' );
		PHP_VERSION_ID < 80100 && $reflector_property->setAccessible( true );

		$this->assertEquals( $my_handler, $reflector_property->getValue( $sut ) );
	}

	/**
	 * When the error was caused by the plugin, it should be logged with the plugin's logger.
	 *
	 * Tests the filename is a file from this plugin.
	 *
	 * @covers ::plugin_error_handler
	 */
	public function test_a_relevant_error(): void {

		$api = $this->makeEmpty( API::class );

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug'             => 'my-plugin',
				'get_plugin_slug_underscored' => 'my_plugin',
				'get_plugin_basename'         => 'my-plugin/my-plugin.php',
			)
		);

		$logger = $this->makeEmpty(
			LoggerInterface::class,
			array(
				'warning' => \Codeception\Stub\Expected::once(),
			)
		);

		$sut = new PHP_Error_Handler( $api, $settings, $logger );
		$sut->init();

		$result = $sut->plugin_error_handler(
			E_WARNING,
			'A warning message',
			WP_PLUGIN_DIR . '/my-plugin/a-plugin-file.php',
			1
		);

		// True means it has been handled.
		$this->assertTrue( $result );
	}

	/**
	 * When an error is caused by another plugin, it should be passed to the next error handler... or returned true|false.
	 *
	 * @covers ::plugin_error_handler
	 */
	public function test_irrelevant_error(): void {

		$api = $this->makeEmpty( API::class );

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug'             => 'my-plugin',
				'get_plugin_slug_underscored' => 'my_plugin',
				'get_plugin_basename'         => 'my-plugin/my-plugin.php',
			)
		);

		$logger = $this->makeEmpty(
			LoggerInterface::class,
			array(
				'warning' => \Codeception\Stub\Expected::never(),
			)
		);

		$sut = new PHP_Error_Handler( $api, $settings, $logger );
		$sut->init();

		$result = $sut->plugin_error_handler(
			E_WARNING,
			'A warning message',
			WP_PLUGIN_DIR . '/another-plugin/a-plugin-file.php',
			1
		);

		// True means it has been handled.
		$this->assertFalse( $result );
	}
}
