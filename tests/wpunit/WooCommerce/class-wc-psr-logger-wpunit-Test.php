<?php

namespace BrianHenryIE\WP_Logger\WooCommerce;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\TestCase\WPTestCase;
use Psr\Log\LoggerInterface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger
 */
class WC_PSR_Logger_WPUnit_Test extends WPTestCase {

	/**
	 * Test construction doesn't fail and that it is a logger.
	 *
	 * @covers ::__construct
	 */
	public function test_instantiate_is_logger(): void {

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new WC_PSR_Logger( $setttings );

		$this->assertInstanceOf( LoggerInterface::class, $sut );
	}

	/**
	 * Checks there is a closure bound to an instance of WC_PSR_Logger at woocommerce_loaded, 1.
	 *
	 * @covers ::__construct
	 */
	public function test_instantiate_before_woocommerce_loaded():void {

		// The tests run after WordPress and plugins have loaded.
		global $wp_actions;
		unset( $wp_actions['woocommerce_loaded'] );

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new WC_PSR_Logger( $setttings );

		$action_name  = 'woocommerce_loaded';
		$priority     = 1;
		$hooked       = (array) $GLOBALS['wp_filter'][ $action_name ][ $priority ];
		$first_action = array_pop( $hooked );

		$function = $first_action['function'];

		try {
			$reflection = new \ReflectionFunction( $function );
		} catch ( \ReflectionException $e ) {
			$this->fail();
		}

		$bound_to = $reflection->getClosureThis();

		$this->assertInstanceOf( WC_PSR_Logger::class, $bound_to );
		$this->assertEquals( $sut, $bound_to );
	}

	/**
	 * Before the wc_logger is present, check nothing bad happens.
	 *
	 * @covers ::log
	 */
	public function test_log_before_loaded(): void {
		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new WC_PSR_Logger( $setttings );

		$sut->log( 'error', 'Log an error' );
	}

	/**
	 * @covers ::log
	 */
	public function test_log_before_wc_loaded(): void {

		// The tests run after WordPress and plugins have loaded.
		global $wp_actions;
		unset( $wp_actions['woocommerce_loaded'] );

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new WC_PSR_Logger( $setttings );

		$sut->log( 'error', 'Log an error' );

		$action_name  = 'woocommerce_loaded';
		$priority     = 2;
		$hooked       = (array) $GLOBALS['wp_filter'][ $action_name ][ $priority ];
		$first_action = array_pop( $hooked );

		$function = $first_action['function'];

		try {
			$reflection = new \ReflectionFunction( $function );
		} catch ( \ReflectionException $e ) {
			$this->fail();
		}

		$bound_to = $reflection->getClosureThis();

		$this->assertInstanceOf( WC_PSR_Logger::class, $bound_to );

	}

	/**
	 * While we wait for WooCommerce to be loaded, the logger should be null.
	 *
	 * But the logs should be enqueued.
	 *
	 * Test the logs made before plugins_loaded are recorded after plugins_loaded.
	 */
	public function test_woocommerce_logger_log_on_plugins_loaded(): void {

		// The tests run after WordPress and plugins have loaded.
		global $wp_actions;
		unset( $wp_actions['woocommerce_loaded'] );

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new WC_PSR_Logger( $setttings );

		assert( is_plugin_active( 'woocommerce/woocommerce.php' ) );

		$property = new \ReflectionProperty( WC_PSR_Logger::class, 'wc_logger' );
		$property->setAccessible( true );
		$logger = $property->getValue( $sut );

		assert( is_null( $logger ) );

		$mock_logger = new class() implements \WC_Logger_Interface {

			/**
			 * The logged message, public to verify in the test.
			 *
			 * @var string
			 */
			public ?string $log_message = null;

			public function add( $handle, $message, $level = \WC_Log_Levels::NOTICE ) {
				// TODO: Implement add() method.
			}

			public function log( $level, $message, $context = array() ) {
				$this->log_message = $message;
			}

			public function emergency( $message, $context = array() ) {

			}

			public function alert( $message, $context = array() ) {
				// TODO: Implement alert() method.
			}

			public function critical( $message, $context = array() ) {
				// TODO: Implement critical() method.
			}

			public function error( $message, $context = array() ) {
				$this->log( __FUNCTION__, $message, $context );
			}

			public function warning( $message, $context = array() ) {
				// TODO: Implement warning() method.
			}

			public function notice( $message, $context = array() ) {
				// TODO: Implement notice() method.
			}

			public function info( $message, $context = array() ) {
				// TODO: Implement info() method.
			}

			public function debug( $message, $context = array() ) {
				// TODO: Implement debug() method.
			}
		};

		/**
		 * `wc_get_logger` has a filter to allow substitution another class. We don't use it in general, because it
		 * would apply to every plugin on the site. But we'll use a mock for the tests.
		 * TODO: This filter is added too late.
		 */
		add_filter(
			'woocommerce_logging_class',
			function( $class_name ) use ( $mock_logger ) {
				return $mock_logger;
			}
		);

		// The log message should be enqueued until plugins_loaded.
		$sut->error( 'error log message' );

		$this->assertNull( $mock_logger->log_message );

		do_action( 'woocommerce_loaded' );

		$this->assertEquals( 'error log message', $mock_logger->log_message );
	}

}
