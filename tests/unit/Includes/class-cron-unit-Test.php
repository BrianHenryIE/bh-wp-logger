<?php

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use Codeception\Stub\Expected;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Includes\Cron
 */
class Cron_Unit_Test extends \Codeception\Test\Unit {

	protected function setUp(): void {
		\WP_Mock::setUp();
	}

	protected function tearDown(): void {
		parent::tearDown();
		\WP_Mock::tearDown();
	}

	/**
	 * @covers ::__construct
	 */
	public function test_cover_constructor(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$cron = new Cron( $api, $settings, $logger );

		$this->assertInstanceOf( Cron::class, $cron );
	}

	/**
	 * @covers ::register_cron_job
	 */
	public function test_register_cron_job(): void {

		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$cron = new Cron( $api, $settings, $logger );

		$cron_hook = 'delete_logs_test-plugin';

		\WP_Mock::userFunction(
			'wp_get_scheduled_event',
			array(
				'args'   => array( $cron_hook ),
				'return' => false,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_schedule_event',
			array(
				'args'  => array( \WP_Mock\Functions::type( 'int' ), 'daily', $cron_hook ),
				'times' => 1,
			)
		);

		$cron->register_cron_job();
	}

	/**
	 * @covers ::register_cron_job
	 */
	public function test_do_not_schedule_if_already_scheduled(): void {
		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = $this->makeEmpty( BH_WP_PSR_Logger::class );

		$cron = new Cron( $api, $settings, $logger );

		$cron_hook = 'delete_logs_test-plugin';

		\WP_Mock::userFunction(
			'wp_get_scheduled_event',
			array(
				'args'   => array( $cron_hook ),
				'return' => true,
				'times'  => 1,
			)
		);

		\WP_Mock::userFunction(
			'wp_schedule_event',
			array(
				'times' => 0,
			)
		);

		$cron->register_cron_job();
	}

	/**
	 * @covers ::register_cron_job
	 */
	public function test_do_not_schedule_for_woocommerce_logger(): void {
		$api      = $this->makeEmpty( API_Interface::class );
		$settings = $this->makeEmpty(
			Logger_Settings_Interface::class,
			array(
				'get_plugin_slug' => 'test-plugin',
			)
		);
		$logger   = $this->makeEmpty(
			BH_WP_PSR_Logger::class,
			array(
				'get_logger' => $this->makeEmpty( WC_PSR_Logger::class ),
			)
		);

		$cron = new Cron( $api, $settings, $logger );

		$cron_hook = 'delete_logs_test-plugin';

		\WP_Mock::userFunction(
			'wp_get_scheduled_event',
			array(
				'args'   => array( $cron_hook ),
				'return' => true,
				'times'  => 0,
			)
		);

		\WP_Mock::userFunction(
			'wp_schedule_event',
			array(
				'times' => 0,
			)
		);

		$cron->register_cron_job();
	}

	/**
	 * @covers ::delete_old_logs
	 */
	public function test_execute_cron(): void {

		$api          = $this->makeEmpty(
			API_Interface::class,
			array(
				'delete_old_logs' => Expected::once(),
			)
		);
		$settings     = $this->makeEmpty( Logger_Settings_Interface::class );
		$color_logger = new ColorLogger();

		$logger = $this->makeEmpty(
			BH_WP_PSR_Logger::class,
			array(
				'logger'     => $color_logger,
				'get_logger' => $color_logger,
				'debug'      => function( $message, $context ) use ( $color_logger ) {
					$color_logger->debug( $message, $context );
				},
			)
		);

		\WP_Mock::userFunction(
			'current_action',
			array(
				'return' => 'testing_cron',
				'times'  => 1,
			)
		);

		$cron = new Cron( $api, $settings, $logger );

		$cron->delete_old_logs();

		$this->assertTrue( $color_logger->hasDebug( 'Executing testing_cron cron job.' ) );
	}

}
