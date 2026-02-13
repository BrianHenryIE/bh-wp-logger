<?php
/**
 * Add the WordPress hooks and filters.
 *
 * @package brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\WP_Includes;

use BrianHenryIE\WP_Logger\Admin\Admin_Notices;
use BrianHenryIE\WP_Logger\Admin\AJAX;
use BrianHenryIE\WP_Logger\Admin\Logs_Page;
use BrianHenryIE\WP_Logger\Admin\Plugin_Installer;
use BrianHenryIE\WP_Logger\Admin\Plugins_Page;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use BrianHenryIE\WP_Logger\PHP\PHP_Error_Handler;
use BrianHenryIE\WP_Logger\PHP\PHP_Shutdown_Handler;
use BrianHenryIE\WP_Logger\Private_Uploads\URL_Is_Public;

/**
 * Just uses add_action and add_filter.
 *
 * @see WC_Logger
 * @see https://www.php-fig.org/psr/psr-3/
 */
class Plugin_Logger_Actions {

	/**
	 * The library object that acts as a facade to the true logger.
	 *
	 * @var BH_WP_PSR_Logger
	 */
	protected BH_WP_PSR_Logger $wrapped_real_logger;

	/**
	 * Settings object for instantiating classes.
	 *
	 * @var Logger_Settings_Interface
	 */
	protected Logger_Settings_Interface $settings;

	/**
	 * API object for instantiating classes.
	 *
	 * @var API_Interface
	 */
	protected API_Interface $api;

	/**
	 * Logger constructor.
	 *
	 * @param API_Interface             $api The main utility class.
	 * @param Logger_Settings_Interface $settings The log level etc. for this plugin.
	 * @param BH_WP_PSR_Logger          $wrapped_real_logger A facade of the real logger.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $wrapped_real_logger ) {
		$this->wrapped_real_logger = $wrapped_real_logger;
		$this->settings            = $settings;
		$this->api                 = $api;

		$this->add_error_handler_hooks();

		$this->add_admin_ui_logs_page_hooks();
		$this->add_admin_notices_hooks();
		$this->add_ajax_hooks();
		$this->add_plugins_page_hooks();
		$this->add_plugin_installer_page_hooks();
		$this->add_cron_hooks();
		$this->add_private_uploads_hooks();
		$this->define_init_hooks();
		$this->define_cli_hooks();

		if ( ! $this->is_wp_debug() ) {
			return;
		}

		$this->add_wordpress_error_handling_hooks();
	}

	/**
	 *
	 *
	 * Defined as a protected function so it can be overridden.
	 * Caution: only consider overriding this when running on your own site. This
	 * adds significant inefficiency.
	 */
	protected function is_wp_debug(): bool {
		return defined( 'WP_DEBUG' ) && WP_DEBUG;
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
	 *
	 * Only runs in WP_DEBUG because this runs a backtrace on every entry to the error log, which can be significant
	 * when other plugins are not "clean".
	 */
	protected function add_wordpress_error_handling_hooks(): void {

		if ( ! $this->is_wp_debug() ) {
			return;
		}

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
	}

	/**
	 * Add an admin UI page to display the logs table.
	 * Enqueue the JavaScript for handling the buttons.
	 */
	protected function add_admin_ui_logs_page_hooks(): void {

		$logs_page = new Logs_Page( $this->api, $this->settings, $this->wrapped_real_logger );
		add_action( 'admin_menu', array( $logs_page, 'add_page' ), 20 );
		add_action( 'admin_enqueue_scripts', array( $logs_page, 'enqueue_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $logs_page, 'enqueue_styles' ) );
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
		add_filter( $hook, array( $plugins_page, 'add_logs_action_link' ), 99, 4 );
	}

	/**
	 * Add link to Logs on the plugin installer page (after installing a plugin via .zip file).
	 *
	 * Hooked late because the logs link should be last.
	 */
	protected function add_plugin_installer_page_hooks(): void {

		$plugin_installer = new Plugin_Installer( $this->settings );

		add_filter( 'install_plugin_complete_actions', array( $plugin_installer, 'add_logs_link' ), 99, 3 );
	}

	/**
	 * Schedule a job to clean up logs.
	 */
	protected function add_cron_hooks(): void {

		$cron = new Cron( $this->api, $this->settings, $this->wrapped_real_logger );

		add_action( 'init', array( $cron, 'register_delete_logs_cron_job' ) );
		add_action( 'delete_logs_' . $this->settings->get_plugin_slug(), array( $cron, 'delete_old_logs' ) );
	}

	/**
	 * Add filter to change the admin notice when the logs directory is publicly accessible.
	 *
	 * @see \BrianHenryIE\WP_Private_Uploads\Admin\Admin_Notices::admin_notices()
	 */
	protected function add_private_uploads_hooks(): void {

		$url_is_public = new URL_Is_Public();

		add_filter( "bh_wp_private_uploads_url_is_public_warning_{$this->settings->get_plugin_slug()}_logger", array( $url_is_public, 'change_warning_message' ), 10, 2 );
	}

	/**
	 * Hook in to init to download log files.
	 */
	protected function define_init_hooks(): void {

		$init = new Init( $this->api, $this->settings, $this->wrapped_real_logger );

		add_action( 'init', array( $init, 'maybe_download_log' ) );
	}

	/**
	 * Add CLI commands to delete logs.
	 *
	 * Use `null` to disable CLI commands.
	 * The settings trait uses the plugin slug as the default CLI base.
	 *
	 * @see Logger_Settings_Trait::get_cli_base()
	 */
	protected function define_cli_hooks(): void {

		$cli_base = $this->settings->get_cli_base();

		if ( is_null( $cli_base ) ) {
			return;
		}

		$cli = new CLI( $this->api, $this->settings, $this->wrapped_real_logger );

		add_action( 'cli_init', array( $cli, 'register_commands' ) );
	}
}
