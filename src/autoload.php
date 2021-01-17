<?php
/**
 * The files here don't really follow any convention. It's the WordPress Coding Standards' filename standard,
 * extended to make abstract classes and interfaces easy to identify.
 *
 * `wp-namespace-autoloader` by Pablo dos Santos Gonçalves Pacheco can autoload these files, so one option is to use it.
 *
 * @see https://github.com/pablo-sg-pacheco/wp-namespace-autoloader
 *
 * Otherwise, just `reqire_once path/to/autoload.php`.
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WP_Logger\admin\Admin;
use BrianHenryIE\WP_Logger\admin\Logs_Page;
use BrianHenryIE\WP_Logger\admin\Logs_Table;
use BrianHenryIE\WP_Logger\admin\Plugins_Page;
use BrianHenryIE\WP_Logger\api\API;
use BrianHenryIE\WP_Logger\api\API_Interface;
use BrianHenryIE\WP_Logger\api\Logger_Settings;
use BrianHenryIE\WP_Logger\api\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\api\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\api\Plugin_Helper;
use BrianHenryIE\WP_Logger\includes\BH_WP_Logger;
use BrianHenryIE\WP_Logger\includes\Cron;
use BrianHenryIE\WP_Logger\woocommerce\Log_Handler;
use WP_List_Table;

$autoload_classmap = array(
	Admin::class                     => __DIR__ . '/admin/class-admin.php',
	API::class                       => __DIR__ . '/api/class-api.php',
	API_Interface::class             => __DIR__ . '/api/interface-api.php',
	BH_WP_Logger::class              => __DIR__ . '/includes/class-bh-wp-logger.php',
	Cron::class                      => __DIR__ . '/includes/class-cron.php',
	Logger::class                    => __DIR__ . '/class-logger.php',
	Logger_Settings::class           => __DIR__ . '/api/class-logger-settings.php',
	Logger_Settings_Interface::class => __DIR__ . '/api/interface-logger-settings.php',
	Log_Handler::class               => __DIR__ . '/woocommerce/class-log-handler.php',
	Logs_Page::class                 => __DIR__ . '/admin/class-logs-page.php',
	Logs_Table::class                => __DIR__ . '/admin/class-logs-table.php',
	PHP_Error_Handler::class         => __DIR__ . '/api/class-php-error-handler.php',
	Plugin_Helper::class             => __DIR__ . '/api/class-plugin-helper.php',
	Plugins_Page::class              => __DIR__ . '/admin/class-plugins-page.php',
);

$wordpress_classmap = array(
	WP_List_Table::class => ABSPATH . 'wp-admin/includes/class-wp-list-table.php',
);

$classmaps = array_merge( $autoload_classmap, $wordpress_classmap );

spl_autoload_register(
	function ( $classname ) use ( $classmaps ) {
		if ( array_key_exists( $classname, $classmaps ) && file_exists( $classmaps[ $classname ] ) ) {
			require_once wp_normalize_path( $classmaps[ $classname ] );
		}
	}
);

// TODO: Can we do without this in autoload?
require_once ABSPATH . '/wp-admin/includes/plugin.php';
