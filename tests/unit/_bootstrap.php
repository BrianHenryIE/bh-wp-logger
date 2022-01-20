<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package           BH_WP_Logger
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

global $plugin_root_dir;
require_once $plugin_root_dir . '/autoload.php';
