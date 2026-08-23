<?php

namespace BrianHenryIE\WP_Logger\Private_Uploads;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Private_Uploads\URL_Is_Public
 */
class URL_Is_Public_Unit_Test extends Unit_Testcase {

	/**
	 * @covers ::change_warning_message
	 */
	public function test_message_changed_for_own_private_uploads_instance(): void {

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
			)
		);

		$sut = new URL_Is_Public( $settings );

		$message = 'Unused';
		$url     = 'https://example.com/wp-content/logs';

		$result = $sut->change_warning_message( $message, 'plugin-slug_logger', 'plugin-slug_private', $url );

		$this->assertStringContainsString( 'Please update your webserver configuration', $result );
	}

	/**
	 * The filter fires for every private-uploads instance; other instances' messages must be unchanged.
	 *
	 * @covers ::change_warning_message
	 */
	public function test_message_unchanged_for_other_private_uploads_instances(): void {

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
			)
		);

		$sut = new URL_Is_Public( $settings );

		$message = 'Another plugin instance message';

		$result = $sut->change_warning_message( $message, 'other-plugin', 'other_plugin_private', 'https://example.com/wp-content/uploads/other' );

		$this->assertSame( $message, $result );
	}
}
