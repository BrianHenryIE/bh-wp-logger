<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Admin\Logs_List_Table;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass  \BrianHenryIE\WP_Logger\API\API
 */
class API_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::get_backtrace
	 */
	public function test_backtrace_excludes_logger_files(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$api = new API( $settings, $logger );

		$result = $api->get_backtrace();

		$this->assertEquals( $result[0]->file, __FILE__ );

	}


	/**
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_logs_simple(): void {

		global $project_root_dir;

		$simple_log_file = $project_root_dir . '/tests/_data/simple-log-8-lines.log';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new API( $settings, $logger );

		$result = $sut->parse_log( $simple_log_file );

		$this->assertCount( 8, $result );

	}

	/**
	 * A log message could span multiple lines, e.g. fatal error backtrace.
	 *
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_logs_multiline_message(): void {

		global $project_root_dir;

		$multiline_message_log_file = $project_root_dir . '/tests/_data/context-not-rendering.log';

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new API( $settings, $logger );

		$result = $sut->parse_log( $multiline_message_log_file );

		$this->assertCount( 15, $result );

	}

}
