<?php

namespace BrianHenryIE\WP_Logger\WooCommerce;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WooCommerce\Log_Handler
 */
class Log_Handler_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::add_context_to_logs
	 */
	public function test_context_is_added_for_errors(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_log_level'   => 'info',
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = new ColorLogger();

		$sut = new Log_Handler( $api, $settings, $logger );

		$entry          = 'Log message';
		$log_data_array = array(
			'timestamp' => time(),
			'level'     => 'error',
			'message'   => 'Log message',
			'context'   => array(
				'source'       => 'test-plugin',
				'more_context' => 'data',
			),
		);

		$result = $sut->add_context_to_logs( $entry, $log_data_array );

		$this->assertStringContainsString( 'more_context', $result );

	}
}
