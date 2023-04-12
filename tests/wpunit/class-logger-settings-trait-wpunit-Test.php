<?php
/**
 * @see https://core.trac.wordpress.org/ticket/42670
 *
 * @package brianhenryie/bh-wp-logger
 */

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Logger_Settings_Trait
 */
class Logger_Settings_Trait_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_default_info(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'test-plugin/test-plugin.php';
			}
		};

		self::assertEquals( 'info', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_wp_options(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'test-plugin/test-plugin.php';
			}
		};

		update_option( 'test-plugin_log_level', 'error' );

		self::assertEquals( 'error', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_no_basename(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				throw new Exception();
			}
		};

		self::assertEquals( 'none', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'test-plugin/test-plugin.php';
			}
		};

		$plugins_array = array(
			'test-plugin/test-plugin.php' =>
				array(
					'Name' => 'BH WP Logger Test Plugin',
				),
		);

		wp_cache_set( 'plugins', $plugins_array, 'plugins' );

		self::assertEquals( 'BH WP Logger Test Plugin', $sut->get_plugin_name() );
	}

	/**
	 * @covers ::get_plugin_slug
	 */
	public function test_get_plugin_slug(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'test-plugin/test-plugin.php';
			}
		};

		self::assertEquals( 'test-plugin', $sut->get_plugin_slug() );
	}
}
