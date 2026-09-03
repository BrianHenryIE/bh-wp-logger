<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use Monolog\Level;
use Monolog\LogRecord;
use WP_Hook;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\Remove_WP_Hooks_Processor
 */
class Remove_WP_Hooks_Processor_Unit_Test extends Unit_Testcase {

	/**
	 * Create a log record with the given context, as {@see BH_WP_PSR_Logger::log()} would.
	 *
	 * @param array<string,mixed> $context The log context, e.g. containing a `debug_backtrace`.
	 */
	protected function new_log_record( array $context ): LogRecord {
		return new LogRecord(
			datetime: new \DateTimeImmutable(),
			channel: '',
			level: Level::Debug,
			message: '',
			context: $context,
		);
	}

	/**
	 * Create a real WP_Hook with one registered callback, as found in `$wp_filter`.
	 */
	protected function new_wp_hook(): WP_Hook {
		\WP_Mock::userFunction( '_wp_filter_build_unique_id' )->andReturn( '42' );

		$wp_hook = new WP_Hook();
		$wp_hook->add_filter( 'test_hook_name', 'callback', 10, 3 );

		return $wp_hook;
	}

	/**
	 * A `WP_Hook` in a backtrace frame's `object` should be replaced with its class name in the logged record.
	 *
	 * @see API::get_backtrace()
	 *
	 * @covers ::__invoke
	 */
	public function test_replaces_wp_hook_in_backtrace_frames(): void {

		$wp_hook = $this->new_wp_hook();

		$monolog_record = $this->new_log_record(
			array(
				'debug_backtrace' => array(
					array(
						'function' => 'apply_filters',
						'class'    => 'WP_Hook',
						'object'   => $wp_hook,
					),
				),
			),
		);

		$sut = new Remove_WP_Hooks_Processor();

		$result = $sut->__invoke( $monolog_record );

		$this->assertSame( WP_Hook::class, $result->context['debug_backtrace'][0]['object'] );
	}

	/**
	 * Regression test: the live `WP_Hook` (referenced from `$wp_filter`) must not be modified — emptying its
	 * callbacks would deregister every callback on that hook for the remainder of the request.
	 *
	 * @covers ::__invoke
	 */
	public function test_does_not_modify_the_live_wp_hook(): void {

		$wp_hook = $this->new_wp_hook();

		$monolog_record = $this->new_log_record(
			array(
				'debug_backtrace' => array(
					array( 'object' => $wp_hook ),
				),
			),
		);

		$sut = new Remove_WP_Hooks_Processor();

		$sut->__invoke( $monolog_record );

		$this->assertNotEmpty( $wp_hook->callbacks );
	}

	/**
	 * A record without a `debug_backtrace` context should be returned unchanged.
	 *
	 * @covers ::__invoke
	 */
	public function test_returns_record_unchanged_without_backtrace_context(): void {

		$monolog_record = $this->new_log_record( array( 'other' => 'context' ) );

		$sut = new Remove_WP_Hooks_Processor();

		$result = $sut->__invoke( $monolog_record );

		$this->assertSame( $monolog_record, $result );
	}

	/**
	 * Frames without an `object`, and frames whose `object` is not a `WP_Hook`, should pass through untouched.
	 *
	 * @covers ::__invoke
	 */
	public function test_ignores_frames_without_wp_hook(): void {

		$other_object = new \stdClass();

		$monolog_record = $this->new_log_record(
			array(
				'debug_backtrace' => array(
					array( 'function' => 'do_action' ),
					array(
						'function' => 'log',
						'object'   => $other_object,
					),
				),
			),
		);

		$sut = new Remove_WP_Hooks_Processor();

		$result = $sut->__invoke( $monolog_record );

		$this->assertArrayNotHasKey( 'object', $result->context['debug_backtrace'][0] );
		$this->assertSame( $other_object, $result->context['debug_backtrace'][1]['object'] );
	}
}
