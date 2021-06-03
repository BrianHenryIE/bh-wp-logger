<?php
/**
 * Catches PHP warning, deprecated etc. which are then checked to see if they were raised by the
 * plugin the logger is for, in which case they are logged to its log file.
 *
 * A linked list of error handlers.
 */

namespace BrianHenryIE\WP_Logger\API;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Spatie\Backtrace\Backtrace;
use Spatie\Backtrace\Frame;

class PHP_Error_Handler {

	/** @var LoggerInterface */
	protected $logger;

	/** @var Logger_Settings_Interface */
	protected $settings;

	/**
	 * Only one error handler can be added at a time, so we chain them.
	 *
	 * @var callable with signature ( int $errno, string $errstr, ?string $errfile, ?int $errline  )
	 */
	protected $previous_error_handler = null;

	public function __construct( Logger_Settings_Interface $settings, LoggerInterface $logger ) {
		$this->logger   = $logger;
		$this->settings = $settings;
	}

	/**
	 * Since this is hooked on plugins_loaded, it won't display errors that occur as plugins' constructors run.
	 *
	 * But since it's hooked on plugins_loaded (as distinct from just initializing), it allows other plugins to
	 * hook error handlers in before or after this one.
	 *
	 * @hooked plugins_loaded
	 */
	public function init() {

		// TODO: Don't subscribe to compiler and parse errors etc?
		// Is there any practical difference to that.

		// Needs more testing (esp. of multiple) before using in prod.
		return;

		$this->previous_error_handler = set_error_handler(
			array( $this, 'plugin_error_handler' ),
			E_ALL
		);
	}

	/**
	 * The error handler itself.
	 *
	 * @see set_error_handler()
	 *
	 * @param int    $errno the error code (the level of the error raised, as an integer)
	 * @param string $errstr a string describing the error
	 * @param string $errfile the filename in which the error occurred
	 * @param int    $errline the line number in which the error occurred
	 *
	 * @return bool True if error had been handled and no more handling to do, false to pass the error on.
	 */
	public function plugin_error_handler( int $errno, string $errstr, string $errfile, int $errline ) {

		// Check is already logged error here?

		$plugin_related_error = $this->is_related_error( $errno, $errstr, $errfile, $errline );

		if ( ! $plugin_related_error ) {

			// Too few arguments to function PHPUnit\Util\ErrorHandler::__invoke(), 3 passed and exactly 4 expected

			// TODO: Maybe need to check the receiving function accepts four arguments.

			// If there is another handler, return its result, otherwise indicate the error was not handled.
			return $this->return_error_handler_result( false );
		}

		// TODO: maybe don't use transients?

		// e.g. my-plugin-slug-logged-error-4f6ead5467acd...
		$transient_key = "{$this->settings->get_plugin_slug()}-logged-{$this->errno_to_psr3( $errno )}-" . md5( $errstr );

		$transient_value = get_transient( $transient_key );

		// We've already logged this error recently, don't bother logging it again.
		if ( ! empty( $transient_value ) ) {
			return $this->return_error_handler_result( true );
		}

		// TODO: Add regex filters to skip uninteresting errors.
		// e.g. after an error has been reported, don't log it ever again.

		// func_get_args shows extra param ["queue_conn":false,"oauth2_refresh":false].
		$context          = array();
		$context['error'] = array_combine( array( 'errno', 'errstr', 'errfile', 'errline' ), array_slice( func_get_args(), 0, 4 ) );

		// Backtrace will contain two entries for each instance of this class that are
		// looped through until reaching the true handler.
		// TODO: Look at using ->startingFromFrame( function(){} );
		$backtrace_frames = Backtrace::create()->frames();
		/** @var Frame $frame */
		foreach ( $backtrace_frames as $frame ) {
			if ( __FUNCTION__ === $frame->method
				  || 'call_user_func_array' === $frame->method ) {
				array_shift( $backtrace_frames );
			} else {
				break;
			}
		}

		$context['backtrace'] = $backtrace_frames;

		$log_level = $this->errno_to_psr3( $errno );

		$this->logger->$log_level( $errstr, $context );

		// TODO: Fitler on expiration time length.
		set_transient( $transient_key, func_get_args(), WEEK_IN_SECONDS );

		// TODO: Add a filter here
		// e.g. to return false for fatal, i.e so it would log fatal errors to error_log's file and not handle ("surpress" them herer).

		/* Don't execute PHP internal error handler */
		return $this->return_error_handler_result( true );
	}

