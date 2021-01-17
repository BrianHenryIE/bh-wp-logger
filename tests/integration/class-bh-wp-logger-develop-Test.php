<?php
/**
 * Class Plugin_Test. Tests the root plugin setup.
 *
 * @package BH_WP_Logger
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Logger_Test_Plugin;

use BH_WP_Logger_Test_Plugin\includes\BH_WP_Logger_Test_Plugin;

/**
 * Verifies the plugin has been instantiated and added to PHP's $GLOBALS variable.
 */
class Plugin_Develop_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * Test the main plugin object is added to PHP's GLOBALS and that it is the correct class.
	 */
	public function test_plugin_instantiated() {

		$this->assertArrayHasKey( 'bh_wp_logger_test_plugin', $GLOBALS );

		$this->assertInstanceOf( BH_WP_Logger_Test_Plugin::class, $GLOBALS['bh_wp_logger_test_plugin'] );
	}

}
