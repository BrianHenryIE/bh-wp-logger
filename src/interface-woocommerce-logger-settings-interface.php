<?php
/**
 * Implement this interface in the settings object to indicate the plugin is a WooCommerce plugin.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger;

use BrianHenryIE\WC_Logger\WC_Logger_Settings_Interface;

interface WooCommerce_Logger_Settings_Interface extends Logger_Settings_Interface, WC_Logger_Settings_Interface {}
