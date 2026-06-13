<?php
/**
 * Use some deprecated etc functions and check the logger records them.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\WPUnit_Testcase;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger;

/**
 * @coversNothing
 */
class Functions_Integration_Test extends \BrianHenryIE\WP_Logger\WPUnit_Testcase {

	/**
	 * Execute a deprecated function and verify it is handled by our logger.
	 *
	 * @see Functions::log_deprecated_functions_only_once_per_day()
	 */
	public function test_deprecated_function(): void {

		/**
		 * Remove wp-browser's deprecated warnings test fail mechanism.
		 *
		 * @see WPTestCase.php:303
		 *
		 * @var \WP_Hook[] $wp_filter
		 */
		global $wp_filter;
		array_pop( $wp_filter['deprecated_function_run']->callbacks[10] );

		$test_logger = $this->logger;
		$logger      = Logger::instance();
		$logger->setLogger( $test_logger );

		$closure = function () {
			// phpcs:disable WordPress.WP.DeprecatedFunctions.documentation_linkFound
			documentation_link();
		};

		\BH_WP_Logger_Test_Plugin\run_closure_in_plugin( $closure );

		$this->assertTrue( $test_logger->hasWarningThatContains( 'documentation_link' ) );
	}
}
