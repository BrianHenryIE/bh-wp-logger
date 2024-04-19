<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WP_Logger
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Logger_Test_Plugin;

use BH_WP_Logger_Test_Plugin\WP_Includes\BH_WP_Logger_Test_Plugin;

/**
 * Class Plugin_WP_Mock_Test
 *
 * @coversNothing
 */
class Plugin_Unit_Test extends \Codeception\Test\Unit {


	protected function setup(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		\WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include(): void {

		// Prevents code-coverage counting, and removes the need to define the WordPress functions that are used in that class.
		\Patchwork\redefine(
			array( BH_WP_Logger_Test_Plugin::class, '__construct' ),
			function ( $settings, $logger ) {}
		);

		$plugin_root_dir = dirname( __DIR__, 2 ) . '/test-plugin';

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'plugin_basename',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => 'bh-wp-logger-test-plugin/bh-wp-logger-test-plugin.php',
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins' ),
				'return' => array(),
			)
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'get_current_user_id'
		);

		\WP_Mock::userFunction(
			'wp_normalize_path',
			array(
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'active_plugins', \WP_Mock\Functions::type( 'array' ) ),
				'return' => array( 'woocommerce/woocommerce.php' ),
			)
		);

		\WP_Mock::userFunction(
			'did_action',
			array(
				'return' => false,
			)
		);

		\WP_Mock::userFunction(
			'add_action',
			array(
				'return' => false,
			)
		);

		ob_start();

		include $plugin_root_dir . '/bh-wp-logger-test-plugin.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

		$this->assertArrayHasKey( 'bh_wp_logger_test_plugin', $GLOBALS );

		$this->assertInstanceOf( BH_WP_Logger_Test_Plugin::class, $GLOBALS['bh_wp_logger_test_plugin'] );
	}
}
