<?php
/**
 * Strips `WP_Hook` objects from logged backtraces.
 *
 * Backtraces added to the log context can contain `WP_Hook` objects, whose `callbacks` property lists every
 * callback every plugin has registered on that hook, which makes the log file size explode. They appear in
 * two places:
 *
 * * `context.debug_backtrace[].object` — `debug_backtrace()` includes each frame's live object.
 * * `context.exception.backtrace[].args` — `Throwable::getTrace()` frames omit `object` but include `args`,
 *   which can carry a `WP_Hook` (e.g. an exception thrown inside a hook callback).
 *
 * This Monolog processor replaces each `WP_Hook` found in either with its class name before the record is written.
 *
 * Monolog's generic lever for oversized context is `NormalizerFormatter`'s `maxNormalizeDepth` /
 * `maxNormalizeItemCount`, but those truncate indiscriminately; this processor targets the known
 * offender without losing other context.
 *
 * @see \BrianHenryIE\WP_Logger\API\API::get_backtrace()
 * @see \BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger::log()
 * @see \Monolog\Formatter\NormalizerFormatter
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use Monolog\LogRecord;
use Monolog\Processor\ProcessorInterface;
use WP_Hook;

/**
 * Monolog processor which replaces `WP_Hook` objects in the record's backtrace context with their class name.
 *
 * NB: Backtrace frames reference the live `WP_Hook` objects in `$wp_filter`, so they must never be modified —
 * e.g. emptying `WP_Hook::$callbacks` would deregister every callback on that hook for the remainder of the
 * request. The frame entry is instead replaced in a copy of the context array.
 */
class Remove_WP_Hooks_Processor implements ProcessorInterface {

	/**
	 * Replace any `WP_Hook` object in the record's backtrace frames with its class name.
	 *
	 * Checks both the `debug_backtrace` context and the backtrace recorded from a logged exception.
	 *
	 * @param LogRecord $record The Monolog log record, whose context may contain backtraces.
	 *
	 * @return LogRecord The record, with a modified copy of the context when a `WP_Hook` was present.
	 */
	public function __invoke( LogRecord $record ): LogRecord {

		$context = $record->context;
		$changed = false;

		if ( isset( $context['debug_backtrace'] ) && is_array( $context['debug_backtrace'] ) ) {
			$context['debug_backtrace'] = $this->remove_wp_hooks_from_frames( $context['debug_backtrace'], $changed );
		}

		if ( isset( $context['exception']['backtrace'] ) && is_array( $context['exception']['backtrace'] ) ) {
			$context['exception']['backtrace'] = $this->remove_wp_hooks_from_frames( $context['exception']['backtrace'], $changed );
		}

		return $changed ? $record->with( context: $context ) : $record;
	}

	/**
	 * Replace `WP_Hook` objects found in each frame's `object` or among its `args` with the class name string.
	 *
	 * Only those two locations are checked — deeper structures inside `args` are intentionally not walked,
	 * since a `WP_Hook` nested further than a direct argument has not been observed in practice.
	 *
	 * @param array<int|string, mixed> $frames Backtrace-shaped frames, from `debug_backtrace()` or `Throwable::getTrace()`.
	 * @param bool                     $changed Set to true when a `WP_Hook` was replaced; otherwise left unchanged.
	 *
	 * @return array<int|string, mixed> The frames, with any `WP_Hook` replaced by its class name.
	 */
	protected function remove_wp_hooks_from_frames( array $frames, bool &$changed ): array {

		foreach ( $frames as $frame_index => $frame ) {
			if ( ! is_array( $frame ) ) {
				continue;
			}

			if ( isset( $frame['object'] ) && $frame['object'] instanceof WP_Hook ) {
				$frames[ $frame_index ]['object'] = WP_Hook::class;
				$changed                          = true;
			}

			if ( isset( $frame['args'] ) && is_array( $frame['args'] ) ) {
				foreach ( $frame['args'] as $arg_index => $arg ) {
					if ( $arg instanceof WP_Hook ) {
						$frames[ $frame_index ]['args'][ $arg_index ] = WP_Hook::class;

						$changed = true;
					}
				}
			}
		}

		return $frames;
	}
}
