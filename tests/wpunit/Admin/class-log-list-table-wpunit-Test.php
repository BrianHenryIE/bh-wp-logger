<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Logs_List_Table
 */
class Logs_List_Table_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	public function test_parse_logs_simple(): void {

		global $project_root_dir;

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_files' => array( $project_root_dir . '/tests/_data/simple-log-8-lines.log' ),
			)
		);
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$args['screen'] = 'admin_page_bh-wp-logger-test-plugin-logs';

		$sut = new Logs_List_Table( $api, $settings, $logger, $args );

		$data = $sut->get_data();

		$this->assertCount( 8, $data );

	}

	/**
	 * A log message could span multiple lines, e.g. fatal error backtrace.
	 */
	public function test_parse_logs_multiline_message(): void {

		global $project_root_dir;

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_files' => array( $project_root_dir . '/tests/_data/context-not-rendering.log' ),
			)
		);
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		$args['screen'] = 'admin_page_bh-wp-logger-test-plugin-logs';

		$sut = new Logs_List_Table( $api, $settings, $logger, $args );

		$data = $sut->get_data();

		$this->assertCount( 15, $data );

	}

}
