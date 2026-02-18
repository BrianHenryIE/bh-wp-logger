<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\ColorLogger\ColorLogger;
use Psr\Log\LoggerInterface;
use WP_Mock;

class Unit_Testcase extends \Codeception\Test\Unit {

	protected LoggerInterface $logger;

	protected function setUp(): void {
		WP_Mock::setUp();

		$this->logger = new ColorLogger();
	}

	protected function tearDown(): void {
		parent::_tearDown();
		WP_Mock::tearDown();
		\Patchwork\restoreAll();
	}
}
