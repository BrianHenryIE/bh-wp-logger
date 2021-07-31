<?php

namespace BH_WP_Logger_Test_Plugin\Includes;

use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\Includes\BH_WP_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\LogLevel;
use WC_Log_Levels;
use WC_Logger_Interface;

class BH_WP_Logger_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

    /**
     * When WooCommerce is active and the plugin uses the WooCommerce_Logger_Interface marker to indicate we should
     * use wc_logger, check the correct logger is used.
     *
     * @see wc_get_logger()
     * @see WooCommerce_Logger_Interface
     */
    public function tests_woocommerce_logger() {

        $settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

            public function get_log_level(): string
            {
                return LogLevel::DEBUG;
            }

            public function get_plugin_name(): string
            {
                return 'Test';
            }

            public function get_plugin_slug(): string
            {
                return 'test';
            }

            public function get_plugin_basename(): string
            {
                return 'test/test.php';
            }
        };

        $api = $this->makeEmpty( API::class );

        assert( $settings instanceof WooCommerce_Logger_Interface );

        $sut = new BH_WP_Logger( $api, $settings );

        // We can't call wc_get_logger() until WooCommerce is loaded.
        do_action('plugins_loaded' );

        $property = new \ReflectionProperty(BH_WP_Logger::class, 'logger');
        $property->setAccessible(true);

        $logger = $property->getValue($sut);

        $this->assertInstanceOf( WC_Logger_Interface::class, $logger );
    }

    /**
     * For a non-WooCommerce logger, Klogger should be used.
     */
    public function tests_regular_logger() {

        $settings = new class() implements Logger_Settings_Interface {

            public function get_log_level(): string
            {
                return LogLevel::DEBUG;
            }

            public function get_plugin_name(): string
            {
                return 'Test';
            }

            public function get_plugin_slug(): string
            {
                return 'test';
            }

            public function get_plugin_basename(): string
            {
                return 'test/test.php';
            }
        };

        $api = $this->makeEmpty( API::class );

        assert( !($settings instanceof WooCommerce_Logger_Interface ));

        $sut = new BH_WP_Logger( $api, $settings );

        // We can't call wc_get_logger() until WooCommerce is loaded.
        do_action('plugins_loaded' );

        $property = new \ReflectionProperty(BH_WP_Logger::class, 'logger');
        $property->setAccessible(true);

        $logger = $property->getValue($sut);

        $this->assertInstanceOf( KLogger::class, $logger );

    }


    /**
     * If a plugin asks to use the WooCommerce logger, but WooCommerce is inactive, use the default KLogger.
     */
    public function tests_woocommerce_inactive_logger() {

        $settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

            public function get_log_level(): string
            {
                return LogLevel::DEBUG;
            }

            public function get_plugin_name(): string
            {
                return 'Test';
            }

            public function get_plugin_slug(): string
            {
                return 'test';
            }

            public function get_plugin_basename(): string
            {
                return 'test/test.php';
            }
        };

        $api = $this->makeEmpty( API::class );

        assert( $settings instanceof WooCommerce_Logger_Interface );

        // Remove WooCommerce from the active plugins list.
        add_filter( 'active_plugins', function( $active_plugins ){
            return array_filter( $active_plugins, function( $element ) { return 'woocommerce/woocommerce.php' !== $element; });
        }, 999 );

        $sut = new BH_WP_Logger( $api, $settings );

        // We can't call wc_get_logger() until WooCommerce is loaded.
        do_action('plugins_loaded' );

        $property = new \ReflectionProperty(BH_WP_Logger::class, 'logger');
        $property->setAccessible(true);

        $logger = $property->getValue($sut);

        $this->assertInstanceOf( KLogger::class, $logger );
    }

    /**
     * While we wait for WooCommerce to be loaded, the logger should be null.
     */
    public function tests_woocommerce_logger_before_plugins_loaded() {

        $settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

            public function get_log_level(): string
            {
                return LogLevel::DEBUG;
            }

            public function get_plugin_name(): string
            {
                return 'Test';
            }

            public function get_plugin_slug(): string
            {
                return 'test';
            }

            public function get_plugin_basename(): string
            {
                return 'test/test.php';
            }
        };

        $api = $this->makeEmpty( API::class );

        assert( $settings instanceof WooCommerce_Logger_Interface );

        $sut = new BH_WP_Logger( $api, $settings );

        $property = new \ReflectionProperty(BH_WP_Logger::class, 'logger');
        $property->setAccessible(true);

        $logger = $property->getValue($sut);

        $this->assertNull( $logger );

    }


    /**
     * While we wait for WooCommerce to be loaded, the logger should be null.
     *
     * But the logs should be enqueued.
     *
     * Test the logs made before plugins_loaded are recorded after plugins_loaded.
     */
    public function tests_woocommerce_logger_log_on_plugins_loaded() {

        $settings = new class() implements Logger_Settings_Interface, WooCommerce_Logger_Interface {

            public function get_log_level(): string
            {
                return LogLevel::DEBUG;
            }

            public function get_plugin_name(): string
            {
                return 'Test';
            }

            public function get_plugin_slug(): string
            {
                return 'test';
            }

            public function get_plugin_basename(): string
            {
                return 'test/test.php';
            }
        };

        $api = $this->makeEmpty( API::class );

        assert( $settings instanceof WooCommerce_Logger_Interface );

        $sut = new BH_WP_Logger( $api, $settings );

        $property = new \ReflectionProperty(BH_WP_Logger::class, 'logger');
        $property->setAccessible(true);

        $logger = $property->getValue($sut);

        assert( is_null( $logger ) );

        $mock_logger = new class() implements WC_Logger_Interface {

            public $log_message;

            public function add($handle, $message, $level = WC_Log_Levels::NOTICE)
            {
                // TODO: Implement add() method.
            }

            public function log($level, $message, $context = array())
            {
                $this->log_message = $message;
            }

            public function emergency($message, $context = array())
            {

            }

            public function alert($message, $context = array())
            {
                // TODO: Implement alert() method.
            }

            public function critical($message, $context = array())
            {
                // TODO: Implement critical() method.
            }

            public function error($message, $context = array())
            {
                $this->log( 'emergency', $message, $context );
            }

            public function warning($message, $context = array())
            {
                // TODO: Implement warning() method.
            }

            public function notice($message, $context = array())
            {
                // TODO: Implement notice() method.
            }

            public function info($message, $context = array())
            {
                // TODO: Implement info() method.
            }

            public function debug($message, $context = array())
            {
                // TODO: Implement debug() method.
            }
        };

        /**
         * wc_get_logger has a filter to allow substitution another class. We don't use it in general, because it
         * would apply to every plugin on the site. But we'll use a mock for the tests.
         */
        add_filter( 'woocommerce_logging_class', function( $class_name ) use ( $mock_logger ) {
            return  $mock_logger ;
        } );

        // This should be enqueued until plugins_loaded
        $sut->error('error log message' );

        do_action('plugins_loaded' );

        $this->assertEquals( 'error log message', $mock_logger->log_message );

    }


}
