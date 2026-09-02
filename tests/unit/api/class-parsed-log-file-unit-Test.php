<?php

namespace BrianHenryIE\WP_Logger\API;

use BrianHenryIE\WP_Logger\Unit_Testcase;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\API\Parsed_Log_File
 */
class Parsed_Log_File_Unit_Test extends Unit_Testcase {

	/**
	 * @covers ::__construct
	 * @covers ::is_truncated
	 */
	public function test_is_truncated_when_entries_were_omitted(): void {

		$entry = array(
			'time'     => '2026-08-23T00:00:00+00:00',
			'datetime' => null,
			'level'    => 'debug',
			'message'  => 'message',
			'context'  => null,
		);

		$complete = new Parsed_Log_File( array( $entry ), 1, array( 'debug' => 1 ) );
		$this->assertFalse( $complete->is_truncated() );

		$truncated = new Parsed_Log_File( array( $entry ), 5, array( 'debug' => 5 ) );
		$this->assertTrue( $truncated->is_truncated() );
	}
}
