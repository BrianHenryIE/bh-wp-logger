<?php
/**
 * The result of parsing a log file: its entries (possibly capped) and summary counts.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use DateTime;

/**
 * Value object returned by {@see API::parse_log_file()}.
 */
class Parsed_Log_File {

	/**
	 * Constructor.
	 *
	 * @param array<array{time:string,datetime:DateTime|null,level:string,message:string,context:string|null}> $entries The parsed log entries, oldest first. When a max-entries limit was set, only the most recent entries are included.
	 * @param int                                                                                              $total_entries_count The number of entries in the whole file, including any omitted from $entries.
	 * @param array<string,int>                                                                                $level_counts        The number of entries in the whole file at each log level, keyed by lowercase level name.
	 */
	public function __construct(
		public readonly array $entries,
		public readonly int $total_entries_count,
		public readonly array $level_counts,
	) {
	}

	/**
	 * Were entries omitted due to the max-entries limit?
	 */
	public function is_truncated(): bool {
		return count( $this->entries ) < $this->total_entries_count;
	}
}