	/**
	 * Call other registered error handlers before returning the result.
	 *
	 * @param $result
	 *
	 * @return ?bool True if the error has been handled, false if PHP error handler should still run.
	 */
	protected function return_error_handler_result( $result ) {

		if ( ! is_null( $this->previous_error_handler ) ) {

			// If null is returned from the previous handler, treat that as if the error has not been handled by them.
			return ( call_user_func_array( $this->previous_error_handler, func_get_args() ) ) ?? true && $result;
		}

		return $result;
	}

	/**
	 * Logic to check is the error relevant to this plugin.
	 *
	 * Check:
	 * * is the source file path in the plugin directory
	 * * is the plugin string mentioned in the error message
	 *
	 * @param int    $errno the error code (the level of the error raised, as an integer)
	 * @param string $errstr a string describing the error
	 * @param string $errfile the filename in which the error occurred
	 * @param int    $errline the line number in which the error occurred
	 *
	 * @return bool
	 */
	protected function is_related_error( int $errno, string $errstr, string $errfile, int $errline ) {

		// If the source file has the plugin dir in it.
		// Prepend the WP_PLUGINS_DIR so a subdir with the same name (e.g. my-plugin/integrations/your-plugin) does not match.
		$plugin_dir = WP_PLUGIN_DIR . DIRECTORY_SEPARATOR . explode( '/', $this->settings->get_plugin_basename() )[0];
		if ( false !== strpos( $errfile, $plugin_dir ) ) {
			return true;
		}

		if ( false !== strpos( $errstr, $this->settings->get_plugin_slug() ) ) {
			// If the plugin slug is outright named in the error message.
			return true;
		}

		// e.g. WooCommerce Admin could be the $errfile of a problem caused by another plugin.
		$backtrace_frames = Backtrace::create()->frames();
		foreach ( $backtrace_frames as $frame ) {
			if ( false !== strpos( $frame->file, $plugin_dir ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Maps PHP's error types to PSR-3's error levels.
	 *
	 * Some of these will never occur at runtime.
	 *
	 * @see trigger_error()
	 *
	 * @param int $errno
	 *
	 * @return string
	 */
	protected function errno_to_psr3( int $errno ): string {

		$errorType = array(
			E_ERROR             => LogLevel::ERROR,
			E_CORE_ERROR        => LogLevel::ERROR,
			E_COMPILE_ERROR     => LogLevel::ERROR,
			E_USER_ERROR        => LogLevel::ERROR, // User-generated error message  – trigger_error().
			E_RECOVERABLE_ERROR => LogLevel::ERROR,
			E_WARNING           => LogLevel::WARNING,
			E_CORE_WARNING      => LogLevel::WARNING, // Warnings (non-fatal errors) that occur during PHP's initial startup.
			E_COMPILE_WARNING   => LogLevel::WARNING, // Compile-time warnings.
			E_USER_WARNING      => LogLevel::WARNING, // User-generated warning message – trigger_error().
			E_NOTICE            => LogLevel::NOTICE,
			E_USER_NOTICE       => LogLevel::NOTICE,
			E_DEPRECATED        => LogLevel::DEBUG,
			E_USER_DEPRECATED   => LogLevel::DEBUG, // User-generated warning message – trigger_error().
			E_PARSE             => LogLevel::ERROR, // Compile-time parse errors.
		);

		if ( array_key_exists( $errno, $errorType ) ) {
			return $errorType[ $errno ];
		} else {
			return LogLevel::ERROR;
		}

	}
}
