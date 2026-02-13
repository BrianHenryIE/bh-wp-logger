<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\AJAX
 */
class AJAX_Unit_Test extends \Codeception\Test\Unit {

	protected function setup(): void {
		parent::setup();
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
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
					function () {
						return array( 'success' => true ); }
				),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once(
					function () {
						return 'development-plugin-slug';}
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
					function ( string $ymd_date ) {
						return array( 'success' => true ); }
				),
			)
		);

		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once(
					function () {
						return 'development-plugin-slug';
					}
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
}
