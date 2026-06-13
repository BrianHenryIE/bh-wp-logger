<?php

/**
 * Fix.
 *
 * @see plugin_basename
 */
global $wp_plugin_paths;
$plugin_path = codecept_root_dir( 'development-plugin/development-plugin.php' );
$wp_plugin_paths[ WP_PLUGIN_DIR . '/development-plugin/development-plugin.php' ] = $plugin_path;
