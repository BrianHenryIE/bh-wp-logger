<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WPUnit_Testcase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\API
 */
class API_WPUnit_Test extends WPUnit_Testcase {

	protected string $plugin_slug = 'api-wpunit-test';

	protected string $legacy_dir;

	protected string $plugin_dir;

	protected function setUp(): void {
		parent::setUp();

		$this->legacy_dir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs/' );
		$this->plugin_dir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs/' . $this->plugin_slug . '/' );

		wp_mkdir_p( $this->plugin_dir );
	}

	protected function tearDown(): void {
		foreach ( glob( $this->legacy_dir . $this->plugin_slug . '-*.log' ) ?: array() as $file ) {
			wp_delete_file( $file );
		}
		foreach ( glob( $this->plugin_dir . $this->plugin_slug . '-*.log' ) ?: array() as $file ) {
			wp_delete_file( $file );
		}
		if ( is_dir( $this->plugin_dir ) ) {
			rmdir( $this->plugin_dir );
		}

		parent::tearDown();
	}

	protected function get_sut(): API {
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => $this->plugin_slug,
			)
		);

		return new API( $settings, $this->logger );
	}

	/**
	 * @covers ::get_log_files_dir
	 */
	public function test_get_log_files_dir_is_per_plugin_subdirectory(): void {
		$sut = $this->get_sut();

		$this->assertSame( $this->plugin_dir, $sut->get_log_files_dir() );
	}

	/**
	 * @covers ::get_log_files
	 */
	public function test_get_log_files_reads_per_plugin_subdirectory(): void {
		file_put_contents( $this->plugin_dir . $this->plugin_slug . '-2026-01-02.log', "log\n" );

		$sut = $this->get_sut();

		$result = $sut->get_log_files();

		$this->assertSame(
			array( '2026-01-02' => $this->plugin_dir . $this->plugin_slug . '-2026-01-02.log' ),
			$result
		);
	}

	/**
	 * Log files in the old shared `wp-content/uploads/logs/` directory are not read.
	 *
	 * @covers ::get_log_files
	 */
	public function test_get_log_files_ignores_parent_logs_directory(): void {
		file_put_contents( $this->legacy_dir . $this->plugin_slug . '-2026-01-01.log', "log\n" );
		file_put_contents( $this->plugin_dir . $this->plugin_slug . '-2026-01-02.log', "log\n" );

		$sut = $this->get_sut();

		$this->assertSame( array( '2026-01-02' ), array_keys( $sut->get_log_files() ) );
	}

	/**
	 * @covers ::get_log_files
	 */
	public function test_get_log_files_for_missing_date_returns_empty(): void {
		file_put_contents( $this->plugin_dir . $this->plugin_slug . '-2026-01-02.log', "log\n" );

		$sut = $this->get_sut();

		$this->assertSame( array(), $sut->get_log_files( '2025-12-31' ) );
	}
}
