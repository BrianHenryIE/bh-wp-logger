<?php
/**
 * For the Settings class to implement.
 */

namespace BrianHenryIE\WP_Logger\API;

trait Logger_Settings_Trait {

	protected string $log_level;

	protected string $plugin_name;

	protected string $plugin_slug;

	protected string $plugin_basename;

	public function get_log_level(): string {
		return $this->log_level;
	}

	public function get_plugin_name(): string {
		return $this->get_plugin_name();
	}

	public function get_plugin_slug(): string {
		return $this->get_plugin_slug();
	}

	public function get_plugin_basename(): string {
		return $this->get_plugin_basename();
	}

}
