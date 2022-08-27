<?php
/**
 * PHPUnit bootstrap file for WP_Mock.
 *
 * @package           BH_WP_Logger
 */

WP_Mock::setUsePatchwork( true );
WP_Mock::bootstrap();

define( 'DAY_IN_SECONDS', 60 * 60 * 24 );
