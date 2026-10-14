<?php

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\WPUnit_Testcase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WP_Includes\Functions
 */
class Functions_WPUnit_Test extends WPUnit_Testcase {

	/**
	 * Happy path. Test:
	 * * the deprecation warning is logged
	 * * the transient is added
	 * * the filter to prevent native WordPress logging is added
	 *
	 * @covers ::log_deprecated_functions_only_once_per_day
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
				'get_plugin_slug'             => 'development-plugin',
				'get_plugin_slug_underscored' => 'development_plugin',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Functions( $api, $settings, $logger );

		assert( false === get_transient( 'log_deprecated_function_my_deprecated_function_development_plugin' ) );
		assert( false === has_filter( 'deprecated_function_trigger_error', '__return_false' ) );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		$log_message = 'my_deprecated_function is <strong>deprecated</strong> since version 5.9.0! Use my_replacement_function instead.';

		$this->assertTrue( $logger->hasWarning( $log_message ) );

		$this->assertNotFalse( get_transient( 'log_deprecated_function_my_deprecated_function_development_plugin' ) );

		// `has_filter` function returns the priority.
		$this->assertNotFalse( has_filter( 'deprecated_function_trigger_error', '__return_false' ) );
	}

	/**
	 * @covers ::log_deprecated_functions_only_once_per_day
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
	 * @covers ::log_deprecated_functions_only_once_per_day
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
				'get_plugin_slug'             => 'development-plugin',
				'get_plugin_slug_underscored' => 'development_plugin',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Functions( $api, $settings, $logger );

		assert( false === get_transient( 'log_deprecated_function_my_deprecated_function_development_plugin' ) );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		remove_filter( 'deprecated_function_trigger_error', '__return_false' );

		assert( false === has_filter( 'deprecated_function_trigger_error', '__return_false' ) );
		assert( false !== get_transient( 'log_deprecated_function_my_deprecated_function_development_plugin' ) );

		$logger = new ColorLogger();
		$sut->setLogger( $logger );

		$sut->log_deprecated_functions_only_once_per_day( 'my_deprecated_function', 'my_replacement_function', '5.9.0' );

		$log_message = 'my_deprecated_function is <strong>deprecated</strong> since version 5.9.0! Use my_replacement_function instead.';

		$this->assertFalse( $logger->hasWarning( $log_message ) );

		// `has_filter` function returns the priority.
		$this->assertNotFalse( has_filter( 'deprecated_function_trigger_error', '__return_false' ) );
	}

	protected function get_sut_for_transient_name_tests(): Functions {
		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'is_backtrace_contains_plugin' => true,
			)
		);
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug'             => 'development-plugin',
				'get_plugin_slug_underscored' => 'development_plugin',
			)
		);

		return new Functions( $api, $settings, new ColorLogger() );
	}

	/**
	 * @covers ::log_deprecated_arguments_only_once_per_day
	 */
	public function test_deprecated_argument_transient_name(): void {

		$sut = $this->get_sut_for_transient_name_tests();

		$sut->log_deprecated_arguments_only_once_per_day( 'my_function', 'Use the other argument.', '5.9.0' );

		$this->assertNotFalse( get_transient( 'log_deprecated_argument_my_function_development_plugin' ) );
	}

	/**
	 * @covers ::log_doing_it_wrong_only_once_per_day
	 */
	public function test_doing_it_wrong_transient_name(): void {

		$sut = $this->get_sut_for_transient_name_tests();

		$sut->log_doing_it_wrong_only_once_per_day( 'my_function', 'Called too early.', '5.9.0' );

		$this->assertNotFalse( get_transient( 'log_doing_it_wrong_run_my_function_development_plugin' ) );
	}

	/**
	 * @covers ::log_deprecated_hook_only_once_per_day
	 */
	public function test_deprecated_hook_transient_name(): void {

		$sut = $this->get_sut_for_transient_name_tests();

		$sut->log_deprecated_hook_only_once_per_day( 'my_hook', 'my_replacement_hook', '5.9.0', '' );

		$this->assertNotFalse( get_transient( 'log_deprecated_hook_run_my_hook_development_plugin' ) );
	}
}
