<?php

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\PHP\PHP_Shutdown_Handler;
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

	/**
	 * @covers ::add_error_handler_hooks
	 */
	public function test_add_error_handler_hooks(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( PHP_Error_Handler::class ), 'init' ),
			2
		);

		\WP_Mock::expectActionAdded(
			'plugins_loaded',
			array( new AnyInstance( PHP_Shutdown_Handler::class ), 'init' ),
			2
		);

		new Plugin_Logger_Actions( $api, $settings, $logger );

	}

	/**
	 * @covers ::add_wordpress_error_handling_hooks
	 */
	public function test_add_wordpress_error_handling_hooks(): void {

		\WP_Mock::expectActionAdded(
			'deprecated_function_run',
			array( new AnyInstance( Functions::class ), 'log_deprecated_functions_only_once_per_day' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'deprecated_argument_run',
			array( new AnyInstance( Functions::class ), 'log_deprecated_arguments_only_once_per_day' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'doing_it_wrong_run',
			array( new AnyInstance( Functions::class ), 'log_doing_it_wrong_only_once_per_day' ),
			10,
			3
		);

		\WP_Mock::expectActionAdded(
			'deprecated_hook_run',
			array( new AnyInstance( Functions::class ), 'log_deprecated_hook_only_once_per_day' ),
			10,
			4
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = new ColorLogger();

		new Plugin_Logger_Actions( $api, $settings, $logger );
	}

}