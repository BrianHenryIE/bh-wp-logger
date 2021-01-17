<?php
/**
 * Tests for the root plugin file.
 *
 * @package BH_WP_Logger
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Logger;

use BH_WP_Logger\includes\BH_WP_Logger;
use BH_WP_Logger_Test_Plugin\includes\BH_WP_Logger_Test_Plugin;

/**
 * Class Plugin_WP_Mock_Test
 */
class Plugin_WP_Mock_Test extends \Codeception\Test\Unit {

	protected function _before() {
		\WP_Mock::setUp();
	}

	/**
	 * Verifies the plugin initialization.
	 */
	public function test_plugin_include() {

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		require_once $plugin_root_dir . '/bh-wp-logger-test-plugin.php';

		$this->assertArrayHasKey( 'bh_wp_logger_test_plugin', $GLOBALS );

		$this->assertInstanceOf( BH_WP_Logger_Test_Plugin::class, $GLOBALS['bh_wp_logger_test_plugin'] );

	}


	/**
	 * Verifies the plugin does not output anything to screen.
	 */
	public function test_plugin_include_no_output() {

		global $plugin_root_dir;

		\WP_Mock::userFunction(
			'plugin_dir_path',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ) ),
				'return' => $plugin_root_dir . '/',
			)
		);

		\WP_Mock::userFunction(
			'register_activation_hook'
		);

		\WP_Mock::userFunction(
			'register_deactivation_hook'
		);

		ob_start();

		require_once $plugin_root_dir . '/bh-wp-logger-test-plugin.php';

		$printed_output = ob_get_contents();

		ob_end_clean();

		$this->assertEmpty( $printed_output );

	}

}
