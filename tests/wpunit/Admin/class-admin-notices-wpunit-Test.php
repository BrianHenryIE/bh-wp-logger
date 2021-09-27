<?php

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\ColorLogger\ColorLogger;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;

class Admin_Notices_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

    /**
     * Date should be displayed in UTC and in local time.
     */
    public function test_date_in_notice() {

//        2021-09-13T22:05:11Z UTC

        $api = $this->makeEmpty( API_Interface::class );
        $settings = $this->makeEmpty( Logger_Settings_Interface::class,
        array(
            'get_plugin_slug' => 'bh-wp-logger-tests'
        ));
        $logger = new ColorLogger();

        $option_name = 'bh-wp-logger-tests-recent-error-data';

        $sut = new Admin_Notices( $api, $settings, $logger );




        $sut->admin_notices();


    }

}