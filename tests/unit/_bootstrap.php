<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package           BH_WP_Logger
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

define( 'DAY_IN_SECONDS', 60 * 60 * 24 );

$class_map = array(
	'WP_Error' => codecept_root_dir('wordpress/wp-includes/class-wp-error.php'),
	'WP_Filesystem_Base' => codecept_root_dir('wordpress/wp-admin/includes/class-wp-filesystem-base.php'),
	'WP_Filesystem_Direct' => codecept_root_dir('wordpress/wp-admin/includes/class-wp-filesystem-direct.php'),
);

spl_autoload_register(
	function ( $classname ) use ( $class_map ) {

		if ( array_key_exists( $classname, $class_map ) && file_exists( $class_map[ $classname ] ) ) {
			require_once $class_map[ $classname ];
		}
	}
);
