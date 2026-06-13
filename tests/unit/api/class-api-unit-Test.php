<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass  \BrianHenryIE\WP_Logger\API\API
 */
class API_Unit_Test extends Unit_Testcase {

	/**
	 * @covers ::get_backtrace
	 */
	public function test_backtrace_excludes_logger_files(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$api = new API( $settings, $logger );

		$result = $api->get_backtrace();

		$this->assertEquals( $result[0]['file'], __FILE__ );
	}

	/**
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_logs_simple(): void {

		$this->markTestSkipped( 'Test data file not found at: tests/_data/simple-log-8-lines.log' );

		global $project_root_dir;

		$simple_log_file = $project_root_dir . '/tests/_data/simple-log-8-lines.log';

		if ( ! file_exists( $simple_log_file ) ) {
			$this->markTestSkipped( 'Test data file not found at: ' . $simple_log_file );
		}

		$logger   = $this->logger;
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

		$multiline_message_log_file = codecept_root_dir( '/tests/_data/context-not-rendering.log' );

		if ( ! file_exists( $multiline_message_log_file ) ) {
			$this->markTestSkipped( 'Test data file not found at: ' . $multiline_message_log_file );
		}

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new API( $settings, $logger );

		$result = $sut->parse_log( $multiline_message_log_file );

		$this->assertCount( 15, $result );
	}

	/**
	 * Parsing date in and out of int / datetime results in a one-second difference, which is fine.
	 *
	 * @covers ::get_last_log_time
	 */
	public function test_get_last_log_time(): void {

		global $wp_filesystem;
		$wp_filesystem = new \WP_Filesystem_Direct( array() );

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);

		$sut = new class( $settings, $logger ) extends API {
			public function get_log_files( ?string $date = null ): array {
				$logfile_contents = <<<'EOD'
2022-02-23T22:55:46+00:00 ERROR test_backtrace_excludes_logger_files
{"debug_backtrace":[],"filters":[]}
2022-02-23T22:56:00+00:00 INFO Registered the `private_uploads_check_url_bh-wp-logger-development-plugin_logger` cron job.
[]
2022-02-23T22:56:00+00:00 ERROR test_backtrace_excludes_logger_files
{"debug_backtrace":[],"filters":[]}
EOD;

				$logfilepath = sys_get_temp_dir() . '/get_last_log_time.log';

				file_put_contents( $logfilepath, $logfile_contents );

				$timestamp = ( new \DateTimeImmutable( '2022-02-23T22:56:00+00:00', new \DateTimeZone( 'UTC' ) ) )->format( 'U' );

				touch( $logfilepath, $timestamp );

				return array( $logfilepath );
			}
		};

		\WP_Mock::userFunction(
			'get_transient',
			array(
				'args'   => array( 'test-last-log-time' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'activate_plugins' ),
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'set_transient',
			array(
				'args'  => array( 'test-last-log-time', '2022-02-23T22:56:01+00:00', DAY_IN_SECONDS ),
				'times' => 1,
			)
		);

		$result = $sut->get_last_log_time();

		$expected = new \DateTime( '2022-02-23T22:56:01+00:00' );
		$this->assertEquals( $expected->getTimestamp(), $result->getTimestamp() );
	}

	/**
	 * @covers ::is_backtrace_contains_plugin
	 */
	public function test_is_backtrace_contains_plugin(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'development-plugin',
			)
		);

		$cache_hash = 'hash1';

		$GLOBALS['bh_wp_logger_cache'] = array(
			$cache_hash => array(
				array(
					'file' => 'development-plugin/subfolder/guilty-file.php',
				),
			),
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'times'  => 1,
				'return' => 'development-plugin/subfolder/guilty-file.php',
			)
		);

		\WP_Mock::passthruFunction( 'sanitize_key' );

		\Patchwork\redefine(
			'realpath',
			fn( $value ) => $value
		);

		$sut = new API( $settings, $logger );

		$result = $sut->is_backtrace_contains_plugin( $cache_hash );

		$this->assertTrue( $result );
	}
}
