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

?>

<!-- This file should primarily consist of HTML with a little bit of PHP. -->
<div>
	<h1>Logs Test</h1>
	<h2>Some buttons for writing to the logs.</h2>

	<div>

		<p>Current log level is:</p>
		<p>Log UI page:</p>
		<p>WP_DEBUG:</p>
		<p>WP_DEBUG_DISPLAY:</p>
		<p>WP_DEBUG_LOG:</p>
	</div>
	<div>
		<h3>Log a message</h3>

		<pre>$logger->info('message', 'context');</pre>

		<div>
			<form>
				<input type="text" name="message" placeholder="message"/>
				<input type="text" name="context" placeholder="context"/>

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
