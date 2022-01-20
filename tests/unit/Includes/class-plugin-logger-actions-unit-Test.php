<?php

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Includes\Plugin_Logger_Actions
 */
class Plugin_Logger_Actions_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_construct(): void {

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new Plugin_Logger_Actions( $api, $settings, $logger );
	}

	/**
	 * @covers ::add_plugins_page_hooks
	 */
	public function test_plugins_page_hooks(): void {

		$basename = 'test-plugin/test-plugin.php';

		\WP_Mock::expectFilterAdded(
			"plugin_action_links_{$basename}",
			array( new AnyInstance( Plugins_Page::class ), 'add_logs_action_link' ),
			10,
			4
		);

		$logger   = new ColorLogger();
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_basename' => $basename,
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );
		new Plugin_Logger_Actions( $api, $settings, $logger );
	}
}
