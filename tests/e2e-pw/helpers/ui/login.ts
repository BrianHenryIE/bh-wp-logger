/**
 * External dependencies
 */
import { Page } from '@playwright/test';
import { expect } from '@wordpress/e2e-test-utils-playwright';

export async function loginAsAdmin( page: Page ): Promise< void > {
	if ( await isLoggedIn( page ) ) {
		return;
	}
	await login( { username: 'admin', password: 'password' }, page );
}

export async function login( user: { username: string; password: string }, page: Page ): Promise< void > {
	if ( await isLoggedIn( page ) ) {
		return;
	}

	await page.goto( '/wp-login.php', { waitUntil: 'networkidle' } );
	await page.fill( 'input[name="log"]', user.username );
	await page.fill( 'input[name="pwd"]', user.password );
	await page.locator( '#loginform' ).getByText( 'Log In' ).click();
	await page.waitForLoadState( 'networkidle' );

	expect( await isLoggedIn( page ) );
}

export async function logout( page: Page ): Promise< void > {
	if ( ! ( await isLoggedIn( page ) ) ) {
		return;
	}

	// Clear WordPress cookies to log out
	const cookies = await page.context().cookies();
	const wpCookies = cookies.filter(
		( cookie ) =>
			cookie.name.startsWith( 'wordpress_' ) ||
			cookie.name.startsWith( 'wp_' ) ||
			cookie.name.includes( 'login' ) ||
			cookie.name.includes( 'session' )
	);

	for ( const cookie of wpCookies ) {
		await page.context().clearCookies( { name: cookie.name } );
	}
	await page.context().clearCookies();

	if ( await isLoggedIn( page ) ) {
		throw new Error( 'Logout failed - still appears to be logged in' );
	}
}

export async function isLoggedIn( page: Page ): Promise< boolean > {
	try {
		const cookies = await page.context().cookies();
		const hasLoginCookie = cookies.some(
			( cookie ) =>
				( cookie.name.startsWith( 'wordpress_logged_in_' ) ||
					cookie.name.includes( 'logged_in' ) ) &&
				cookie.value.length > 0
		);

		try {
			const adminResponse = await page.request.get( '/wp-admin/' );
			const finalUrl = adminResponse.url();
			if ( finalUrl.includes( 'wp-login.php' ) ) {
				return false;
			}
			if ( adminResponse.status() === 200 ) {
				return true;
			}
		} catch ( error ) {
			// fall through
		}

		return hasLoginCookie;
	} catch ( error ) {
		return false;
	}
}
