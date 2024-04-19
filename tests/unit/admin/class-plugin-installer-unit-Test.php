<?php
/**
 * After a plugin has been installed/updated, beside "Go to plugin installer", add a link to the plugin's Logs page.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Plugin_Installer
 */
class Plugin_Installer_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	/**
	 * Without this, WP_Mock userFunctions might stick around for the next test.
	 */
	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::add_logs_link
	 * @covers ::__construct
	 */
	public function test_logs_link_added(): void {

		\WP_Mock::userFunction(
			'admin_url',
			array(
				'return_arg' => 0,
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once( 'bh-wp-my-plugin/bh-wp-my-plugin.php' ),
				'get_plugin_slug'     => Expected::once( 'bh-wp-my-plugin' ),
				'get_plugin_name'     => Expected::once( 'My Plugin' ),
			)
		);

		$sut = new Plugin_Installer( $settings );

		$result = $sut->add_logs_link( array(), null, 'bh-wp-my-plugin/bh-wp-my-plugin.php' );

		$this->assertIsArray( $result );

		$link_html = array_pop( $result );

		$this->assertStringContainsString( 'Go to My Plugin logs', $link_html );

		$this->assertStringContainsString( 'href="/admin.php?page=bh-wp-my-plugin', $link_html );
	}


	/**
	 * @covers ::add_logs_link
	 */
	public function test_return_early_for_other_plugins(): void {

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_basename' => Expected::once( 'bh-wp-my-plugin/bh-wp-my-plugin.php' ),
				'get_plugin_slug'     => Expected::never( 'bh-wp-my-plugin' ),
				'get_plugin_name'     => Expected::never( 'My Plugin' ),
			)
		);
		$logger   = new ColorLogger();

		$sut = new Plugin_Installer( $settings, $logger );

		$result = $sut->add_logs_link( array(), null, 'any-other-plugin/any-other-plugin.php' );

		$this->assertIsArray( $result );

		$this->assertEmpty( $result );
	}
}
