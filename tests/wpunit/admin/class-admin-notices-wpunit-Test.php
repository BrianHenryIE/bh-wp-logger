<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WPUnit_Testcase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Admin_Notices
 */
class Admin_Notices_WPUnit_Test extends WPUnit_Testcase {

	/**
	 * Date should be displayed in UTC and in local time.
	 */
	public function test_date_in_notice(): void {

		$this->markTestIncomplete();

		// 2021-09-13T22:05:11Z UTC

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug'             => 'bh-wp-logger-tests',
				'get_plugin_slug_underscored' => 'bh_wp_logger_tests',
			)
		);
		$logger   = new ColorLogger();

		$option_name = 'bh_wp_logger_tests_recent_error_data';

		$sut = new Admin_Notices( $api, $settings, $logger );

		$sut->admin_notices();
	}

	/**
	 * The notice id, error-detail option name, and dismissed option name all use underscores, derived from the
	 * hyphenated plugin slug.
	 *
	 * @covers ::admin_notices
	 * @covers ::get_error_detail_option_name
	 * @covers ::get_last_error
	 */
	public function test_recent_error_notice_uses_underscored_names(): void {

		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_last_logs_view_time' => null,
				'get_log_url'             => 'https://example.org/wp-admin/admin.php?page=bh-wp-logger-tests-logs',
			)
		);
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug'             => 'bh-wp-logger-tests',
				'get_plugin_slug_underscored' => 'bh_wp_logger_tests',
				'get_plugin_name'             => 'BH WP Logger Tests',
			)
		);

		update_option(
			'bh_wp_logger_tests_recent_error_data',
			array(
				'message'   => 'Something went wrong',
				'timestamp' => time(),
			)
		);

		set_current_screen( 'dashboard' );
		global $pagenow;
		$pagenow = 'index.php';

		$sut = new Admin_Notices( $api, $settings, $this->logger );

		$sut->admin_notices();

		$this->assertNotNull( $sut->get( 'bh_wp_logger_tests_recent_error' ) );
		$this->assertNull( $sut->get( 'bh-wp-logger-tests-recent-error' ) );

		$this->assertTrue( has_filter( 'pre_update_option_wptrt_notice_dismissed_bh_wp_logger_tests_recent_error' ) );

		// Dismissing the notice deletes the error detail option.
		update_option( 'wptrt_notice_dismissed_bh_wp_logger_tests_recent_error', true );

		$this->assertFalse( get_option( 'bh_wp_logger_tests_recent_error_data' ) );
	}
}
