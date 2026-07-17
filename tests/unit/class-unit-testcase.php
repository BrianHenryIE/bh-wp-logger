<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\ColorLogger\ColorLogger;
use Codeception\Test\Unit;
use Psr\Log\LoggerInterface;
use WP_Mock;

class Unit_Testcase extends Unit {

	protected LoggerInterface $logger;

	protected function setUp(): void {
		WP_Mock::setUp();

		\Patchwork\redefine(
			'constant',
			function ( string $constant_name ) {
				return 'DAY_IN_SECONDS' === $constant_name
					? 60 * 60 * 24
					: \Patchwork\relay( func_get_args() );
			}
		);

		$this->logger = new ColorLogger();
	}

	protected function tearDown(): void {
		parent::tearDown();
		WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}
}
