<?php
/**
 * Functions for determining the plugin a file is in.
 *
 * Works OK with symlinks, except when there's symlinks in symlinks.
 */

namespace BrianHenryIE\WP_Logger\API;

class Plugin_Helper {

	public function discover_plugin_data() {

		$directory = $this->discover_plugin_basename();

		$plugin_data = $this->get_plugin_data_from_directory( $directory );

		return $plugin_data;
	}

	/**
	 * Given a slug, searches the get_plugins() array for the plugin details.
	 *
	 * TODO: TextDomain is not an essential part of a miinimum viable WordPress plugin (only `Plugin Name`).
	 *
	 * @param string $slug
	 *
	 * @return array|null
	 */
	public function get_plugin_data_from_slug( $slug ): ?array {

		$plugins = get_plugins();

		$plugin_data = array_filter(
			$plugins,
			function( $plugin ) use ( $slug ) {
				return ( $plugin['TextDomain'] === $slug );
			}
		);

		if ( count( $plugin_data ) === 1 ) {
			$plugin             = reset( $plugin_data );
			$basename           = key( $plugin_data );
			$plugin['basename'] = $basename;
			return $plugin;
		}

		return null;
	}

	/**
	 * Find the first half of the plugin basename... i.e. the directory it appears in under WP_PLUGIN_DIR.
	 *
	 * Find the plugin directory from the filepath.
	 * Should work with one level of symlinks.
	 * Does not work with symlinks inside symlinks.
	 *
	 * @return ?string
	 */
	public function discover_plugin_basename( $dir = null ): ?string {

		$dir = $dir ?? __DIR__;

		// If the $dir has a file at the end, remove it.
		// https://www.phpliveregex.com/p/yGC
		if ( 1 === preg_match( '/(.*)\/[^\/]*$/', $dir, $output_array ) ) {
			$dir = $output_array[1];
		}

		// $plugins_dir_entry = str_replace( WP_PLUGIN_DIR, '', __DIR__ );

		// Check for a standard WordPress install...
		// __DIR__ has no trailing slash.
		$capture_first_string_after_slash_in_plugins_dir = '/' . preg_quote( WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)', DIRECTORY_SEPARATOR ) . '/';
		$capture_first_string_after_slash_in_plugins_dir = '/' . str_replace( DIRECTORY_SEPARATOR, '\\' . DIRECTORY_SEPARATOR, WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . '([^' . DIRECTORY_SEPARATOR . ']*)' ) . '/';
		if ( 1 === preg_match( $capture_first_string_after_slash_in_plugins_dir, $dir, $output_array ) ) {
			$plugin_directory = $output_array[1];

			$plugin_data = $this->get_plugin_data_from_slug( $plugin_directory );

			if ( ! is_null( $plugin_data ) ) {
				return $this->get_plugin_data_from_slug( $plugin_directory )['basename'];
			}
		} else {

			// If we're in a live plugin in another directory, it's probably symlinked inside WP_PLUGIN_DIR.

			// Find the filename.
			// class-plugin-helper.php
			preg_match( '/\/([^\/]*$)/', __FILE__, $output_array );
			$filename = $output_array[1];

			// List the WP_PLUGIN_DIR directory, check for symlinks.
			if ( $opendirectory = opendir( WP_PLUGIN_DIR ) ) {
				while ( ( $plugins_dir_entry = readdir( $opendirectory ) ) !== false ) {

					if ( is_link( WP_PLUGIN_DIR . "/{$plugins_dir_entry}" ) ) {

						// __DIR__ is the true path on the filesystem.
						$dir_parts = explode( DIRECTORY_SEPARATOR, $dir );

						$plugin_internal_path = '';

						// Keep prefixing with additional levels of directory
						foreach ( array_reverse( $dir_parts ) as $parent_dir ) {

							$plugin_internal_path = "{$parent_dir}/" . $plugin_internal_path;

							$filepath = WP_PLUGIN_DIR . "/{$plugins_dir_entry}/{$plugin_internal_path}$filename";

							if ( file_exists( $filepath ) ) {
								closedir( $opendirectory );

								return $this->get_plugin_data_from_slug( $plugins_dir_entry )['basename'];
							}
						}
					}
				}
				closedir( $opendirectory );
			}
		}

		$dir_parts = explode( DIRECTORY_SEPARATOR, $dir );
		// Assuming here the logging library is in a subdir or two of the plugin itself.
		while ( array_pop( $dir_parts ) ) {
			// List the WP_PLUGIN_DIR directory, check for symlinks.
			$current_dir = implode( DIRECTORY_SEPARATOR, $dir_parts );
			if ( $opendirectory = opendir( $current_dir ) ) {
				while ( ( $file = readdir( $opendirectory ) ) !== false ) {
					if ( is_file( $current_dir . DIRECTORY_SEPARATOR . $file ) ) {

						if ( '.php' === substr( $file, -4 ) ) {
							$plugin_data = get_plugin_data( $current_dir . DIRECTORY_SEPARATOR . $file, false, false );

							if ( isset( $plugin_data['Name'] ) && ! empty( $plugin_data['Name'] ) ) {
								$plugin_slug = $plugin_data['TextDomain'];

								$get_plugin_data_from_slug = $this->get_plugin_data_from_slug( $plugin_slug );
								if ( ! is_null( $get_plugin_data_from_slug ) && isset( $get_plugin_data_from_slug['basename'] ) ) {
									return $get_plugin_data_from_slug['basename'];
								} else {
									return null;
								}
							}
						}
					}
				}
			}
		}

		return null;
	}

	/**
	 * @param string $directory The WP_PLUGIN_DIR subdirectory for the plugin.
	 *
	 * @return string
	 */
	public function get_plugin_data_from_directory( $directory ): array {

		$plugins = get_plugins();

		foreach ( $plugins as $plugin_file => $plugin_data ) {
			if ( explode( '/', $plugin_file )[0] === $directory ) {
				return $plugin_data;
			}
		}

		// TODO: something else.
		return array();
	}

}
