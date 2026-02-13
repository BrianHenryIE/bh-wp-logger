<?php
/**
 * Tests for I18n. Tests load_plugin_textdomain.
 *
 * @package BH_WP_Logger
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BH_WP_Logger_Test_Plugin\WP_Includes;

use BrianHenryIE\WP_Logger\WPUnit_Testcase;

/**
 * Class I18n_Test
 *
 * @coversNothing
 */
class I18n_WPUnit_Test extends WPUnit_Testcase {

	/**
	 * Checks if the filter run by WordPress in the load_plugin_textdomain() function is called.
	 */
	public function test_load_plugin_textdomain_function() {

		$this->markTestSkipped( 'I think this is maybe the wrong way to do it now.' );

		$called        = false;
		$actual_domain = null;

		$filter = function ( $locale, $domain ) use ( &$called, &$actual_domain ) {

			$called        = true;
			$actual_domain = $domain;

			return $locale;
		};

		add_filter( 'plugin_locale', $filter, 10, 2 );

		$i18n = new I18n();

		$i18n->load_plugin_textdomain();

		$this->assertTrue( $called, 'plugin_locale filter not called within load_plugin_textdomain() suggesting it has not been set by the plugin.' );
		$this->assertEquals( 'bh-wp-logger-development-plugin', $actual_domain );
	}
}
