<?php
/**
 * Default function implementations for classes implementing `Logger_Settings_Interface`.
 *
 * These functions infer the current plugin's basename, name, log level.
 *
 * It is faster to provide your own implementation of each function.
 *
 * A Settings class should `implements Logger_Settings_Interface` and `use Logger_Settings_Trait`
 * then override all the functions. This allows more functions to be added to the interface in future
 * library updates without requiring projects to implement the new functions, i.e. it provides
 * forward-compatibility.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\WP_Includes\CLI;
use Exception;
use Psr\Log\LogLevel;

/**
 * Default function implementations for Logger_Settings_Interface.
 *
 * @see Logger_Settings_Interface
 */
trait Logger_Settings_Trait {

	/**
	 * The log level to use.
	 *
	 * Default is Info.
	 * Looks for saved value in `get_option( 'my-plugin-slug_log_level' ).
	 * Returns `none` when the plugin basename cannot be determined.
	 *
	 * @see LogLevel
	 */
	public function get_log_level(): string {
		try {
			return get_option( $this->get_plugin_slug() . '_log_level', LogLevel::INFO );
		} catch ( \Exception $exception ) {
			return 'none';
		}
	}

	/**
	 * The plugin friendly name to use in UIs.
	 *
	 * @throws Exception When the basename cannot be determined.
	 */
	public function get_plugin_name(): string {
		$plugin_data = get_plugin_data( WP_PLUGIN_DIR . '/' . $this->get_plugin_basename() );
		return $plugin_data['Name'];
	}

	/**
	 * The plugin slug. I.e. the plugin directory name. Used in URLs and wp_options.
	 *
	 * @throws Exception When the basename cannot be determined.
	 */
	public function get_plugin_slug(): string {
		return explode( '/', $this->get_plugin_basename() )[0];
	}

	/**
	 * The plugin basename. Used to add the Logs link on `plugins.php`.
	 *
	 * @see https://core.trac.wordpress.org/ticket/42670
	 *
	 * @throws Exception When it cannot be determined. I.e. a symlink inside a symlink.
	 */
	public function get_plugin_basename(): string {

		// TODO: The following might work but there are known issues around symlinks that need to be tested and handled correctly.
		// @see  https://core.trac.wordpress.org/ticket/42670

		$wp_plugin_basename = plugin_basename( __DIR__ );

		$plugin_filename = get_plugins( explode( '/', $wp_plugin_basename )[0] );

		return array_key_first( $plugin_filename );

		throw new Exception( 'Plugin installed in an unusual directory.' );
	}

	/**
	 * Default CLI commands to use the plugin slug as the base for commands.
	 *
	 * @see CLI
	 */
	public function get_cli_base(): ?string {
		return $this->get_plugin_slug();
	}
}
