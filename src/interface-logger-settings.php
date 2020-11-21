<?php

namespace BrianHenryIE\WP_Logger;

interface Logger_Settings_Interface {

	public function get_plugin_slug(): string;

	public function get_log_level(): string;
}
