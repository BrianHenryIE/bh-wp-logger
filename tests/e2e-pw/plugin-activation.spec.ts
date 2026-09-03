/**
 * Verify the development plugin activates correctly in wp-env.
 */
import { test, expect } from '@wordpress/e2e-test-utils-playwright';

test.describe( 'Plugin activation', () => {
	test( 'development plugin is listed on the plugins page', async ( { admin, page } ) => {
		await admin.visitAdminPage( 'plugins.php' );

		// NB: `data-slug` is only reliable for wp.org-hosted plugins (it falls back to the sanitized
		// plugin Name); `data-plugin` is always the plugin file.
		const pluginRow = page.locator(
			'tr[data-plugin="development-plugin/development-plugin.php"]'
		);
		await expect( pluginRow ).toBeVisible();

		const deactivateLink = pluginRow.locator( 'a:has-text("Deactivate")' );
		await expect( deactivateLink ).toBeVisible();
	} );
} );
