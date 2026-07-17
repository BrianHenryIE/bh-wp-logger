<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WPUnit_Testcase;
use Codeception\Stub\Expected;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\Test\TestLogger;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger
 */
class BH_WP_PSR_Logger_WPUnit_Test extends WPUnit_Testcase {

	/**
	 * When the log level is regular (notice, info), and the level of the error being logged is 'error', the backtrace
	 * should always be recorded.
	 */
	public function test_error_log_always_includes_backtrace(): void {

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new BH_WP_PSR_Logger( $setttings, $this->logger );

		$logger = new class() extends ColorLogger {
			/**
			 * Extend TestLogger to record context.
			 *
			 * @see TestLogger
			 */
			public $context = array();
			public function log( $level, $message, array $context = array() ): void {
				$this->context = $context;
				parent::log( $level, $message, $context );
			}
		};

		$sut->setLogger( $logger );

		$sut->error( 'error log message' );

		$this->assertArrayHasKey( 'debug_backtrace', $logger->context );
	}

	/**
	 * E.g. a library might often report "Trying to access array offset on value of type bool", which is a known
	 * upstream issue that does not need to be reported/recorded.
	 *
	 * @covers ::log
	 */
	public function test_cancel_log_filter(): void {

		$logger = $this->makeEmpty(
			LoggerInterface::class,
			array(
				'error' => Expected::never(),
			)
		);

		$setttings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new BH_WP_PSR_Logger( $setttings, $this->logger );

		$sut->setLogger( $logger );
		$context = json_decode( '{ "type": 2, "message": "Trying to access array offset on value of type bool", "file": "\/Users\/brianhenry\/Sites\/bh-wc-shipment-tracking-updates\/includes\/strauss\/jamiemadden\/licenseserver\/includes\/class-slswc-client.php", "line": 296, "debug_backtrace": [ { "file": "\/Users\/brianhenry\/Sites\/bh-wc-shipment-tracking-updates\/includes\/strauss\/brianhenryie\/bh-wp-logger\/includes\/PHP\/class-php-shutdown-handler.php", "lineNumber": 87, "arguments": [], "applicationFrame": true, "method": "handle" }, { "file": "unknown", "lineNumber": 0, "arguments": [], "applicationFrame": false, "method": "[top]", "class": null } ], "filters": [] }', true );

		/**
		 * Return null to cancel logging.
		 *
		 * @pararm array{level:string,message:string,context:array} $log_data
		 * @param Logger_Settings_Interface $settings
		 * @param BH_WP_PSR_Logger $bh_wp_psr_logger
		 */
		add_filter(
			'plugin-slug_bh_wp_logger_log',
			fn( array $log_data, $settings, $bh_wp_psr_logger ) => null,
			10,
			3
		);

		$sut->log( LogLevel::ERROR, 'Trying to access array offset on value of type bool', $context );
	}

	/**
	 * When the delegated logger itself triggers logging, `is_looping` should short-circuit the
	 * re-entrant call so the delegated logger is reached exactly once rather than recursing forever.
	 *
	 * @covers ::log
	 */
	public function test_reentrant_delegated_logger_does_not_loop(): void {

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new BH_WP_PSR_Logger( $settings, $this->logger );

		// A delegated logger that itself triggers logging through the SUT.
		$reentrant_logger      = new class() extends ColorLogger {
			public ?BH_WP_PSR_Logger $sut = null;
			public int $call_count        = 0;
			public function log( $level, $message, array $context = array() ): void {
				++$this->call_count;
				parent::log( $level, $message, $context );
				$this->sut->log( $level, 'reentrant message' );
			}
		};
		$reentrant_logger->sut = $sut;

		$sut->setLogger( $reentrant_logger );

		$sut->info( 'first message' );

		// The re-entrant call returns early because `is_looping` is still true,
		// so the delegated logger is reached exactly once (no infinite loop).
		$this->assertSame( 1, $reentrant_logger->call_count );
	}

	/**
	 * When a log is cancelled by the filter, `log()` returns early from inside the guarded block.
	 * The `try`/`finally` must still reset `is_looping` so subsequent logs are not silently dropped.
	 *
	 * @covers ::log
	 */
	public function test_is_looping_reset_after_cancelled_log(): void {

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'plugin-slug',
				'get_log_level'   => 'info',
			)
		);

		$sut = new BH_WP_PSR_Logger( $settings, $this->logger );

		add_filter(
			'plugin-slug_bh_wp_logger_log',
			fn( array $log_data, $settings, $bh_wp_psr_logger )
				=> 'cancel me' === $log_data['message'] ? null : $log_data,
			10,
			3
		);

		$sut->info( 'cancel me' ); // Cancelled: early return inside try; finally resets is_looping.
		$sut->info( 'log me' );    // Must still be delegated.

		// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$this->assertCount( 1, $this->logger->recordsByLevel['info'] );
	}
}
