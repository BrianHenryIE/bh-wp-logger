<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use DateTime;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Plugins_Page
 */
class Plugins_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Without this, WP_Mock userFunctions might stick around for the next test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_constructor(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );

		$sut = new Plugins_Page( $api, $settings );

		$this->assertInstanceOf( Plugins_Page::class, $sut );
	}


	/**
	 * @covers ::add_logs_action_link
	 */
	public function test_logs_link_added(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_url' => 'admin.php?page=bh-wp-logger-test-plugin-logs',
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wp-logger-test-plugin' )
		);
		$logger   = new ColorLogger();

		$sut = new Plugins_Page( $api, $settings, $logger );

		// Return the default value when get_option is called.
		\WP_Mock::userFunction(
			'get_option',
			array(
				'return_arg' => 1,
			)
		);

		$result = $sut->add_logs_action_link( array(), '', array(), '' );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( 'Logs', $link_html );

		// To distinguish this from the later test.
		$this->assertStringNotContainsString( '<strong>', $link_html );

		$this->assertStringContainsString( 'href="admin.php?page=bh-wp-logger-test-plugin-logs', $link_html );
	}


	/**
	 * @covers ::add_logs_action_link
	 */
	public function test_logs_link_placed_before_deactivate(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_url' => 'admin.php?page=bh-wp-logger-test-plugin-logs',
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wp-logger-test-plugin' )
		);
		$logger   = new ColorLogger();

		$sut = new Plugins_Page( $api, $settings, $logger );

		// Return the default value when get_option is called.
		\WP_Mock::userFunction(
			'get_option',
			array(
				'return_arg' => 1,
			)
		);

		$existing_links = array(
			'Settings',
			'Deactivate',
		);

		$result = $sut->add_logs_action_link( $existing_links, '', array(), '' );

		$this->assertStringContainsString( 'Settings', $result[0] );
		$this->assertStringContainsString( 'Logs', $result[1] );
		$this->assertStringContainsString( 'Deactivate', $result[2] );
	}

	/**
	 * @covers ::add_logs_action_link
	 */
	public function test_new_logs_get_highlighted(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				// NB: the order here matters because the milliseconds difference make the test pass/fail.
				'get_last_logs_view_time' => new DateTime(),
				'get_last_log_time'       => new DateTime(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array( 'get_plugin_slug' => 'bh-wp-logger-test-plugin' )
		);
		$logger   = new ColorLogger();

		// When the latest log time is more recent than the last logs view time...
		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wp-logger-test-plugin-last-log-time', 0 ),
				'return' => time(),
			)
		);
		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'bh-wp-logger-test-plugin-last-logs-view-time', 0 ),
				'return' => time() - 60 * 60, // HOUR_IN_SECONDS.
			)
		);

		$sut = new Plugins_Page( $api, $settings, $logger );

		$result = $sut->add_logs_action_link( array(), '', array(), '' );

		$this->assertIsArray( $result );

		$link_html = $result[0];

		$this->assertStringContainsString( '<b>', $link_html );
	}
}
