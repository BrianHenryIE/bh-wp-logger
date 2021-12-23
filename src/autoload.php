<?php
/**
 * The files here don't strictly follow any convention. It's the WordPress Coding Standards' filename standard,
 * extended to make abstract classes and interfaces easy to identify.
 *
 * `wp-namespace-autoloader` by Pablo dos Santos Gonçalves Pacheco can autoload these files, so one option is to use it.
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader
 *
 * Otherwise, just `require_once path/to/autoload.php`.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\Admin\Admin;
use BrianHenryIE\WP_Logger\Admin\Admin_Notices;
use BrianHenryIE\WP_Logger\Admin\AJAX;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Admin\Logs_Table;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API\Logger_Settings;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Trait;
use BrianHenryIE\WP_Logger\API\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\API\PHP_Shutdown_Handler;
use BrianHenryIE\WP_Logger\API\Plugin_Helper;
use BrianHenryIE\WP_Logger\Includes\Cron;
use BrianHenryIE\WP_Logger\Includes\Functions;
use BrianHenryIE\WP_Logger\Includes\Plugin_Logger_Actions;
use BrianHenryIE\WP_Logger\WooCommerce\Log_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\WC_PSR_Logger;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use WP_List_Table;

$autoload_classmap = array(
	Admin::class                        => __DIR__ . '/Admin/class-admin.php',
	Admin_Notices::class                => __DIR__ . '/Admin/class-admin-notices.php',
	AJAX::class                         => __DIR__ . '/Admin/class-ajax.php',
	API::class                          => __DIR__ . '/API/class-api.php',
	API_Interface::class                => __DIR__ . '/API/interface-api.php',
	BH_WP_PSR_Logger::class             => __DIR__ . '/API/class-bh-wp-psr-logger.php',
	Plugin_Logger_Actions::class        => __DIR__ . '/Includes/class-plugin-logger-actions.php',
	Cron::class                         => __DIR__ . '/Includes/class-cron.php',
	Functions::class                    => __DIR__ . '/Includes/class-functions.php',
	Logger::class                       => __DIR__ . '/class-logger.php',
	Logger_Settings::class              => __DIR__ . '/API/class-logger-settings.php',
	Logger_Settings_Interface::class    => __DIR__ . '/API/interface-logger-settings.php',
	// Logger_Settings_Trait::class=> __DIR__ . '/API/trait-logger-settings.php',
	Log_Handler::class                  => __DIR__ . '/WooCommerce/class-log-handler.php',
	WC_PSR_Logger::class                => __DIR__ . '/WooCommerce/class-wc-psr-logger.php',
	WooCommerce_Logger_Interface::class => __DIR__ . '/WooCommerce/interface-woocommerce-logger.php',
	Logs_Page::class                    => __DIR__ . '/Admin/class-logs-page.php',
	Logs_Table::class                   => __DIR__ . '/Admin/class-logs-table.php',
	PHP_Error_Handler::class            => __DIR__ . '/API/class-php-error-handler.php',
	PHP_Shutdown_Handler::class         => __DIR__ . '/API/class-php-shutdown-handler.php',
	Plugin_Helper::class                => __DIR__ . '/API/class-plugin-helper.php',
	Plugins_Page::class                 => __DIR__ . '/Admin/class-plugins-page.php',
);

$wordpress_classmap = array();
if ( defined( 'ABSPATH' ) ) {
	$wordpress_classmap[ WP_List_Table::class ] = ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

	// TODO: Can we do without this in autoload? presumably better not to include admin files.
	if ( file_exists( ABSPATH . '/wp-admin/includes/plugin.php' ) ) {
		require_once ABSPATH . '/wp-admin/includes/plugin.php';
	}
}

$classmaps = array_merge( $autoload_classmap, $wordpress_classmap );

spl_autoload_register(
	function ( $classname ) use ( $classmaps ) {
		if ( array_key_exists( $classname, $classmaps ) && file_exists( $classmaps[ $classname ] ) ) {
			require_once $classmaps[ $classname ];
		}
	}
);
