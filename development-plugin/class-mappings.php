<?php

namespace BH_WP_Logger_Test_Plugin;

class Mappings {

	public function register_hooks(): void {
		add_action( 'init', array( $this, 'wp_plugin_paths' ) );
		add_filter( 'plugins_url', array( $this, 'plugins_url_fix' ), 10, 3 );
	}


	/**
	 * Fix for mapped directories. I.e. vendor is not under `wp-content/plugins/development-plugin`.
	 *
	 * @see plugin_basename()
	 */
	public function wp_plugin_paths(): void {
		/** @var array<string, string> $wp_plugin_paths {@see wordpress/wp-settings.php:501} */
		global $wp_plugin_paths;
		$plugin_path = '/var/www/html/wp-content/uploads/development-plugin/';
		// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited
		$wp_plugin_paths[ WP_PLUGIN_DIR . '/development-plugin/' ] = $plugin_path;
	}

	/**
	 * Partial fix for symlinks.
	 *
	 * @hooked plugins_url
	 *
	 * @param string $url  The complete URL to the plugins directory including scheme and path.
	 * @param string $_path Path relative to the URL to the plugins directory. Blank string if no path is specified.
	 * @param string $_plugin The plugin file path to be relative to. Blank string if no plugin is specified.
	 *
	 * @see plugins_url()
	 */
	public function plugins_url_fix( string $url, string $_path, string $_plugin ): string {
		$url = str_replace( 'wp-content/plugins/var/www/html/', '', $url );
		$url = str_replace( 'plugins/development-plugin/vendor', 'uploads/development-plugin/vendor', $url );
		$url = str_replace( 'plugins/development-plugin/includes', 'uploads/development-plugin/includes', $url );
		$url = str_replace( 'plugins/development-plugin/assets', 'uploads/development-plugin/assets', $url );
		return $url;
	}
}
