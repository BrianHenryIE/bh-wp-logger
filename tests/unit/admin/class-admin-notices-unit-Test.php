<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Admin_Notices
 */
class Admin_Notices_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::admin_notices
	 */
	public function test_delete_option_when_on_logs_page(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);
		$api      = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'delete_option',
			array(
				'args'  => array( 'test-recent-error-data' ),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_key',
			array(
				'return_arg' => true,
				'times'      => 1,
			)
		);

		$_GET['page'] = 'test-logs';

		$sut = new Admin_Notices( $api, $settings, $logger );

		$sut->admin_notices();
	}


	/**
	 * @covers ::admin_notices
	 */
	public function test_return_early_when_not_in_admin_or_ajax(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::never(),
			)
		);
		$api      = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_doing_ajax',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		$sut = new Admin_Notices( $api, $settings, $logger );

		$sut->admin_notices();
	}
}
