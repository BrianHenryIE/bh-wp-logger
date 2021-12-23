<?php
/**
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Includes\Cron;
use Spatie\Backtrace\Frame;

interface API_Interface {

	/**
	 * @return array<string, string> Y-m-d index with path as the value.
	 */
	public function get_log_files( ?string $date = null ): array;

	/**
	 * Get the URL for the settings page Logs link.
	 *
	 * @return string
	 */
	public function get_log_url( ?string $date = null ): string;

	/**
	 * Delete a specific date's log file.
	 *
	 * @param string $ymd_date The date formatted Y-m-d, e.g. 2021-09-27.
	 *
	 * @used-by Logs_Page
	 *
	 * @return array{success: bool, message: ?string}
	 */
	public function delete_log( string $ymd_date ): array;

	/**
	 * Delete all logs for this plugin.
	 *
	 * @used-by Logs_Page
	 *
	 * @return array{success: bool, deleted_files: array, failed_to_delete: ?array}
	 */
	public function delete_all_logs(): array;

	/**
	 * Delete old log files so they don't build up.
	 * Presumably called from cron.
	 *
	 * @used-by Cron
	 */
	public function delete_old_logs(): void;

	/**
	 * Set the context that will be added to all log messages.
	 * e.g. request_id, user_id.
	 *
	 * @param string $key
	 * @param mixed  $value
	 */
	public function set_common_context( $key, $value ): void;

	/**
	 * @return array<string, mixed>
	 */
	public function get_common_context(): array;

	/**
	 * @return string|null
	 */
	public function determine_plugin_slug_from_backtrace(): ?string;

	/**
	 * @return Frame[]
	 */
	public function get_backtrace(): array;

	/**
	 * Checks the current backtrace for any reference to the current plugin.
	 *
	 * @return bool
	 */
	public function is_backtrace_contains_plugin(): bool;

	public function is_file_from_plugin( string $filepath ): bool;

}
