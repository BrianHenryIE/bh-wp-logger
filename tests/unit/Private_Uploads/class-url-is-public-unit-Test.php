<?php

namespace BrianHenryIE\WP_Logger\Private_Uploads;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Private_Uploads\URL_Is_Public
 */
class URL_Is_Public_Unit_Test extends \Codeception\Test\Unit {

	/**
	 * @covers ::change_warning_message
	 */
	public function test_message_changed(): void {

		$sut = new URL_Is_Public();

		$message = 'Unused';
		$url     = 'https://example.com/wp-content/logs';

		$result = $sut->change_warning_message( $message, $url );

		$this->assertStringContainsString( 'Please update your webserver configuration', $result );
	}
}
