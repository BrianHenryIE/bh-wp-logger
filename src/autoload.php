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

$autoload_classmap = array(
	Logger::class                    => __DIR__ . '/class-logger.php',
	Logs_Table::class                => __DIR__ . '/class-logs-table.php',
	Logger_Settings_Interface::class => __DIR__ . '/interface-logger-settings.php',
);

spl_autoload_register(
	function ( $classname ) use ( $autoload_classmap ) {
		if ( array_key_exists( $classname, $autoload_classmap ) && file_exists( $autoload_classmap[ $classname ] ) ) {
			require_once $autoload_classmap[ $classname ];
		}
	}
);
