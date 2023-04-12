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
		return get_plugins( $this->get_plugin_basename() )['Name'];
	}

	/**
	 * The plugin slug. I.e. the plugin directory name. Used in URLs and wp_options.
	 *
	 * @throws Exception When the basename cannot be determined.
	 */
	public function get_plugin_slug(): string {
		return explode( '/', $this->get_plugin_basename() )[0];
	}

}
