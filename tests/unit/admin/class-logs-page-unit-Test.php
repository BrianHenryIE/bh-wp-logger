<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Logs_Page
 */
class Logs_Page_Unit_Test extends Unit_Testcase {



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
		// TODO: It would be nice to use ColorLogger here with this.
		$logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

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
				'args'  => array( 'test-enqueue-styles-logs', \WP_Mock\Functions::type( 'string' ), array(), $version ),
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
		// TODO: It would be nice to use ColorLogger here with this.
		$logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

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
		$version = '1.1.0';

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'args'  => array(
					$handle,
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ), // 2023-03: jquery, renderjson, colresizable
					$version,
					true,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'args'  => array(
					'renderjson',
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'string' ), // version.
					true,
				),
				'times' => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_enqueue_script',
			array(
				'args'  => array(
					'colresizable',
					\WP_Mock\Functions::type( 'string' ),
					\WP_Mock\Functions::type( 'array' ),
					\WP_Mock\Functions::type( 'string' ), // version.
					true,
				),
				'times' => 1,
			)
		);

		\WP_Mock::passthruFunction( 'plugin_dir_url' );

		$sut = new Logs_Page( $api, $settings, $logger );

		$sut->enqueue_scripts();
	}

	/**
	 * The `global $title` should be set on the logs page.
	 *
	 * @covers ::set_page_title
	 */
	public function test_set_page_title_on_logs_page(): void {
		global $pagenow, $plugin_page, $title;
		$pagenow     = 'admin.php';
		$plugin_page = 'test-plugin-logs';
		$title       = null;

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
				'get_plugin_name' => 'Test Plugin',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$sut = new Logs_Page( $api, $settings, $logger );
		$sut->set_page_title();

		$this->assertSame( 'Test Plugin Logs page', $title );
	}

	/**
	 * Other admin.php pages (e.g. WooCommerce settings) must not have their title overwritten.
	 *
	 * @covers ::set_page_title
	 */
	public function test_set_page_title_not_on_other_admin_php_page(): void {
		global $pagenow, $plugin_page, $title;
		$pagenow     = 'admin.php';
		$plugin_page = 'wc-settings';
		$title       = 'WooCommerce Settings';

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
				'get_plugin_name' => 'Test Plugin',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$sut = new Logs_Page( $api, $settings, $logger );
		$sut->set_page_title();

		$this->assertSame( 'WooCommerce Settings', $title );
	}

	/**
	 * Pages outside admin.php (e.g. edit.php) must not have their title overwritten.
	 *
	 * @covers ::set_page_title
	 */
	public function test_set_page_title_not_on_other_pagenow(): void {
		global $pagenow, $plugin_page, $title;
		$pagenow     = 'edit.php';
		$plugin_page = null;
		$title       = 'Posts';

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
				'get_plugin_name' => 'Test Plugin',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$sut = new Logs_Page( $api, $settings, $logger );
		$sut->set_page_title();

		$this->assertSame( 'Posts', $title );
	}
}
