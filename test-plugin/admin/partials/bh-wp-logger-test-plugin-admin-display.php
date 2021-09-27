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

		<p>Plugin log level is: <code><?php echo $plugin_log_level; ?></code></p>
        <p>Plugin log file is at: <a href="<?php echo $plugin_log_url; ?>">View logs</a></p>
		<p>WP_DEBUG: <code><?php echo $wp_debug; ?></code></p>
		<p>WP_DEBUG_DISPLAY: <code><?php echo $wp_debug_display; ?></code></p>
		<p>WP_DEBUG_LOG: <code><?php echo $wp_debug_log; ?></code></p>
	</div>
	<div>
		<h3>Log a message</h3>

		<pre>$logger->info('message', 'context');</pre>

		<div>
			<form>
				<input type="text" name="message" id="log_message" placeholder="message"/>
				<input type="text" name="context" id="log_context" placeholder="context"/>

				<button type="button" class="button log" name="debug-message">Debug</button>
				<button type="button" class="button log" name="info-message">Info</button>
				<button type="button" class="button log" name="notice-message">Notice</button>
				<button type="button" class="button log" name="warning-message">Warning</button>
				<button type="button" class="button log" name="error-message">Error</button>
			</form>
		</div>
	</div>

	<div>
		<h3>Trigger a PHP error</h3>

		<pre>trigger_error( 'message', E_USER_NOTICE );</pre>

		<form>
			<button type="button" class="button log" name="deprecated-php">Deprecated</button>
			<button type="button" class="button log" name="notice-php">Notice</button>
			<button type="button" class="button log" name="warning-php">Warning</button>
			<button type="button" class="button log" name="error-php">Error</button>

		</form>
	</div>

	<div id="log-table"></div>
</div>
