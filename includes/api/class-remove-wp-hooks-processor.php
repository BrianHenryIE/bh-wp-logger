<?php
/**
 * Strips `WP_Hook` objects from logged debug backtraces.
 *
 * The `debug_backtrace` added to the log context contains each frame's `object` — often a `WP_Hook`, whose
 * `callbacks` property lists every callback every plugin has registered on that hook, which makes the log
 * file size explode. This Monolog processor replaces each `WP_Hook` found in `context.debug_backtrace[].object`
 * with its class name before the record is written.
 *
 * Monolog's generic lever for oversized context is `NormalizerFormatter`'s `maxNormalizeDepth` /
 * `maxNormalizeItemCount`, but those truncate indiscriminately; this processor targets the known
 * offender without losing other context.
 *
 * @see \BrianHenryIE\WP_Logger\API\API::get_backtrace()
 * @see \Monolog\Formatter\NormalizerFormatter
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use WP_Hook;

/**
 * Monolog processor which replaces `WP_Hook` objects in the record's `debug_backtrace` context with their class name.
 *
 * NB: Backtrace frames reference the live `WP_Hook` objects in `$wp_filter`, so they must never be modified —
 * e.g. emptying `WP_Hook::$callbacks` would deregister every callback on that hook for the remainder of the
 * request. The frame entry is instead replaced in a copy of the context array.
 */
class Remove_WP_Hooks_Processor implements ProcessorInterface {

	/**
	 * Replace any `WP_Hook` object in the record's backtrace frames with its class name.
	 *
	 * @param LogRecord $record The Monolog log record, whose context may contain a `debug_backtrace`.
	 *
	 * @return LogRecord The record, with a modified copy of the context when a `WP_Hook` was present.
	 */
	public function __invoke( LogRecord $record ): LogRecord {

		if ( ! isset( $record->context['debug_backtrace'] ) || ! is_array( $record->context['debug_backtrace'] ) ) {
			return $record;
		}

		$context = $record->context;

		foreach ( $context['debug_backtrace'] as $frame_index => $frame ) {
			if ( is_array( $frame ) && isset( $frame['object'] ) && $frame['object'] instanceof WP_Hook ) {
				$context['debug_backtrace'][ $frame_index ]['object'] = WP_Hook::class;
			}
		}

		return $record->with( context: $context );
	}
}
