<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Logs_Page
 */
class Logs_Page_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @covers ::enqueue_styles
	 * @see wp_enqueue_style()
	 */
	public function test_enqueue_styles() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-enqueue-styles',
			)
		);
		$logger   = new ColorLogger();

		$screen     = new \stdClass();
		$screen->id = 'admin_page_test-enqueue-styles-logs';

		// Return any old url.
		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $screen,
				'times'  => 1,
			)
		);

		global $plugin_root_dir;
		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => $plugin_root_dir . '/admin/',
				'times'  => 1,
			)
		);

		$version = '1.0.0';

		\WP_Mock::userFunction(
			'wp_enqueue_style',
			array(
				'args'  => array( 'test-enqueue-styles-logs', \WP_Mock\Functions::type( 'string' ), array(), $version, 'all' ),
				'times' => 1,
			)
		);

		$sut = new Logs_Page( $api, $settings, $logger );

		$sut->enqueue_styles();

	}



	/**
	 * Verifies enqueue_styles() calls wp_enqueue_style() with appropriate parameters.
	 * Verifies the .css file exists.
	 *
	 * @covers ::enqueue_scripts
	 * @see wp_enqueue_style()
	 */
	public function test_enqueue_scripts() {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-enqueue-styles',
			)
		);
		$logger   = new ColorLogger();

		$screen     = new \stdClass();
		$screen->id = 'admin_page_test-enqueue-styles-logs';

		// Return any old url.
		\WP_Mock::userFunction(
			'get_current_screen',
			array(
				'return' => $screen,
				'times'  => 1,
			)
		);

		global $plugin_root_dir;
		// Return any old url.
		\WP_Mock::userFunction(
			'plugin_dir_url',
			array(
				'return' => $plugin_root_dir . '/admin/',
				'times'  => 1,
			)
		);

		$handle  = 'bh-wp-logger-admin-logs-page-test-enqueue-styles';
		$version = '1.0.0';

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'args'  => array(
					$handle,
					\WP_Mock\Functions::type( 'string' ),
					array( 'jquery' ),
					$version,
					true,
				),
				'times' => 1,
			)
		);

		$sut = new Logs_Page( $api, $settings, $logger );

		$sut->enqueue_scripts();

	}

}
