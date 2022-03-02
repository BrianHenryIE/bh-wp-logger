<?php
/**
 * The main functions in the logger.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\Admin\Logs_List_Table;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\WP_Includes\Cron;
use DateTime;
use DateTimeInterface;
use Spatie\Backtrace\Frame;
use stdClass;

interface API_Interface {

	/**
	 * Get a list of log files.
	 *
	 * @used-by Logs_Page
	 *
	 * @param string $date Optional specific dated file to delete.
	 *
	 * @return array<string, string> Y-m-d index with path as the value.
	 */
	public function get_log_files( ?string $date = null ): array;

	/**
	 * Get the URL for the settings page Logs link.
	 *
	 * @param ?string $date A date string Y-m-d or null to get the most recent.
	 *
	 * @return string
	 */
	public function get_log_url( ?string $date = null ): string;

	/**
	 * Delete the log file for a specific date.
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
	 * Delete old log files, so they don't build up.
	 * Presumably called from cron.
	 *
	 * @used-by Cron
	 */
	public function delete_old_logs(): void;

	/**
	 * Set the context that will be added to all log messages.
	 * e.g. request_id, user_id.
	 *
	 * This will be cumulative, but reset on every request (every new page load).
	 *
	 * @param string $key The entry's key to save. This will overwrite any existing entry.
	 * @param mixed  $value The new value.
	 */
	public function set_common_context( string $key, $value ): void;

	/**
	 * Get context to add to each log message.
	 * e.g. session, filters used, IP.
	 *
	 * @return array<string, mixed>
	 */
	public function get_common_context(): array;

	/**
	 * Get the debug backtrace leading up to the point the message was logged.
	 *
	 * @param ?int $steps The number of entries to return.
	 *
	 * @return Frame[]
	 */
	public function get_backtrace( ?int $steps = null ): array;

	/**
	 * Checks the current backtrace for any reference to the current plugin.
	 *
	 * @return bool
	 */
	public function is_backtrace_contains_plugin(): bool;

	/**
	 * Given a filepath (from a backtrace or error message) determine is it from this plugin.
	 *
	 * @param string $filepath Filesystem filepath to check.
	 *
	 * @return bool
	 */
	public function is_file_from_plugin( string $filepath ): bool;

	/**
	 * Reads the most recent log file, if any, and returns the time of the most recent log (or null).
	 *
	 * @used-by Plugins_Page::add_logs_action_link()
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_last_log_time(): ?DateTimeInterface;

	/**
	 * Find the time the logs were last viewed.
	 *
	 * @used-by Plugins_Page::add_logs_action_link()
	 *
	 * @return ?DateTimeInterface
	 */
	public function get_last_logs_view_time(): ?DateTimeInterface;

	/**
	 * Record the last time the logs were viewed in order to determine if admin notices should or should not be displayed.
	 * i.e. "mark read".
	 *
	 * @used-by Logs_Page
	 *
	 * @param ?DateTimeInterface $date_time A time to set, defaults to "now".
	 */
	public function set_last_logs_view_time( ?DateTimeInterface $date_time = null ): void;

	/**
	 * Given the path to a log text file, parse its lines into an array of individual log entries parsed into
	 * time, level, message, and context.
	 *
	 * @used-by Logs_List_Table::get_data()
	 *
	 * @param string $filepath The full path to the log file.
	 *
	 * @return array<array{time:string,datetime:DateTime|null,level:string,message:string,context:stdClass|null}>
	 */
	public function parse_log( string $filepath ): array;

}
