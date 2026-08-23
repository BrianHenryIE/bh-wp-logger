<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Admin_Notices
 */
class Admin_Notices_Unit_Test extends Unit_Testcase {

	/**
	 * @covers ::admin_notices
	 */
	public function test_delete_option_when_on_logs_page(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);
		$api      = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction( 'wp_doing_ajax' )->andReturnFalse();

		\WP_Mock::userFunction(
			'delete_option',
			array(
				'args'  => array( 'test-recent-error-data' ),
				'times' => 1,
			)
		);

		global $pagenow;
		$pagenow = 'admin.php';
		global $plugin_page;
		$plugin_page = 'test-logs';

		$sut = new Admin_Notices( $api, $settings, $logger );

		$sut->admin_notices();
	}

	/**
	 * Dismissing another plugin's WPTRT notice must not be intercepted: this class previously called
	 * `check_admin_referer()` with its own notice's nonce action on every `wptrt_dismiss_notice` ajax
	 * request, `die()`ing with a 403 when the request was for a different notice (whose nonce is for
	 * that notice's own id), e.g. bh-wp-private-uploads' "directory is publicly accessible" notice.
	 *
	 * `check_admin_referer` is deliberately not stubbed: if the code under test called it, WP_Mock
	 * would raise an undefined-function error.
	 *
	 * @covers ::admin_notices
	 */
	public function test_returns_early_when_ajax_dismissal_is_for_another_notice(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);
		$api      = $this->makeEmpty( API_Interface::class, array() );

		\WP_Mock::userFunction( 'is_admin' )->andReturnTrue();
		\WP_Mock::userFunction( 'wp_doing_ajax' )->andReturnTrue();
		\WP_Mock::passthruFunction( 'sanitize_key' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		// The option is only read after the ajax gate, so it must never be queried here.
		\WP_Mock::userFunction(
			'get_option',
			array(
				'times' => 0,
			)
		);

		global $pagenow;
		$pagenow = 'index.php';

		$_POST['action'] = 'wptrt_dismiss_notice';
		$_POST['id']     = 'other-plugin-private-uploads-url-is-public';

		try {
			$sut = new Admin_Notices( $api, $settings, $logger );

			$sut->admin_notices();

			$this->assertEmpty( $sut->get_all() );
		} finally {
			unset( $_POST['action'], $_POST['id'] );
		}
	}

	/**
	 * An ajax dismissal of this plugin's own recent-error notice proceeds past the gate (observable
	 * because the recent-error option is then queried).
	 *
	 * @covers ::admin_notices
	 */
	public function test_proceeds_when_ajax_dismissal_is_for_own_notice(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test',
			)
		);
		$api      = $this->makeEmpty( API_Interface::class, array() );

		\WP_Mock::userFunction( 'is_admin' )->andReturnTrue();
		\WP_Mock::userFunction( 'wp_doing_ajax' )->andReturnTrue();
		\WP_Mock::passthruFunction( 'sanitize_key' );
		\WP_Mock::passthruFunction( 'wp_unslash' );

		\WP_Mock::userFunction(
			'get_option',
			array(
				'args'   => array( 'test-recent-error-data' ),
				'times'  => 1,
				'return' => false,
			)
		);

		global $pagenow;
		$pagenow = 'index.php';

		$_POST['action'] = 'wptrt_dismiss_notice';
		$_POST['id']     = 'test-recent-error';

		try {
			$sut = new Admin_Notices( $api, $settings, $logger );

			$sut->admin_notices();
		} finally {
			unset( $_POST['action'], $_POST['id'] );
		}
	}

	/**
	 * @covers ::admin_notices
	 */
	public function test_return_early_when_not_in_admin_or_ajax(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::never(),
			)
		);
		$api      = $this->makeEmpty(
			API_Interface::class,
			array()
		);

		\WP_Mock::userFunction(
			'is_admin',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_doing_ajax',
			array(
				'return' => false,
				'times'  => 1,
			)
		);

		$sut = new Admin_Notices( $api, $settings, $logger );

		$sut->admin_notices();
	}
}
