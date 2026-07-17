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
	 * This line was not parsing correctly
	 *
	 * @covers ::parse_log
	 * @covers ::log_lines_to_entry
	 */
	public function test_parse_log_problem(): void {

		$log_entry = <<<'EOD'
2026-07-17T19:36:59+00:00 ERROR Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\Admin\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown
{"type":1,"message":"Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\\Admin\\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown","file":"/var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php","line":99,"debug_backtrace":[{"file":"/var/www/html/wp-content/uploads/development-plugin/includes/api/class-bh-wp-psr-logger.php","line":123,"function":"get_backtrace","class":"BrianHenryIE\\WP_Logger\\API\\API","object":{"BrianHenryIE\\WP_Logger\\Logger":[]},"type":"->","args":[]},{"file":"/var/www/html/wp-content/uploads/development-plugin/includes/api/class-bh-wp-psr-logger.php","line":87,"function":"log","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger","object":{"BrianHenryIE\\WP_Logger\\Logger":[]},"type":"->","args":["error","Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\\Admin\\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown",{"type":1,"message":"Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\\Admin\\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown","file":"/var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php","line":99}]},{"file":"/var/www/html/wp-content/uploads/development-plugin/includes/php/class-php-shutdown-handler.php","line":71,"function":"error","class":"BrianHenryIE\\WP_Logger\\API\\BH_WP_PSR_Logger","object":{"BrianHenryIE\\WP_Logger\\Logger":[]},"type":"->","args":["Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\\Admin\\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown",{"type":1,"message":"Uncaught Exception: log test exception in /var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php:99
Stack trace:
#0 /var/www/html/wp-includes/class-wp-hook.php(341): BH_WP_Logger_Test_Plugin\\Admin\\Admin_Ajax->handle_request('')
#1 /var/www/html/wp-includes/class-wp-hook.php(365): WP_Hook->apply_filters('', Array)
#2 /var/www/html/wp-includes/plugin.php(522): WP_Hook->do_action(Array)
#3 /var/www/html/wp-admin/admin-ajax.php(192): do_action('wp_ajax_log')
#4 {main}
  thrown","file":"/var/www/html/wp-content/uploads/development-plugin/development-plugin/Admin/class-admin-ajax.php","line":99}]},{"function":"handle","class":"BrianHenryIE\\WP_Logger\\PHP\\PHP_Shutdown_Handler","object":{"BrianHenryIE\\WP_Logger\\PHP\\PHP_Shutdown_Handler":[]},"type":"->","args":[]}],"filters":["wp_ajax_log"]}
EOD;

		$temp_file = tempnam( sys_get_temp_dir(), 'log' ) . '.txt';

		try {
			file_put_contents( $temp_file, $log_entry );

			$logger   = $this->logger;
			$settings = $this->makeEmpty( Logger_Settings_Interface::class );

			$sut = new API( $settings, $logger );

			$result = $sut->parse_log( $temp_file );

			$this->assertNotNull( $result[0]['context'], 'context not properly parsed from log entry');
		} finally {
			unlink( $temp_file );
		}
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
				'args'  => array(
					'test-last-log-time',
					'2022-02-23T22:56:01+00:00',
					60 * 60 * 24, // `DAY_IN_SECONDS`.
				),
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
