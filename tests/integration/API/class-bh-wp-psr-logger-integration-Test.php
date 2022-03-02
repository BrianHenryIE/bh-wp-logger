<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Logger;

/**
 * @coversNothing
 */
class BH_WP_PSR_Logger_Integration_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * The backtrace was starting from inside the logger, naturally, so exclude the first few backtrace entries
	 * until the actual $logger->error call.
	 */
	public function test_backtrace_excludes_logger_files(): void {

		// Integration test has already loaded the test plugin.
		$logger = Logger::instance();

		$logger->error( __FUNCTION__ );

		$log_file_paths = $logger->get_log_files();

		$log_file_path = array_pop( $log_file_paths );

		$log_contents = file_get_contents( $log_file_path );
		$log_lines    = explode( "\n", $log_contents );

		array_pop( $log_lines ); // blank line at end of file.

		$context_line = array_pop( $log_lines );
		$context      = json_decode( $context_line, true );

		$debug_backtrace = $context['debug_backtrace'];

		$this->assertEquals( __FILE__, $debug_backtrace[0]['file'] );
	}
}
