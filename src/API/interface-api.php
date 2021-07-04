<?php

namespace BrianHenryIE\WP_Logger\API;

interface API_Interface {

	/**
	 * Get the URL for the settings page Logs link.
	 *
	 * @return string
	 */
	public function get_log_url(): string;

	/**
	 * Delete old log files so they don't build up.
	 * Presumably called from cron.
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

	public function get_common_context(): array;


	public function determine_plugin_slug_from_backtrace(): ?string;

	public function get_backtrace(): array;
}
