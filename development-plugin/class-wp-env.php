<?php
/**
 * Fix for cron jobs not working in wp-env.
 *
 * Without this, `wp cron test` returns:
 * `Error: WP-Cron spawn failed with error: cURL error 7: Failed to connect to localhost port 8888 after 0 ms: Could not connect to server`.
 *
 * NB: This potentially has side effects, e.g. an email sent on a cron job might contain links to the modified URL.
 *
 * @see https://github.com/WordPress/gutenberg/issues/20569
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BH_WP_Logger_Test_Plugin;

use CurlHandle;
use Exception;
use WP_HTTP_Requests_Hooks;
use WpOrg\Requests\Response;

/**
 * Modify the URL used in requests to itself.
 *
 * The parameters vary and are documented.
 * phpcs:disable Squiz.Commenting.FunctionComment.MissingParamTag
 */
class WP_Env {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->record_hostname();
	}

	/**
	 * Partly pulled from wp-graphql, where they generate a mu-plugin during wp-env boot script.
	 */
	public function register_hooks(): void {
		add_filter( 'site_url', array( $this, 'wpenv_fix_url' ), 1, 2 );
		add_filter( 'home_url', array( $this, 'wpenv_fix_url' ), 1, 2 );
		add_filter( 'wp_login_url', array( $this, 'wpenv_fix_url' ), 1, 2 );
		add_filter( 'admin_url', array( $this, 'wpenv_fix_url' ), 1, 2 );
		add_action( 'requests-requests.before_request', array( $this, 'wpenv_fix_requests_url' ), 1, 5 );
	}

	/**
	 * Record the container's hostname that WordPress sees for itself.
	 *
	 * Do not record it when running in the `cli` or `tests-cli` containers.
	 */
	protected function record_hostname(): void {

		if ( defined( 'WP_CLI' ) && ( true === constant( 'WP_CLI' ) ) ) {
			return;
		}

		$hostname = gethostname();

		if ( ! $hostname ) {
			return;
		}

		update_option( 'wp_env_cron_hostname', $hostname );
	}

	/**
	 * Edit urls as the Requests HTTP library is about to use them.
	 *
	 * @see do_action_ref_array()
	 * @see WP_HTTP_Requests_Hooks::dispatch()
	 * @hooked requests-requests.before_request
	 *
	 * `WP_HTTP_Requests_Hooks::dispatch()` fires on at least: `requests.before_request`, `curl.before_request`,
	 * `curl.before_send`, `curl.after_send`, `curl.after_request`, `requests.before_parse`,
	 * `requests.before_redirect_check`, `requests.after_request`, which are each prefixed with `requests-` for
	 * their action names.
	 *
	 * @phpstan-type Url string
	 * @phpstan-type Data mixed
	 * @phpstan-type Method string|"GET"|"POST"
	 * @phpstan-type HttpHeadersArray array<string,string>
	 * @phpstan-type RequestOptions array{timeout:int, connect_timeout:int, useragent:string, protocol_version:float, redirected:int, redirects:int, follow_redirects:bool, blocking:bool, type:string, filename:bool, auth:bool, proxy:bool, cookies:\WpOrg\Requests\Cookie\Jar, max_bytes:bool, idn:bool, hooks:WP_HTTP_Requests_Hooks, transport:null, verify:string, verifyname:bool, data_format:string}
	 * @phpstan-type ResponseHeadersString string
	 *
	 * @phpstan-type RequestsBeforeRequest array{0:Url, 1:HttpHeadersArray, 2:Data, 3:Method, 4:RequestOptions}
	 * @phpstan-type CurlBeforeRequestParameters array{0:CurlHandle}
	 * @phpstan-type CurlBeforeSendParameters array{0:CurlHandle}
	 * @phpstan-type CurlAfterSendParameters array{}
	 * @phpstan-type CurlAfterRequestParameters array{1:array<string,mixed>}
	 * @phpstan-type RequestsBeforeParseParameters array{0:ResponseHeadersString, 1:Url, 2:HttpHeadersArray, 3:Data, 4:Method, 5:RequestOptions}
	 * @phpstan-type RequestsBeforeRedirectCheckParameters array{0:Response, 1:HttpHeadersArray, 2:Data, 3:RequestOptions}
	 * @phpstan-type RequestsAfterRequest array{0:Response, 1:HttpHeadersArray, 2:Data, 3:RequestOptions}
	 */
	public function wpenv_fix_requests_url( &$parameter0 = null, &$parameter1 = null, &$parameter2 = null, &$parameter3 = null, &$parameter4 = null, &$parameter5 = null ): void {
		try {
			$is_url = function ( mixed $maybe_url ): bool {
				return is_string( $maybe_url ) && sanitize_url( $maybe_url ) === $maybe_url;
			};

			if ( ! $is_url( $parameter0 ) ) {
				return;
			}

			$parameter0 = $this->get_internal_url( $parameter0 );
		} catch ( Exception $_exception ) {
			return;
		}
	}

	/**
	 * Replace the URL when it is an internal cron request or a(n internal) WP CLI request.
	 *
	 * @see get_site_url()
	 * @see cron.php:957
	 *
	 * @param string $url  The full URL.
	 * @param string $path The URL path.
	 *
	 * @throws Exception If an error occurs running `preg_replace()` on the URL.
	 */
	public function wpenv_fix_url( string $url, string $path = '' ): string {

		switch ( true ) {
			case 'wp-cron.php' === $path:
			case ( isset( $_SERVER['REQUEST_URI'] ) && 'wp-cron.php' === $_SERVER['REQUEST_URI'] ):
			case wp_doing_cron():
			case defined( 'WP_CLI' ) && ( true === constant( 'WP_CLI' ) ):
			case ! isset( $_SERVER['HTTP_USER_AGENT'] ):
				return $this->get_internal_url( $url );
			default:
				return $url;
		}
	}

	/**
	 * Given a `localhost` or `127.0.0.1` URL, strip the port and use the internal hostname.
	 *
	 * @param string $url Whatever URL is about to be used.
	 *
	 * @throws Exception If the regex were to (unlikely) fail.
	 */
	protected function get_internal_url( string $url ): string {
		$internal_hostname = get_option( 'wp_env_cron_hostname' );
		if ( ! is_string( $internal_hostname ) ) {
			$internal_hostname = 'localhost';
		}
		return preg_replace(
			pattern: '#(https?://)(localhost|127.0.0.1):\d{1,6}#',
			replacement: '${1}' . preg_quote( $internal_hostname, '#' ),
			subject: $url
		) ?? ( fn() => throw new Exception( esc_html( 'The `WP_Env::get_internal_url()` regex failed: ' . preg_last_error_msg() ) ) )();
	}
}
