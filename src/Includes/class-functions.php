<?php
/**
 * Manipulate the behaviour of WordPress functions.
 *
 * Use transients to log errors only once per day, with more detail than before.
 *
 * Doing this here means we can set the log level per plugin without relying on WP_DEBUG being true.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WP_GetEnhanced_Shop
 * @subpackage BH_WP_GetEnhanced_Shop/includes
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;

/**
 *
 * @since      1.0.0
 * @package    BH_WP_GetEnhanced_Shop
 * @subpackage BH_WP_GetEnhanced_Shop/includes
 * @author     Brian Henry <BrianHenryIE@gmail.com>
 */
class Functions {

	/** @var Logger_Settings_Interface */
	protected $settings;

	/** @var API_Interface */
	protected $api;

	/** @var LoggerInterface */
	protected $logger;

	/**
	 * Since this class can be instantiated many times (in A-Plugin-Logger, it is instantiated for every plugin), the
	 * debug backtraces would run many times unnecessarily.
	 *
	 * @var string[] The slug found for the backtrace.
	 */
	protected static $debug_backtrace_cache = array();

	/**
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param LoggerInterface           $logger
	 */
	public function __construct( $api, $settings, $logger = null ) {

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

	/**
	 * Limits logging deprecated functions to once per day.
	 * Logs more detail than usual.
	 *
	 * @hooked deprecated_function_run
	 *
	 * @param string  $function_name
	 * @param string  $replacement_function
	 * @param $version
	 *
	 * @see _deprecated_function()
	 */
	public function log_deprecated_functions_only_once_per_day( $function, $replacement_function, $version ) {

		$cache_key = md5( __FUNCTION__ . implode( '', func_get_args() ) );

		if ( isset( self::$debug_backtrace_cache[ $cache_key ] ) ) {
			$plugin_slug = self::$debug_backtrace_cache[ $cache_key ];
		} else {
			$plugin_slug                               = $this->api->determine_plugin_slug_from_backtrace();
			self::$debug_backtrace_cache[ $cache_key ] = $plugin_slug;
		}

		if ( $this->settings->get_plugin_slug() != $plugin_slug ) {
			return;
		}

		$transient_name = "log_deprecated_function_{$function}_{$plugin_slug}";

		$message = "Deprecated function {$function}";

		$this->log_and_filter_false( $message, $transient_name, 'deprecated_function_trigger_error' );

	}

	/**
	 * Limits logging deprecated functions to once per day.
	 * Logs more detail than usual.
	 *
	 * @hooked deprecated_argument_run
	 *
	 * @param string  $function_name
	 * @param string  $message
	 * @param $version
	 *
	 * @see _deprecated_argument()
	 */
	public function log_deprecated_arguments_only_once_per_day( $function, $message, $version ) {

		$cache_key = md5( __FUNCTION__ . implode( '', func_get_args() ) );

		if ( isset( self::$debug_backtrace_cache[ $cache_key ] ) ) {
			$plugin_slug = self::$debug_backtrace_cache[ $cache_key ];
		} else {
			$plugin_slug                               = $this->api->determine_plugin_slug_from_backtrace();
			self::$debug_backtrace_cache[ $cache_key ] = $plugin_slug;
		}

		if ( $this->settings->get_plugin_slug() != $plugin_slug ) {
			return;
		}

		$transient_name = "log_deprecated_argument_{$function}_{$plugin_slug}";

		$this->log_and_filter_false( $message, $transient_name, 'deprecated_argument_trigger_error' );

	}

	/**
	 * @hooked doing_it_wrong_run
	 * @see _doing_it_wrong()
	 *
	 * @hooked doing_it_wrong_run
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 */
	public function log_doing_it_wrong_only_once_per_day( $function, $message, $version ) {

		$cache_key = md5( __FUNCTION__ . implode( '', func_get_args() ) );

		if ( isset( self::$debug_backtrace_cache[ $cache_key ] ) ) {
			$plugin_slug = self::$debug_backtrace_cache[ $cache_key ];
		} else {
			$plugin_slug                               = $this->api->determine_plugin_slug_from_backtrace();
			self::$debug_backtrace_cache[ $cache_key ] = $plugin_slug;
		}

		if ( $this->settings->get_plugin_slug() != $plugin_slug ) {
			return;
		}

		$transient_name = "log_doing_it_wrong_run_{$function}_{$plugin_slug}";

		$this->log_and_filter_false( $message, $transient_name, 'doing_it_wrong_trigger_error' );

	}


	/**
	 * @hooked deprecated_hook_run
	 * @see _deprecated_hook()
	 *
	 * @param string $hook        The hook that was called.
	 * @param string $replacement The hook that should be used as a replacement.
	 * @param string $version     The version of WordPress that deprecated the argument used.
	 * @param string $message     A message regarding the change.
	 */
	public function log_deprecated_hook_only_once_per_day( $hook, $version, $replacement = '', $message = '' ) {

		$cache_key = md5( __FUNCTION__ . implode( '', func_get_args() ) );

		if ( isset( self::$debug_backtrace_cache[ $cache_key ] ) ) {
			$plugin_slug = self::$debug_backtrace_cache[ $cache_key ];
		} else {
			$plugin_slug                               = $this->api->determine_plugin_slug_from_backtrace();
			self::$debug_backtrace_cache[ $cache_key ] = $plugin_slug;
		}

		if ( $this->settings->get_plugin_slug() != $plugin_slug ) {
			return;
		}

		$transient_name = "log_doing_it_wrong_run_{$hook}_{$plugin_slug}";

		$recently_logged = get_transient( $transient_name );

		$return_false_filter_name = 'deprecated_hook_trigger_error';

		$this->log_and_filter_false( $message, $transient_name, $return_false_filter_name );

		return;
		//
		// if ( false === $recently_logged ) {
		//
		// we're inside deprecated_hook_run which is inside $hook which was initiated by the plugin we're interested in.
		// current_action();
		// global $wp_current_filter;
		// return end( $wp_current_filter );
		//
		// $this->get_hook_details($hook);
		//
		//
		// error_log( "\n\n\n{$transient_name}\n\n" );
		// error_log( ( new Exception() )->getTraceAsString() );
		// error_log( "\n\n\n" );
		//
		// set_transient( $transient_name, time(), DAY_IN_SECONDS );
		// } else {
		//
		// We've already logged this erro for this plugin.
		// if ( ! empty( $filter_name ) ) {
		// add_filter( 'deprecated_hook_trigger_error', '__return_false' );
		// }
		// }
	}
	//
	// **
	// *
	// *
	// * @param $hook
	// */
	// protected function get_hook_details( $hook ): string {
	//
	// global $wp_filter;
	// ** @var \WP_Hook $wp_hook */
	// $wp_hook = $wp_filter[ $hook ];
	//
	//
	//
	// foreach ( $wp_hook->callbacks as $priority => $actions ) {
	//
	// foreach ( $actions as $action ) {
	//
	// $callback = $action['function'];
	//
	// If it's a string, it's a function.
	// if ( is_string( $callback ) ) {
	//
	// print
	//
	// return "action is function: {$callback}";
	// continue;
	// }
	//
	// If it's an array then it's an array of [class,function_name].
	// if ( is_array( $callback ) ) {
	// $class = $callback[0];
	//
	// Is this always a string?
	// $function = $callback[1];
	//
	// This could be static.. then it's a string?
	// if ( is_string( $class ) ) {
	// $class_type = $class;
	// return "action is static {$class_type}::{$function}";
	// } else {
	// $class_type = get_class( $class );
	// return "action is instance {$class_type}->{$function}";
	// }
	//
	// continue;
	// }
	// try {
	// $reflection = new ReflectionFunction( $callback );
	// The class the callback was created inside.
	// $bound_to         = $reflection->getClosureThis();
	// $bound_class_type = get_class( $bound_to );
	//
	// return "action is a closure bound to {$bound_class_type}";
	// } catch ( \ReflectionException $e ) {
	// return "Attempted to find closure's bound class in BH_WP_GetEnhanced_Shop\includes\functions.php – maybe not a closure? {$e->getMessage()}";
	//
	// continue;
	// }
	// }
	// }
	//
	//
	// }
	//

	/**
	 * Check if the transient exists, log, or return false to WordPress's "should-print" filter.
	 *
	 * @param string $message The friendly message to log.
	 * @param string $transient_name
	 * @param string $return_false_filter_name  A WordPress filter used to disable further logging by returning false.
	 */
	protected function log_and_filter_false( string $message, string $transient_name, string $return_false_filter_name = null ) {

		$recently_logged = get_transient( $transient_name );

		// TODO
		$recently_logged = false;

		if ( false === $recently_logged ) {

			$context = array();

			$this->logger->warning( $message, $context );

			// TODO: $this->settings->get_suppress_errors_seconds();
			$content = time(); // The time it was logged at.
			set_transient( $transient_name, $content, WEEK_IN_SECONDS );
		} else {

			// TODO: be more specific.
			// We've already logged this type of error for this plugin.

			if ( ! empty( $return_false_filter_name ) ) {
				add_filter( $return_false_filter_name, '__return_false' );
			}
		}

	}
}
