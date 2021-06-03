<?php


namespace BH_WP_Logger\API;

use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\API\PHP_Error_Handler;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class PHP_Error_Handler_Test extends \Codeception\TestCase\WPTestCase {

	//
	public function test_chain() {

		$my_handler_1 = function( $errno, $errstr, $errfile = null, $errline = null ) {
			echo 'here 1';

			// Error has been handled
			return true;
		};

		set_error_handler( $my_handler_1 );

		$my_handler_2 = function( $errno, $errstr, $errfile = null, $errline = null ) {
			echo 'here 2';
			return false;
		};

		set_error_handler( $my_handler_2 );

		trigger_error('asd');

	}

	/**
	 * Verify construction does not change PHP's error handler.
	 */
	public function test_construct() {

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger = new NullLogger();

		$my_handler = function( $errno, $errstr, $errfile = null, $errline = null ) {};

		set_error_handler( $my_handler );

		$php_error_handler = new PHP_Error_Handler( $settings, $logger );
        $php_error_handler->init();

		$my_handler_2 = function( $errno, $errstr, $errfile = null, $errline = null ) {};

		$previous = set_error_handler( $my_handler_2 );

		$this->assertEquals( $my_handler, $previous);

	}

	/**
	 * Verify init does change PHP's error handler
	 */
	public function test_init() {

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger = new NullLogger();

		$my_handler = function( $errno, $errstr, $errfile = null, $errline = null ) {};

		set_error_handler( $my_handler );

		$sut = new PHP_Error_Handler( $settings, $logger );

		$sut->init();

		$my_handler_2 = function( $errno, $errstr, $errfile = null, $errline = null ) {};

		$previous = set_error_handler( $my_handler_2 );

		$this->assertNotEquals( $my_handler, $previous);

	}

	/**
	 * Check the previous error handler is stored as a iinstance variable.
	 */
	public function test_stores_previous_handler() {

		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger = new NullLogger();

		$my_handler = function( $errno, $errstr, $errfile = null, $errline = null ) {};

		set_error_handler( $my_handler );

		$sut = new PHP_Error_Handler( $settings, $logger );

		$sut->init();

		$reflector = new \ReflectionClass($sut);
		$reflector_property = $reflector->getProperty('previous_error_handler');
		$reflector_property->setAccessible(true);

		$this->assertEquals( $my_handler, $reflector_property->getValue($sut) );
	}

	/**
	 * When the error was caused by the plugin, it should be logged with the plugin's logger.
	 *
	 * Tests the filename is a file from this plugin.
	 */
	public function test_a_relevant_error() {

		$settings = $this->makeEmpty( Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'my-plugin',
				'get_plugin_basename' =>  'my-plugin/my-plugin.php'
			)
		);

		$logger = $this->makeEmpty(LoggerInterface::class,
			array(
				'warning' => \Codeception\Stub\Expected::once()
			)
		);

		$sut = new PHP_Error_Handler( $settings, $logger );
		$sut->init();

		$result = $sut->plugin_error_handler(
			E_WARNING,
			'A warning message',
			WP_PLUGIN_DIR . '/my-plugin/a-plugin-file.php',
			1
		);

		// True means it has been handled.
		$this->assertTrue($result);

	}

	/**
	 * When an error is caused by another plugin, it should be passed to the next error handler... or returned true|false.
	 */
	public function test_irrelevant_error() {

		$settings = $this->makeEmpty( Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'my-plugin',
				'get_plugin_basename' =>  'my-plugin/my-plugin.php'
			)
		);

		$logger = $this->makeEmpty(LoggerInterface::class,
			array(
				'warning' => \Codeception\Stub\Expected::never()
			)
		);

		$sut = new PHP_Error_Handler( $settings, $logger );
		$sut->init();

		$result = $sut->plugin_error_handler(
			E_WARNING,
			'A warning message',
			WP_PLUGIN_DIR . '/another-plugin/a-plugin-file.php',
			1
		);

		// True means it has been handled.
		$this->assertFalse($result);

	}

	// PHPUnit's error handler was throwing an exception when it wasn't wanted.
	protected function setUp(): void {
		parent::setUp();

		do {
			$previous_handler = set_error_handler(null);
		} while( $previous_handler !== null );

	}


	protected function tearDown(): void {
		parent::tearDown();

		do {
			$previous_handler = set_error_handler(null);
		} while( $previous_handler !== null );

	}

}