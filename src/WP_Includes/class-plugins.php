<?php
/**
 * Functions for determining the plugin a file is in.
 *
 * Used when logger is instantiated with `Logger::instance()`.
 * Not used when settings are supplied `Logger::instance( $logger_settings_instance )`.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

/**
 * Compare the current path with realpaths recorded by WordPress in $wp_plugin_paths, then return the plugin data
 * for that plugin.
 *
 * @see wp_register_plugin_realpath()
 * @see get_plugins()
 */
class Plugins {

	/**
	 * Attempt to return the get_plugins() data for plugin the current file (class-plugins.php) is contained in.
	 *
	 * @used-by Logger_Settings
	 *
	 * @return ?array<string, mixed>
	 */
	public function discover_plugin_data(): ?array {

		$directory = $this->discover_plugin_relative_directory();

		if ( is_null( $directory ) ) {
			return null;
		}

		$plugin_data = $this->get_plugin_data_from_slug( $directory );

		return $plugin_data;
	}

	/**
	 * Find the plugin relative directory... i.e. the directory it appears in under WP_PLUGIN_DIR.
	 *
	 * Find the plugin directory from the filepath by using WordPress's $wp_plugin_paths.
	 *
	 * @param ?string $dir An absolute directory path, presumed to be a plugin directory or subdirectory.
	 *
	 * @return ?string
	 */
	public function discover_plugin_relative_directory( ?string $dir = null ): ?string {

		global $wp_plugin_paths;

		// __DIR_ is the directory this file is in. (i.e. NOT the calling directory).
		$dir = $dir ?? __DIR__;

		arsort( $wp_plugin_paths );

		$regex_pattern_capture_first_string_after_slash_in_plugins_dir = '~' . WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)~';

		foreach ( $wp_plugin_paths as $basepath => $realpath ) {
			if ( 0 === strpos( $dir, $realpath ) ) {
				if ( 1 === preg_match( $regex_pattern_capture_first_string_after_slash_in_plugins_dir, $basepath, $output_array ) ) {
					$plugin_directory = $output_array[1];
					return $plugin_directory;
				}
			}
		}

		return null;

	}

	/**
	 * Given a slug, searches the get_plugins() array for the plugin details.
	 *
	 * TODO: How does this behave if the plugin is in the root WP_PLUGIN_DIR without its own folder? It might work ok!
	 *
	 * @used-by Logger_Settings
	 * @see get_plugins()
	 *
	 * @param string $slug_or_directory The plugin slug aka the WP_PLUGIN_DIR subdirectory for the plugin.
	 *
	 * @return ?array<string, mixed>
	 */
	public function get_plugin_data_from_slug( string $slug_or_directory ): ?array {

		require_once ABSPATH . '/wp-admin/includes/plugin.php';

		$plugins = get_plugins();

		foreach ( $plugins as $plugin_basename => $plugin_data ) {
			if ( 0 === strpos( $plugin_basename, "{$slug_or_directory}/" ) ) {
				$plugin_data['basename'] = $plugin_basename;
				return $plugin_data;
			}
		}

		return null;
	}

}
