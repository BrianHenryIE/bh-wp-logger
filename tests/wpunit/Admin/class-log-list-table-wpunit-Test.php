<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Admin\Logs_List_Table
 */
class Logs_List_Table_WPUnit_Test extends \Codeception\TestCase\WPTestCase {


	/**
	 * @covers ::replace_wp_user_id_with_link
	 */
	public function test_replace_wp_user_id(): void {

		$user_1_id = wp_create_user( 'username_1', 'password_1', 'user1@example.org' );
		$user_2_id = wp_create_user( 'username_2', 'password_2', 'user2@example.org' );

		$api      = $this->makeEmpty( API::class );
		$settings = $this->makeEmpty( Logger_Settings_Interface::class );
		// TODO: It would be nice to use ColorLogger here with this.
		$logger = $this->makeEmpty( BH_WP_PSR_Logger::class );

		global $hook_suffix;
		$hook_suffix = '';

		$sut = new Logs_List_Table( $api, $settings, $logger );

		$message = "A log message with wp_user:{$user_1_id} and also with wp_user:{$user_2_id} in it.";

		$result = $sut->replace_wp_user_id_with_link( $message );

		$this->assertStringContainsString( "user-edit.php?user_id={$user_1_id}", $result );
		$this->assertStringContainsString( 'username_1', $result );

		$this->assertStringContainsString( "user-edit.php?user_id={$user_2_id}", $result );
		$this->assertStringContainsString( 'username_2', $result );

	}
}
