<?php
/**
 * Use some deprecated etc functions and check the logger records them.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger;

/**
 * @coversNothing
 */
class Functions_Integration_Test  extends \Codeception\TestCase\WPTestCase {

	/**
	 * Execute a deprecated function and verify it is handled by our logger.
	 *
	 * @see Functions::log_deprecated_functions_only_once_per_day()
	 */
	public function test_deprecated_function(): void {

		$test_logger = new ColorLogger();
		$logger      = Logger::instance();
		$logger->setLogger( $test_logger );

		$closure = function() {
			// phpcs:disable WordPress.WP.DeprecatedFunctions.documentation_linkFound
			documentation_link();
		};

		\BH_WP_Logger_Test_Plugin\run_closure_in_plugin( $closure );

		$this->assertTrue( $test_logger->hasWarningThatContains( 'documentation_link' ) );
	}

}
