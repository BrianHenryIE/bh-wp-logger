<?php
/**
 * Hook into the behaviour of WordPress functions.php.
 *
 * Use transients to log errors only once per day, with more detail than before.
 * i.e. to prevent logs being flooded with deprecation warnings.
 *
 * Doing this here means we can set the log level per plugin without relying on WP_DEBUG being true.
 *
 * @since      1.0.0
 *
 * @package brianhenryie/bh-wp-logger
 * @author  Brian Henry <BrianHenryIE@gmail.com>
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Intercepts WordPress's logging to prevent duplicate logs and to add detail.
 *
 * @see _deprecated_function()
 * @see _deprecated_argument()
 * @see _doing_it_wrong()
 * @see _deprecated_hook()
 */
class Functions {

	use LoggerAwareTrait;

	/**
	 * Constructor.
	 * No logic, just assignments.
	 *
	 * @param API_Interface             $api The logger's utility functions. Used to check the backtrace to see is it relevant to this plugin.
	 * @param Logger_Settings_Interface $settings The logger settings. Not used here.
	 * @param ?LoggerInterface          $logger PSR logger.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		LoggerInterface $logger
	) {
		$this->setLogger( $logger );
	}

	/**
	 * Limits logging deprecated functions to once per day.
	 * Logs more detail than usual.
	 *
	 * @hooked deprecated_function_run
	 *
	 * @param string $function    The function that was called.
	 * @param string $replacement_function Optional. The function that should have been called. Default empty.
	 * @param string $version     The version of WordPress that deprecated the function.
	 *
	 * @see _deprecated_function()
	 */
	public function log_deprecated_functions_only_once_per_day( $function, $replacement_function, $version ): void {

		if ( ! $this->api->is_backtrace_contains_plugin( implode( '', func_get_args() ) ) ) {
			return;
		}

		$plugin_slug = $this->settings->get_plugin_slug();

		$transient_name = "log_deprecated_function_{$function}_{$plugin_slug}";

		$recently_logged = get_transient( $transient_name );

		if ( empty( $recently_logged ) ) {

			if ( $replacement_function ) {
				$log_message =
					sprintf(
					/* translators: 1: PHP function name, 2: Version number, 3: Alternative function name. */
						__( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
						$function,
						$version,
						$replacement_function
					);
			} else {
				$log_message =
					sprintf(
					/* translators: 1: PHP function name, 2: Version number. */
						__( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
						$function,
						$version
					);
			}

			$context = array(
				'function'             => $function,
				'replacement_function' => $replacement_function,
				'version'              => $version,
			);

			$this->logger->warning( $log_message, $context );

			set_transient( $transient_name, $log_message, DAY_IN_SECONDS );
		}

		// Suppress WordPress's own logging.
		add_filter( 'deprecated_function_trigger_error', '__return_false' );
	}

	/**
	 * Limits logging deprecated arguments to once per day.
	 * Logs more detail than usual.
	 *
	 * @hooked deprecated_function_run
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message regarding the change.
	 * @param string $version  The version of WordPress that deprecated the argument used.
	 *
	 * @see _deprecated_argument()
	 */
	public function log_deprecated_arguments_only_once_per_day( $function, $message, $version ): void {

		if ( ! $this->api->is_backtrace_contains_plugin( implode( '', func_get_args() ) ) ) {
			return;
		}

		$plugin_slug = $this->settings->get_plugin_slug();

		$transient_name = "log_deprecated_argument_{$function}_{$plugin_slug}";

		$recently_logged = get_transient( $transient_name );

		if ( empty( $recently_logged ) ) {

			if ( $message ) {
				$log_message =
					sprintf(
					/* translators: 1: PHP function name, 2: Version number, 3: Optional message regarding the change. */
						__( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s! %3$s' ),
						$function,
						$version,
						$message
					);
			} else {
				$log_message =
					sprintf(
					/* translators: 1: PHP function name, 2: Version number. */
						__( '%1$s was called with an argument that is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
						$function,
						$version
					);
			}

			$context = array(
				'function' => $function,
				'message'  => $message,
				'version'  => $version,
			);

			$this->logger->warning( $log_message, $context );

			set_transient( $transient_name, $log_message, DAY_IN_SECONDS );
		}

		add_filter( 'deprecated_argument_trigger_error', '__return_false' );
	}

	/**
	 * `_doing_it_wrong` runs e.g. when a function is called before a required action has run.
	 * This function limits logging `_doing_it_wrong` errors to once per day.
	 * Logs more detail than usual.
	 *
	 * @hooked doing_it_wrong_run
	 * @see _doing_it_wrong()
	 *
	 * @hooked doing_it_wrong_run
	 *
	 * @param string $function The function that was called.
	 * @param string $message  A message explaining what has been done incorrectly.
	 * @param string $version  The version of WordPress where the message was added.
	 */
	public function log_doing_it_wrong_only_once_per_day( $function, $message, $version ): void {

		if ( ! $this->api->is_backtrace_contains_plugin( implode( '', func_get_args() ) ) ) {
			return;
		}

		$plugin_slug = $this->settings->get_plugin_slug();

		$transient_name = "log_doing_it_wrong_run_{$function}_{$plugin_slug}";

		$recently_logged = get_transient( $transient_name );

		if ( empty( $recently_logged ) ) {

			if ( $version ) {
				/* translators: %s: Version number. */
				$version = sprintf( __( '(This message was added in version %s.)' ), $version );
			}

			$message .= ' ' . sprintf(
				/* translators: %s: Documentation URL. */
				__( 'Please see <a href="%s">Debugging in WordPress</a> for more information.' ),
				__( 'https://wordpress.org/support/article/debugging-in-wordpress/' )
			);

			$log_message =
				sprintf(
				/* translators: Developer debugging message. 1: PHP function name, 2: Explanatory message, 3: WordPress version number. */
					__( '%1$s was called <strong>incorrectly</strong>. %2$s %3$s' ),
					$function,
					$message,
					$version
				);

			$context = array(
				'function' => $function,
				'message'  => $message,
				'version'  => $version,
			);

			$this->logger->warning( $log_message, $context );

			set_transient( $transient_name, $log_message, DAY_IN_SECONDS );
		}

		add_filter( 'doing_it_wrong_trigger_error', '__return_false' );
	}

	/**
	 * Log deprecated hooks called by this plugin only once per day.
	 *
	 * When a deprecated hook is called, WordPress will call this function.
	 * If the backtrace does not contain this logger's plugin, it will return early.
	 * Otherwise:
	 * * Check for a transient indicating we have logged this already
	 * * If absent, create one
	 * * Prevent WordPress from logging the deprecation warning (w/filter)
	 *
	 * @hooked deprecated_hook_run
	 * @see _deprecated_hook()
	 *
	 * @param string $hook        The hook that was called.
	 * @param string $replacement The hook that should be used as a replacement.
	 * @param string $version     The version of WordPress that deprecated the argument used.
	 * @param string $message     A message regarding the change.
	 */
	public function log_deprecated_hook_only_once_per_day( $hook, $replacement, $version, $message ): void {

		if ( ! $this->api->is_backtrace_contains_plugin( implode( '', func_get_args() ) ) ) {
			return;
		}

		$plugin_slug = $this->settings->get_plugin_slug();

		$transient_name = "log_deprecated_hook_run_{$hook}_{$plugin_slug}";

		$recently_logged = get_transient( $transient_name );

		if ( empty( $recently_logged ) ) {

			$message = empty( $message ) ? '' : ' ' . $message;

			if ( $replacement ) {
				$log_message =
					sprintf(
					/* translators: 1: WordPress hook name, 2: Version number, 3: Alternative hook name. */
						__( '%1$s is <strong>deprecated</strong> since version %2$s! Use %3$s instead.' ),
						$hook,
						$version,
						$replacement
					) . $message;

			} else {
				$log_message =
					sprintf(
					/* translators: 1: WordPress hook name, 2: Version number. */
						__( '%1$s is <strong>deprecated</strong> since version %2$s with no alternative available.' ),
						$hook,
						$version
					) . $message;
			}

			$context = array(
				'hook'        => $hook,
				'replacement' => $replacement,
				'version'     => $version,
				'message'     => $message,
			);

			$this->logger->warning( $log_message, $context );

			set_transient( $transient_name, $log_message, DAY_IN_SECONDS );
		}

		add_filter( 'deprecated_hook_trigger_error', '__return_false' );
	}
}
