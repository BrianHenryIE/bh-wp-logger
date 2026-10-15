<?php

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;
use Exception;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WP_Includes\Init
 */
class Init_Unit_Test extends Unit_Testcase {

	protected function setUp(): void {
		parent::setUp();
		$_GET = array();

		\WP_Mock::userFunction( 'wp_unslash', array( 'return_arg' => 0 ) );
		\WP_Mock::userFunction( 'sanitize_text_field', array( 'return_arg' => 0 ) );
		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => 0 ) );
	}

	protected function tearDown(): void {
		$_GET = array();
		parent::tearDown();
	}

	/**
	 * A valid download request for the `development-plugin` logs page.
	 *
	 * @param string $page The `page` query parameter.
	 * @param string $date The `date` query parameter.
	 */
	protected function set_download_request( string $page = 'development-plugin-logs', string $date = '2022-05-27' ): void {
		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		$_GET['page']         = $page;
		$_GET['date']         = $date;
	}

	protected function get_settings( string $slug = 'development-plugin' ): Logger_Settings_Interface {
		return $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => $slug,
			)
		);
	}

	/**
	 * The API should not be asked for log files.
	 */
	protected function get_unused_api(): API_Interface {
		return $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_files' => Expected::never(),
			)
		);
	}

	/**
	 * Requests not for this plugin's log download must not check the nonce or capability.
	 */
	protected function expect_no_authorization_checks(): void {
		\WP_Mock::userFunction( 'wp_verify_nonce', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'current_user_can', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_die', array( 'times' => 0 ) );
	}

	protected function mock_authorized( bool $nonce_valid = true, bool $can_manage_options = true ): void {
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'a-good-nonce', 'bh-wp-logger-download' ),
				'return' => $nonce_valid ? 1 : false,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'manage_options' ),
				'return' => $can_manage_options,
			)
		);
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_immediately_when_totally_not_relevant(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_download_log_malformed(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request();
		$_GET['download-log'] = 'truee';

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_page_missing(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request();
		unset( $_GET['page'] );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_date_missing(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request();
		unset( $_GET['date'] );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * A request for another plugin's log is left for that plugin's handler: no nonce or capability check.
	 *
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_page_does_not_match_plugin(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request( 'not-development-plugin-logs' );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * Another plugin's logs page, whose slug starts with this plugin's slug, is not this plugin's to serve.
	 *
	 * @covers ::maybe_download_log
	 */
	public function test_return_when_page_is_for_plugin_with_longer_slug(): void {

		$this->expect_no_authorization_checks();

		$init = new Init( $this->get_unused_api(), $this->get_settings( 'bh-wp-aws' ), $this->logger );

		$this->set_download_request( 'bh-wp-aws-ses-bounce-handler-logs' );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_die_when_nonce_fails(): void {

		$this->mock_authorized( false );

		\WP_Mock::userFunction(
			'wp_die',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ), 403 ),
				'return' => function () {
					throw new Exception();
				},
				'times'  => 1,
			)
		);

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request();

		try {
			$init->maybe_download_log();
			$this->fail( 'Expected wp_die().' );
		} catch ( Exception $exception ) {
			$this->assertTrue( $this->logger->hasWarning( 'Bad nonce when downloading log.' ) );
		}
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_die_when_user_cannot_manage_options(): void {

		$this->mock_authorized( true, false );

		\WP_Mock::userFunction(
			'wp_die',
			array(
				'args'   => array( \WP_Mock\Functions::type( 'string' ), 403 ),
				'return' => function () {
					throw new Exception();
				},
				'times'  => 1,
			)
		);

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request();

		try {
			$init->maybe_download_log();
			$this->fail( 'Expected wp_die().' );
		} catch ( Exception $exception ) {
			$this->assertTrue( $this->logger->hasWarning( 'User without manage_options capability tried to download log.' ) );
		}
	}

	/**
	 * @covers ::maybe_download_log
	 * @covers ::download_log_for_date
	 */
	public function test_return_quickly_when_date_malformed(): void {

		$this->mock_authorized();

		$init = new Init( $this->get_unused_api(), $this->get_settings(), $this->logger );

		$this->set_download_request( 'development-plugin-logs', '2022-5-27' );

		$init->maybe_download_log();

		$this->assertEmpty( $this->logger->records );
	}

	/**
	 * The download link on the logs page uses `page={slug}-logs`.
	 *
	 * @covers ::maybe_download_log
	 * @covers ::download_log_for_date
	 */
	public function test_looks_up_log_file_when_page_is_this_plugins_logs_page(): void {

		$this->mock_authorized();

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_log_files' => Expected::once(
					function ( ?string $date ): array {
						$this->assertSame( '2026-10-14', $date );
						return array();
					}
				),
			)
		);

		$init = new Init( $api, $this->get_settings( 'bh-wp-aws-ses-bounce-handler' ), $this->logger );

		$this->set_download_request( 'bh-wp-aws-ses-bounce-handler-logs', '2026-10-14' );

		$init->maybe_download_log();
	}
}
