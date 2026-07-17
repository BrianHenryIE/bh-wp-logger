<?php

namespace BrianHenryIE\WP_Logger\PHP;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler
 */
class PHP_Error_Handler_Unit_Test extends Unit_Testcase {

	/**
	 * @covers ::errno_to_psr3
	 * @dataProvider error_levels
	 *
	 * @param int    $from A PHP error const (int).
	 * @param string $to A PSR LogLevel.
	 */
	public function test_php_error_level_to_psr_level( $from, $to ): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = $this->logger;

		$sut = new PHP_Error_Handler( $api, $settings, $logger );

		$method = new \ReflectionMethod( PHP_Error_Handler::class, 'errno_to_psr3' );
		PHP_VERSION_ID < 80100 && $method->setAccessible( true );

		$result = $method->invoke( $sut, $from );

		$this->assertEquals( $to, $result );
	}

	public function error_levels(): array {
		return array(
			array( E_ERROR, LogLevel::ERROR ),
			array( E_DEPRECATED, LogLevel::NOTICE ),
		);
	}

	/**
	 * @covers ::errno_to_psr3
	 */
	public function test_php_error_level_to_psr_level_unknown(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = $this->logger;

		$sut = new PHP_Error_Handler( $api, $settings, $logger );

		$method = new \ReflectionMethod( PHP_Error_Handler::class, 'errno_to_psr3' );
		PHP_VERSION_ID < 80100 && $method->setAccessible( true );

		$result = $method->invoke( $sut, 918273645 );

		$this->assertEquals( LogLevel::ERROR, $result );
	}
}
