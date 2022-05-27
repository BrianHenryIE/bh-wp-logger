<?php

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Admin\AJAX;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\PHP\PHP_Shutdown_Handler;
use BrianHenryIE\WP_Logger\Private_Uploads\URL_Is_Public;
use WP_Mock\Matcher\AnyInstance;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WP_Includes\Plugin_Logger_Actions
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

		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );
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

		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );
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
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

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
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		new Plugin_Logger_Actions( $api, $settings, $logger );
	}

	/**
	 * @covers ::add_cron_hooks
	 */
	public function test_add_cron_hooks(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( Cron::class ), 'register_cron_job' )
		);
		\WP_Mock::expectActionAdded(
			'delete_logs_plugin-slug',
			array( new AnyInstance( Cron::class ), 'delete_old_logs' )
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		new Plugin_Logger_Actions( $api, $settings, $logger );
	}


	/**
	 * @covers ::add_private_uploads_hooks
	 */
	public function test_add_private_uploads_hooks(): void {

		\WP_Mock::expectFilterAdded(
			'bh_wp_private_uploads_url_is_public_warning_plugin-slug_logger',
			array( new AnyInstance( URL_Is_Public::class ), 'change_warning_message' ),
			10,
			2
		);

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		new Plugin_Logger_Actions( $api, $settings, $logger );
	}


	/**
	 * @covers ::define_init_hooks
	 */
	public function test_init_hooks(): void {

		\WP_Mock::expectActionAdded(
			'init',
			array( new AnyInstance( Init::class ), 'maybe_download_log' )
		);

		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );
		new Plugin_Logger_Actions( $api, $settings, $logger );
	}
}
