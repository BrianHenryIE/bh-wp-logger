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



	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_immediately_when_totally_not_relevant(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		$init = new Init( $api, $settings, $logger );

		$init->maybe_download_log();

		$this->assertEmpty( $logger->records );
	}

	/**
	 *
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_nonce_fails(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_die',
			array(
				'args'   => array(),
				'return' => function () {
					throw new Exception();
				},
				'times'  => 1,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-bad-nonce';

		$this->expectException( Exception::class );
		$init->maybe_download_log();

		$this->assertTrue( $logger->hasWarning( 'Bad nonce when downloading log.' ) );
	}


	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_download_log_malformed(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'truee';
		$_GET['_wpnonce']     = 'a-good-nonce';

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_page_missing(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		unset( $_GET['page'] );
		$_GET['date'] = '2022-05-27';

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}


	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_date_missing(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 1,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		$_GET['page']         = 'bh-wp-logger-development-plugin-logs';
		unset( $_GET['date'] );

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}

	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_page_does_not_match_plugin(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once( 'bh-wp-logger-development-plugin' ),
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 2,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 2,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		$_GET['page']         = 'not-bh-wp-logger-development-plugin-logs';
		$_GET['date']         = '2022-05-27';

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}


	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_date_malformed(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once( 'bh-wp-logger-development-plugin' ),
			)
		);
		$api      = $this->makeEmpty( API_Interface::class );

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		$_GET['page']         = 'bh-wp-logger-development-plugin-logs';
		$_GET['date']         = '05-27-2022';

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}


	/**
	 * @covers ::maybe_download_log
	 */
	public function test_return_quickly_when_file_does_not_exist(): void {

		$logger   = $this->logger;
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => Expected::once( 'bh-wp-logger-development-plugin' ),
			)
		);
		$api      = $this->makeEmpty(
			API_Interface::class,
			array(
				'get_files' => Expected::once( array() ),
			)
		);

		\WP_Mock::userFunction(
			'check_admin_referer',
			array(
				'args'   => array( 'bh-wp-logger-download' ),
				'return' => 1,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_unslash',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);
		\WP_Mock::userFunction(
			'sanitize_text_field',
			array(
				'return_arg' => 0,
				'times'      => 3,
			)
		);

		$init = new Init( $api, $settings, $logger );

		$_GET['download-log'] = 'true';
		$_GET['_wpnonce']     = 'a-good-nonce';
		$_GET['page']         = 'bh-wp-logger-development-plugin-logs';
		$_GET['date']         = '2022-05-27';

		$init->maybe_download_log();

		// Currently not logging anything.
		$this->assertEmpty( $logger->records );
	}
}
