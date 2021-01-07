<?php
/**
 * To provide defaults.
 */

namespace BrianHenryIE\WP_Logger;

use Psr\Log\LogLevel;

class Logger_Settings implements Logger_Settings_Interface {

	/** @var string  */
	protected $plugin_slug;

	public function __construct( $plugin_slug = null ) {

		$this->plugin_slug = $plugin_slug ?? $this->discover_plugin_slug();

	}

	/**
	 *
	 * Find the plugin slug from the filepath.
	 * This doesn't work well with symlinks.
	 *
	 * @return string
	 */
	protected function discover_plugin_slug() {

		// $plugins_dir_entry = str_replace( WP_PLUGIN_DIR, '', __DIR__ );

		// Check for a standard WordPress install...
		// __DIR__ has no trailing slash.
		$capture_last_string_after_slash_in_plugins_dir = '/' . preg_quote( WP_PLUGIN_DIR . '/([^/]*)', '/' ) . '/';
		if ( 1 === preg_match( $capture_last_string_after_slash_in_plugins_dir, __DIR__, $output_array ) ) {
			$plugin_slug = $output_array[1];
			return $plugin_slug;
		} else {

			// If we're in a live plugin in another directory, it's probably symlinked inside WP_PLUGIN_DIR.

			// Find the filename.

			preg_match( '/\/([^\/]*$)/', __FILE__, $output_array );
			$filename = $output_array[1];

			// List the WP_PLUGIN_DIR directory, check for symlinks.
			if ( $opendirectory = opendir( WP_PLUGIN_DIR ) ) {
				while ( ( $plugins_dir_entry = readdir( $opendirectory ) ) !== false ) {

					if ( is_link( WP_PLUGIN_DIR . "/{$plugins_dir_entry}" ) ) {

						// __DIR__ is the true path on the filesystem.
						$dir_parts   = explode( DIRECTORY_SEPARATOR, __DIR__ );
						$dir_parts[] = '';

						$plugin_internal_path = '';

						// Keep prefixing with additional levels of directory
						foreach ( array_reverse( $dir_parts ) as $parent_dir ) {

							$plugin_internal_path = "{$parent_dir}/" . $plugin_internal_path;

							if ( file_exists( WP_PLUGIN_DIR . "/{$plugins_dir_entry}/{$plugin_internal_path}$filename" ) ) {
								return $plugins_dir_entry;
							}
						}
					}
				}
				closedir( $opendirectory );
			}
		}
	}

	public function get_plugin_slug(): string {
		return $this->plugin_slug;
	}

	public function get_log_level(): string {
		return LogLevel::NOTICE;
	}
}
