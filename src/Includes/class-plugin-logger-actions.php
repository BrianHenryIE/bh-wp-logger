<?php
/**
 *
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Includes;

use BrianHenryIE\WP_Logger\Admin\Admin;
use BrianHenryIE\WP_Logger\Admin\Admin_Notices;
use BrianHenryIE\WP_Logger\Admin\AJAX;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\WooCommerce\Log_Handler;
use BrianHenryIE\WP_Logger\WooCommerce\WooCommerce_Logger_Interface;
use Katzgrau\KLogger\Logger as KLogger;
use Psr\Log\AbstractLogger;
use BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\PHP\PHP_Shutdown_Handler;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;
use WC_Logger_Interface;
use WP_CLI;

/**
 *
 * @see WC_Logger
 * @see https://www.php-fig.org/psr/psr-3/
 */
class Plugin_Logger_Actions {

	protected LoggerInterface $wrapped_real_logger;

	protected Logger_Settings_Interface $settings;

	protected API_Interface $api;

	/**
	 * Logger constructor.
	 *
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $wrapped_real_logger ) {
		$this->wrapped_real_logger = $wrapped_real_logger;
		$this->settings            = $settings;
		$this->api                 = $api;

		$this->add_error_handler_hooks();
		$this->add_wordpress_error_handling_hooks();

		$this->add_admin_ui_logs_page_hooks();
		$this->add_admin_notices_hooks();
		$this->add_ajax_hooks();
		$this->add_plugins_page_hooks();
	}

	/**
	 * Add error handling for PHP errors and shutdowns.
	 */
	protected function add_error_handler_hooks(): void {

		$php_error_handler = new PHP_Error_Handler( $this->api, $this->settings, $this->wrapped_real_logger );
		add_action( 'plugins_loaded', array( $php_error_handler, 'init' ), 2 );

		$php_shutdown_handler = new PHP_Shutdown_Handler( $this->api, $this->settings, $this->wrapped_real_logger );
		add_action( 'plugins_loaded', array( $php_shutdown_handler, 'init' ), 2 );
	}

	/**
	 * Add hooks to WordPress's handling of deprecated functions etc. in order to log it ourselves.
	 */
	protected function add_wordpress_error_handling_hooks(): void {

		$functions = new Functions( $this->api, $this->settings, $this->wrapped_real_logger );

		add_action( 'deprecated_function_run', array( $functions, 'log_deprecated_functions_only_once_per_day' ), 10, 3 );
		add_action( 'deprecated_argument_run', array( $functions, 'log_deprecated_arguments_only_once_per_day' ), 10, 3 );
		add_action( 'doing_it_wrong_run', array( $functions, 'log_doing_it_wrong_only_once_per_day' ), 10, 3 );
		add_action( 'deprecated_hook_run', array( $functions, 'log_deprecated_hook_only_once_per_day' ), 10, 4 );
	}

	/**
	 * Register dismissable admin notices for recorded logs.
	 */
	protected function add_admin_notices_hooks(): void {

		$admin_notices = new Admin_Notices( $this->api, $this->settings );
		// Generate the notices from wp_options.
		add_action( 'admin_init', array( $admin_notices, 'admin_notices' ), 9 );
		// Add the notice.
		add_action( 'admin_notices', array( $admin_notices, 'the_notices' ) );
		// Print the script to the footer.
		add_action( 'admin_init', array( $admin_notices, 'register_scripts' ) );
	}

	/**
	 * Add an admin UI page to display the logs table.
	 * Enqueue the JavaScript for handling the buttons.
	 */
	protected function add_admin_ui_logs_page_hooks(): void {

		$logs_page = new Logs_Page( $this->api, $this->settings );
		add_action( 'admin_menu', array( $logs_page, 'add_page' ) );
		add_action( 'admin_enqueue_scripts', array( $logs_page, 'enqueue_styles' ) );

		$admin = new Admin( $this->settings );
		add_action( 'admin_enqueue_scripts', array( $admin, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue AJAX handlers for the logs page's buttons.
	 */
	protected function add_ajax_hooks(): void {

		$ajax = new AJAX( $this->api, $this->settings );

		add_action( 'wp_ajax_bh_wp_logger_logs_delete', array( $ajax, 'delete' ) );
		add_action( 'wp_ajax_bh_wp_logger_logs_delete_all', array( $ajax, 'delete_all' ) );
	}

	/**
	 * Add link on plugins.php to the logs page.
	 */
	protected function add_plugins_page_hooks(): void {

		$plugins_page = new Plugins_Page( $this->api, $this->settings );

		$hook = "plugin_action_links_{$this->settings->get_plugin_basename()}";
		add_filter( $hook, array( $plugins_page, 'add_logs_action_link' ), 10, 4 );
	}

}
