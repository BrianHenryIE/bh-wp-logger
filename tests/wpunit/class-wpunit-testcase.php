<?php

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\ColorLogger\ColorLogger;
use lucatume\WPBrowser\TestCase\WPTestCase;
use Psr\Log\LoggerInterface;

class WPUnit_Testcase extends WPTestCase {

	/**
	 * @var LoggerInterface|TestLogger $logger
	 */
	protected LoggerInterface $logger;

	protected function setUp(): void {
		parent::setUp();
		$this->logger = new class() extends ColorLogger implements LoggerInterface {};
	}

	protected function get_installed_major_version( string $plugin_basename ): int {
		$plugin_headers = get_plugin_data( codecept_root_dir( WP_PLUGIN_DIR . '/' . $plugin_basename ) );
		if ( 1 === preg_match( '/(\d+)/', $plugin_headers['Version'], $output_array ) ) {
			return (int) $output_array[1];
		} else {
			return -1;
		}
	}

	protected function is_activate_and_major_version( string $plugin_basename, int $major_version ): bool {
		$is_active = is_plugin_active( 'newsletter/plugin.php' );
		if ( ! $is_active ) {
			return false;
		}
		return $this->get_installed_major_version( $plugin_basename ) === $major_version;
	}
}
