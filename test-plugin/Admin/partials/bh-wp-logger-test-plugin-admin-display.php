<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    BH_WP_Logger_Test_Plugin
 * @subpackage BH_WP_Logger_Test_Plugin/admin/partials
 */

/** @var string $plugin_log_level The PSR log level, formatted to display. */
/** @var string $is_woocommerce_logger Is the logger logging via wc_logger(). */
/** @var string $plugin_log_file Path to the log file.  */
/** @var string $plugin_log_url URL to view the logs.  */
/** @var string $wp_debug */
/** @var string $wp_debug_display */
/** @var string $wp_debug_log */

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<h1>Logs Test</h1>
	<h2>Some buttons for writing to the logs.</h2>

	<div>

		<p>Plugin log level is: <code><?php echo esc_html( $plugin_log_level ); ?></code></p>
		<p>Plugin is using <code>wc_logger()</code>: <code><?php echo esc_html( $is_woocommerce_logger ); ?></code></p>
		<p>Plugin log file is at: <code><?php echo esc_html( $plugin_log_file ); ?></code></p>
		<p>WP_DEBUG: <code><?php echo esc_html( $wp_debug ); ?></code></p>
		<p>WP_DEBUG_DISPLAY: <code><?php echo esc_html( $wp_debug_display ); ?></code></p>
		<p>WP_DEBUG_LOG: <code><?php echo esc_html( $wp_debug_log ); ?></code></p>
	</div>
	<div>
		<p><a class="button button-primary" href="<?php echo $plugin_log_url; ?>">View logs</a></p>
	</div>
	<div>
		<h3>Log a message</h3>

		<pre>$logger->info('message', 'context');</pre>

		<div>
			<form>
				<input type="text" name="message" id="log_message" placeholder="message"/>
				<input type="text" name="context" id="log_context" placeholder="context"/>

				<button type="button" class="button log log-test" name="debug-message">Debug</button>
				<button type="button" class="button log log-test" name="info-message">Info</button>
				<button type="button" class="button log log-test" name="notice-message">Notice</button>
				<button type="button" class="button log log-test" name="warning-message">Warning</button>
				<button type="button" class="button log log-test" name="error-message">Error</button>
			</form>
		</div>
	</div>

	<div>
		<h3>Trigger a PHP error</h3>

		<pre>trigger_error( 'message', E_USER_NOTICE );</pre>

		<form>
			<button type="button" class="button log log-test" name="deprecated-php">Deprecated</button>
			<button type="button" class="button log log-test" name="notice-php">Notice</button>
			<button type="button" class="button log log-test" name="warning-php">Warning</button>
			<button type="button" class="button log log-test" name="error-php">Error</button>

		</form>
	</div>

	<div>
		<h3>Trigger a WordPress <code>doing_it_wrong</code> warning</h3>

		<form>
			<button type="button" class="button log log-test" name="doing_it_wrong_run-wordpress">doing_it_wrong</button>
			<button type="button" class="button log log-test" name="deprecated_function_run-wordpress">deprecated_function</button>
			<button type="button" class="button log log-test" name="deprecated_argument_run-wordpress">deprecated_argument</button>
			<button type="button" class="button log log-test" name="deprecated_hook_run-wordpress">deprecated_hook</button>
		</form>
	</div>

	<div>
		<h3>Throw an [uncaught] Exception</h3>

		<pre>throw new \Exception( 'log test exception' );</pre>

		<form>
			<button type="button" class="button log log-test" name="uncaught-exception">Throw</button>
		</form>
	</div>


	<div>
		<h3>Delete all transients</h3>

		<p>Transients are used to prevent duplicate logs, in some cases.</p>

		<pre>$wpdb->query('DELETE FROM ' . $wpdb->options . ' WHERE option_name LIKE "_transient_%"');</pre>

		<form>
			<button type="button" class="button log log-test" name="delete-transients">Delete Transients</button>
		</form>
	</div>

	<div id="log-table"></div>
</div>
