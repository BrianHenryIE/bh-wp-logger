<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\Unit_Testcase;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\AJAX
 */
class AJAX_Unit_Test extends Unit_Testcase {

	protected function setUp(): void {
		parent::setUp();
		$_POST = array();
	}

	protected function tearDown(): void {
		$_POST = array();
		parent::tearDown();
	}

	/**
	 * @covers ::delete_all
	 * @covers ::__construct
	 */
	public function test_delete_all_logs(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_all_logs' => Expected::once(
					fn() => array( 'success' => true )
				),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once(
					fn() => 'development-plugin-slug'
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'nonce_value', 'bh-wp-logger-delete' ),
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_key',
			array(
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'manage_options' ),
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_send_json_success',
			array(
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		// $_POST['action'] is handled by WordPress, not by our function.
		$_POST['plugin_slug'] = 'development-plugin-slug';
		$_POST['_wpnonce']    = 'nonce_value';

		$sut->delete_all();
	}


	/**
	 * @covers ::delete
	 * @covers ::__construct
	 */
	public function test_delete(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_log' => Expected::once(
					fn( string $ymd_date ) => array( 'success' => true )
				),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once(
					fn() => 'development-plugin-slug'
				),
			)
		);

		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'nonce_value', 'bh-wp-logger-delete' ),
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'sanitize_key',
			array(
				'return_arg' => true,
			)
		);

		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'manage_options' ),
				'times'  => 1,
				'return' => true,
			)
		);

		\WP_Mock::userFunction(
			'wp_send_json_success',
			array(
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		// $_POST['action'] is handled by WordPress, not by our function.
		$_POST['plugin_slug']    = 'development-plugin-slug';
		$_POST['_wpnonce']       = 'nonce_value';
		$_POST['date_to_delete'] = '2022-03-02';

		$sut->delete();
	}

	/**
	 * A request for another plugin's logs is left for that plugin's handler: no nonce check, no response.
	 *
	 * @covers ::delete_all
	 * @covers ::is_request_for_this_plugin
	 */
	public function test_delete_all_other_plugin_slug_returns_silently(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_all_logs' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction( 'wp_verify_nonce', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_send_json_success', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_send_json_error', array( 'times' => 0 ) );

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug'] = 'plugin-b';
		$_POST['_wpnonce']    = 'nonce_value';

		$sut->delete_all();
	}

	/**
	 * @covers ::delete_all
	 * @covers ::get_authorization_error
	 */
	public function test_delete_all_invalid_nonce_sends_error(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_all_logs' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'bad_nonce', 'bh-wp-logger-delete' ),
				'times'  => 1,
				'return' => false,
			)
		);
		\WP_Mock::userFunction( 'current_user_can', array( 'times' => 0 ) );
		\WP_Mock::userFunction(
			'wp_send_json_error',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'array' ), 403 ),
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug'] = 'plugin-a';
		$_POST['_wpnonce']    = 'bad_nonce';

		$sut->delete_all();
	}

	/**
	 * @covers ::delete
	 * @covers ::get_authorization_error
	 */
	public function test_delete_without_capability_sends_error(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_log' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'args'   => array( 'manage_options' ),
				'times'  => 1,
				'return' => false,
			)
		);
		\WP_Mock::userFunction(
			'wp_send_json_error',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'array' ), 403 ),
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug']    = 'plugin-a';
		$_POST['_wpnonce']       = 'nonce_value';
		$_POST['date_to_delete'] = '2022-03-02';

		$sut->delete();
	}

	/**
	 * Two plugins using the logger each hook a handler to the same AJAX action; only the one whose slug
	 * matches the request deletes its logs.
	 *
	 * @covers ::delete_all
	 */
	public function test_delete_all_two_plugins_only_matching_plugin_deletes(): void {

		$api_a = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_all_logs' => Expected::never(),
			)
		);
		$api_b = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_all_logs' => Expected::once(
					fn() => array( 'success' => true )
				),
			)
		);

		$settings_a = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);
		$settings_b = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-b',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'nonce_value', 'bh-wp-logger-delete' ),
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction( 'wp_send_json_success', array( 'times' => 1 ) );
		\WP_Mock::userFunction( 'wp_send_json_error', array( 'times' => 0 ) );

		$_POST['plugin_slug'] = 'plugin-b';
		$_POST['_wpnonce']    = 'nonce_value';

		// In WordPress, both are hooked to `wp_ajax_bh_wp_logger_logs_delete_all`; plugin-a's runs first.
		( new AJAX( $api_a, $settings_a ) )->delete_all();
		( new AJAX( $api_b, $settings_b ) )->delete_all();
	}

	/**
	 * @covers ::delete
	 * @covers ::is_request_for_this_plugin
	 */
	public function test_delete_other_plugin_slug_returns_silently(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_log' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction( 'wp_verify_nonce', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_send_json_success', array( 'times' => 0 ) );
		\WP_Mock::userFunction( 'wp_send_json_error', array( 'times' => 0 ) );

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug']    = 'plugin-b';
		$_POST['_wpnonce']       = 'nonce_value';
		$_POST['date_to_delete'] = '2022-03-02';

		$sut->delete();
	}

	/**
	 * @covers ::delete
	 * @covers ::get_authorization_error
	 */
	public function test_delete_invalid_nonce_sends_error(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_log' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'args'   => array( 'bad_nonce', 'bh-wp-logger-delete' ),
				'times'  => 1,
				'return' => false,
			)
		);
		\WP_Mock::userFunction( 'current_user_can', array( 'times' => 0 ) );
		\WP_Mock::userFunction(
			'wp_send_json_error',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'array' ), 403 ),
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug']    = 'plugin-a';
		$_POST['_wpnonce']       = 'bad_nonce';
		$_POST['date_to_delete'] = '2022-03-02';

		$sut->delete();
	}

	/**
	 * @covers ::delete
	 */
	public function test_delete_missing_date_sends_400(): void {

		$api = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_log' => Expected::never(),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => fn() => 'plugin-a',
			)
		);

		\WP_Mock::userFunction( 'sanitize_key', array( 'return_arg' => true ) );
		\WP_Mock::userFunction(
			'wp_verify_nonce',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction(
			'current_user_can',
			array(
				'times'  => 1,
				'return' => true,
			)
		);
		\WP_Mock::userFunction( 'wp_send_json_success', array( 'times' => 0 ) );
		\WP_Mock::userFunction(
			'wp_send_json_error',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'array' ), 400 ),
				'times' => 1,
			)
		);

		$sut = new AJAX( $api, $settings );

		$_POST['plugin_slug'] = 'plugin-a';
		$_POST['_wpnonce']    = 'nonce_value';

		$sut->delete();
	}
}
