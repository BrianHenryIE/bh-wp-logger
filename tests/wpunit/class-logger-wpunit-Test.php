<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WC_Logger\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce_Logger_Settings_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Logger
 */
class Logger_WPUnit_Test extends \BrianHenryIE\WP_Logger\WPUnit_Testcase {

	/**
	 * When WooCommerce is active and the plugin uses the WooCommerce_Logger_Interface marker to indicate we should
	 * use wc_logger, check the correct logger is used.
	 *
	 * @see wc_get_logger()
	 * @see WooCommerce_Logger_Settings_Interface
	 *
	 * @covers ::__construct
	 */
	public function tests_woocommerce_logger() {

		$settings = new class() implements WooCommerce_Logger_Settings_Interface {

			public function get_log_level(): string {
				return LogLevel::DEBUG;
			}

			public function get_plugin_name(): string {
				return 'Test';
			}

			public function get_plugin_slug(): string {
				return 'test';
			}

			public function get_plugin_basename(): string {
				return 'test/test.php';
			}

			public function get_cli_base(): ?string {
				return null;
			}
		};

		$sut = new Logger( $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'woocommerce_loaded' );

		$logger = $sut->get_logger();

		$this->assertInstanceOf( WC_PSR_Logger::class, $logger );
	}

	/**
	 * For a non-WooCommerce logger, Klogger should be used.
	 *
	 * @covers ::__construct
	 */
	public function tests_regular_logger() {

		$settings = new class() implements Logger_Settings_Interface {

			public function get_log_level(): string {
				return LogLevel::DEBUG;
			}

			public function get_plugin_name(): string {
				return 'Test';
			}

			public function get_plugin_slug(): string {
				return 'test';
			}

			public function get_plugin_basename(): string {
				return 'test/test.php';
			}

			public function get_cli_base(): ?string {
				return null;
			}
		};

		assert( ! ( $settings instanceof WooCommerce_Logger_Settings_Interface ) );

		$sut = new Logger( $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'plugins_loaded' );

		$logger = $sut->get_logger();

		$this->assertInstanceOf( \Monolog\Logger::class, $logger );
	}


	/**
	 * If a plugin asks to use the WooCommerce logger, but WooCommerce is inactive, use the default KLogger.
	 *
	 * @covers ::__construct
	 */
	public function tests_woocommerce_inactive_logger() {

		$settings = new class() implements WooCommerce_Logger_Settings_Interface {

			public function get_log_level(): string {
				return LogLevel::DEBUG;
			}

			public function get_plugin_name(): string {
				return 'Test';
			}

			public function get_plugin_slug(): string {
				return 'test';
			}

			public function get_plugin_basename(): string {
				return 'test/test.php';
			}

			public function get_cli_base(): ?string {
				return null;
			}
		};

		// Remove WooCommerce from the active plugins list.
		add_filter(
			'active_plugins',
			fn( $active_plugins ) => array_filter(
				$active_plugins,
				fn( $element ) => 'woocommerce/woocommerce.php' !== $element
			),
			999
		);

		$sut = new Logger( $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'plugins_loaded' );

		$logger = $sut->get_logger();

		$this->assertInstanceOf( \Monolog\Logger::class, $logger );
	}

	/**
	 * Directory created by {@see self::test_log_file_written_to_per_plugin_subdirectory()}, removed in {@see self::tearDown()}.
	 */
	protected ?string $per_plugin_log_dir = null;

	protected function tearDown(): void {
		if ( null !== $this->per_plugin_log_dir && is_dir( $this->per_plugin_log_dir ) ) {
			// GLOB_BRACE so dotfiles (e.g. `.htaccess`) are matched too, otherwise `rmdir()` fails.
			foreach ( glob( $this->per_plugin_log_dir . '/{,.}[!.,!..]*', GLOB_BRACE ) ?: array() as $file ) {
				wp_delete_file( $file );
			}
			rmdir( $this->per_plugin_log_dir );
		}
		$this->per_plugin_log_dir = null;

		parent::tearDown();
	}

	/**
	 * Log files should be written to a per-plugin subdirectory: wp-content/uploads/logs/{plugin-slug}/.
	 *
	 * @covers ::__construct
	 */
	public function test_log_file_written_to_per_plugin_subdirectory(): void {

		$settings = new class() implements Logger_Settings_Interface {

			public function get_log_level(): string {
				return LogLevel::DEBUG;
			}

			public function get_plugin_name(): string {
				return 'Test';
			}

			public function get_plugin_slug(): string {
				return 'logger-wpunit-test-subdir';
			}

			public function get_plugin_basename(): string {
				return 'logger-wpunit-test-subdir/logger-wpunit-test-subdir.php';
			}

			public function get_cli_base(): ?string {
				return null;
			}
		};

		$this->per_plugin_log_dir = wp_normalize_path( WP_CONTENT_DIR . '/uploads/logs/logger-wpunit-test-subdir' );
		$expected_file            = sprintf( '%s/logger-wpunit-test-subdir-%s.log', $this->per_plugin_log_dir, gmdate( 'Y-m-d' ) );

		$sut = new Logger( $settings );

		$sut->info( 'test log entry' );

		$this->assertFileExists( $expected_file );
		$this->assertStringContainsString( 'test log entry', (string) file_get_contents( $expected_file ) );
	}
}
