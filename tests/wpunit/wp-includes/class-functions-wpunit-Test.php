<?php

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\NullLogger;
use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use Codeception\TestCase\WPTestCase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WP_Includes\Functions
 */
class Functions_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Happy path. Test:
	 * * the deprecation warning is logged
	 * * the transient is added
	 * * the filter to prevent native WordPress logging is added
	 *
	 * @covers ::log_deprecated_functions_only_once_per_day()
	 */
	public function test_deprecated_function(): void {

		/**
		 * WP Browser adds the `deprecated_function_trigger_error` `__return_false` filter, so we need to remove it to verify behaviour.
		 *
		 * @see WPTestCase::expectDeprecated()
		 */
		remove_filter( 'deprecated_function_trigger_error', '__return_false' );

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_backtrace_contains_plugin' => true,
			)
		);
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Functions( $api, $settings, $logger );

		assert( false === get_transient( 'log_deprecated_function_my_deprecated_function_test-plugin' ) );
		assert( false === has_filter( 'deprecated_function_trigger_error', '__return_false' ) );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		$log_message = 'my_deprecated_function is <strong>deprecated</strong> since version 5.9.0! Use my_replacement_function instead.';

		$this->assertTrue( $logger->hasWarning( $log_message ) );

		$this->assertNotFalse( get_transient( 'log_deprecated_function_my_deprecated_function_test-plugin' ) );

		// `has_filter` function returns the priority.
		$this->assertNotFalse( has_filter( 'deprecated_function_trigger_error', '__return_false' ) );
	}

	/**
	 * @covers ::log_deprecated_functions_only_once_per_day()
	 */
	public function test_return_early_when_not_related_to_this_plugin(): void {

		/**
		 * WP Browser adds the `deprecated_function_trigger_error` `__return_false` filter, so we need to remove it to verify behaviour.
		 *
		 * @see WPTestCase::expectDeprecated()
		 */
		remove_filter( 'deprecated_function_trigger_error', '__return_false' );

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_backtrace_contains_plugin' => false,
			)
		);
		$settings = $this->makeEmpty( Logger_Settings_Interface::class, );
		$logger   = new ColorLogger();

		$sut = new Functions( $api, $settings, $logger );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		$log_message = 'my_deprecated_function is <strong>deprecated</strong> since version 5.9.0! Use my_replacement_function instead.';

		$this->assertFalse( $logger->hasWarning( $log_message ) );
	}

	/**
	 * @covers ::log_deprecated_functions_only_once_per_day()
	 */
	public function test_do_not_log_again_if_transient_present(): void {

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_backtrace_contains_plugin' => true,
			)
		);
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = new NullLogger();

		$sut = new Functions( $api, $settings, $logger );

		assert( false === get_transient( 'log_deprecated_function_my_deprecated_function_test-plugin' ) );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		remove_filter( 'deprecated_function_trigger_error', '__return_false' );

		assert( false === has_filter( 'deprecated_function_trigger_error', '__return_false' ) );
		assert( false !== get_transient( 'log_deprecated_function_my_deprecated_function_test-plugin' ) );

		$logger = new ColorLogger();
		$sut->setLogger( $logger );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		$log_message = 'my_deprecated_function is <strong>deprecated</strong> since version 5.9.0! Use my_replacement_function instead.';

		$this->assertFalse( $logger->hasWarning( $log_message ) );

		// `has_filter` function returns the priority.
		$this->assertNotFalse( has_filter( 'deprecated_function_trigger_error', '__return_false' ) );

	}

}
