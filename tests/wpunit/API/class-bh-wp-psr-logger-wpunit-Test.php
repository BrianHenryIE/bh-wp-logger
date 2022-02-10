<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LogLevel;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger
 */
class BH_WP_PSR_Logger_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * When WooCommerce is active and the plugin uses the WooCommerce_Logger_Interface marker to indicate we should
	 * use wc_logger, check the correct logger is used.
	 *
	 * @see wc_get_logger()
	 * @see WooCommerce_Logger_Interface
	 *
	 * @covers ::__construct
	 */
	public function tests_woocommerce_logger() {

		$settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

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
		};

		$api = $this->makeEmpty( API::class );

		assert( $settings instanceof WooCommerce_Logger_Interface );

		$sut = new BH_WP_PSR_Logger( $api, $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'woocommerce_loaded' );

		$property = new \ReflectionProperty( BH_WP_PSR_Logger::class, 'logger' );
		$property->setAccessible( true );

		$logger = $property->getValue( $sut );

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
		};

		$api = $this->makeEmpty( API::class );

		assert( ! ( $settings instanceof WooCommerce_Logger_Interface ) );

		$sut = new BH_WP_PSR_Logger( $api, $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'plugins_loaded' );

		$property = new \ReflectionProperty( BH_WP_PSR_Logger::class, 'logger' );
		$property->setAccessible( true );

		$logger = $property->getValue( $sut );

		$this->assertInstanceOf( KLogger::class, $logger );

	}


	/**
	 * If a plugin asks to use the WooCommerce logger, but WooCommerce is inactive, use the default KLogger.
	 *
	 * @covers ::__construct
	 */
	public function tests_woocommerce_inactive_logger() {

		$settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

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
		};

		$api = $this->makeEmpty( API::class );

		assert( $settings instanceof WooCommerce_Logger_Interface );

		// Remove WooCommerce from the active plugins list.
		add_filter(
			'active_plugins',
			function( $active_plugins ) {
				return array_filter(
					$active_plugins,
					function( $element ) {
						return 'woocommerce/woocommerce.php' !== $element;
					}
				);
			},
			999
		);

		$sut = new BH_WP_PSR_Logger( $api, $settings );

		// We can't call wc_get_logger() until WooCommerce is loaded.
		do_action( 'plugins_loaded' );

		$property = new \ReflectionProperty( BH_WP_PSR_Logger::class, 'logger' );
		$property->setAccessible( true );

		$logger = $property->getValue( $sut );

		$this->assertInstanceOf( KLogger::class, $logger );
	}


}
