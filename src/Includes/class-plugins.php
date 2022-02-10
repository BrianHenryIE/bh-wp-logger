<?php
/**
 * Functions for determining the plugin a file is in.
 *
 * Works OK with symlinks, except when there's symlinks in symlinks.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Includes;

class Plugins {

	/**
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
	 * Find the plugin directory from the filepath.
	 * Should work with one level of symlinks.
	 * Does not work with symlinks inside symlinks.
	 *
	 * @return ?string
	 */
	public function discover_plugin_relative_directory( ?string $dir = null ): ?string {

		// __DIR_ is the directory this file is in. (i.e. NOT the calling directory).
		$dir = $dir ?? __DIR__;

		/**
		 * If the $dir has a file at the end, remove it.
		 *
		 * @see https://www.phpliveregex.com/p/yGC
		 */
		if ( is_file( $dir ) && 1 === preg_match( '/(.*)\/[^\/]*$/', $dir, $output_array ) ) {
			$dir = $output_array[1];
		}

		// Check for a standard WordPress install...
		$capture_first_string_after_slash_in_plugins_dir = '~' . WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' . '~';

		if ( 1 === preg_match( $capture_first_string_after_slash_in_plugins_dir, $dir, $output_array ) ) {
			$plugin_directory = $output_array[1];

			return $plugin_directory;

		} else {

			// If we're in a live plugin in another directory, it's probably symlinked inside WP_PLUGIN_DIR.

			// Find the filename.
			// class-plugins.php
			preg_match( '/\/([^\/]*$)/', __FILE__, $output_array );
			$filename = $output_array[1];

			// List the WP_PLUGIN_DIR directory, check for symlinks.
			if ( $opendirectory = opendir( WP_PLUGIN_DIR ) ) {
				while ( ( $plugins_dir_entry = readdir( $opendirectory ) ) !== false ) {

					if ( is_link( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . $plugins_dir_entry ) ) {

						// __DIR__ is the true path on the filesystem.
						$dir_parts = explode( DIRECTORY_SEPARATOR, $dir );

						$plugin_internal_path = '';

						// Keep prefixing with additional levels of directory.
						foreach ( array_reverse( $dir_parts ) as $parent_dir ) {

							$plugin_internal_path = $parent_dir . DIRECTORY_SEPARATOR . $plugin_internal_path;

							$filepath = WP_PLUGIN_DIR . "/{$plugins_dir_entry}/{$plugin_internal_path}$filename";

							// This is the filepath with the symlink in it.
							if ( file_exists( $filepath ) ) {
								closedir( $opendirectory );

								return $plugins_dir_entry;
							}
						}
					}
				}
				closedir( $opendirectory );
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
	 *
	 * @param string $slug_or_directory The plugin slug aka the WP_PLUGIN_DIR subdirectory for the plugin.
	 *
	 * @return ?array<string, mixed>
	 */
	public function get_plugin_data_from_slug( string $slug_or_directory ): ?array {

		$plugins = get_plugins();

		foreach ( $plugins as $plugin_basename => $plugin_data ) {
			if ( 0 === strpos( $plugin_basename, $slug_or_directory ) ) {
				$plugin_data['basename'] = $plugin_basename;
				return $plugin_data;
			}
		}

		return null;
	}

}
