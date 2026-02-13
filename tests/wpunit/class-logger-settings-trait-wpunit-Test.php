<?php
/**
 * @see https://core.trac.wordpress.org/ticket/42670
 *
 * @package brianhenryie/bh-wp-logger
 */

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\Logger_Settings_Trait
 */
class Logger_Settings_Trait_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_default_info(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'development-plugin/development-plugin.php';
			}
		};

		self::assertEquals( 'info', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_wp_options(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'development-plugin/development-plugin.php';
			}
		};

		update_option( 'development-plugin_log_level', 'error' );

		self::assertEquals( 'error', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_log_level
	 */
	public function test_get_log_level_no_basename(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				throw new Exception();
			}
		};

		self::assertEquals( 'none', $sut->get_log_level() );
	}

	/**
	 * @covers ::get_plugin_name
	 */
	public function test_get_plugin_name(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'development-plugin/development-plugin.php';
			}
		};

		$plugins_array = array(
			'' => array(
				'development-plugin/development-plugin.php' =>
										array(
											'Name' => 'BH WP Logger Test Plugin',
										),
			),
		);

		wp_cache_set( 'plugins', $plugins_array, 'plugins' );

		self::assertEquals( 'BH WP Logger Test Plugin', $sut->get_plugin_name() );
	}

	/**
	 * @covers ::get_plugin_slug
	 */
	public function test_get_plugin_slug(): void {

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;

			public function get_plugin_basename(): string {
				return 'development-plugin/development-plugin.php';
			}
		};

		self::assertEquals( 'development-plugin', $sut->get_plugin_slug() );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_get_plugin_slug1(): void {

		$this->markTestIncomplete( 'TODO' );

		$plugins_array = array(
			'bh-wp-logger/bh-wp-logger'   =>
				array(
					'Name'        => 'BH WP Logger Test Plugin',
					'PluginURI'   => 'http://github.com/username/bh-wp-logger-development-plugin/',
					'Version'     => '1.0.0',
					'Description' => 'This is a short description of what the plugin does. It\'s displayed in the WordPress admin area.',
					'Title'       => 'BH WP Logger Test Plugin',
				),
			'woocommerce/woocommerce.php' =>
				array(
					'WC requires at least' => '',
					'WC tested up to'      => '',
					'Woo'                  => '',
					'Name'                 => 'WooCommerce',
					'PluginURI'            => 'https://woocommerce.com/',
					'Version'              => '7.4.1',
					'Description'          => 'An eCommerce toolkit that helps you sell anything. Beautifully.',
					'Author'               => 'Automattic',
					'AuthorURI'            => 'https://woocommerce.com',
					'TextDomain'           => 'woocommerce',
					'DomainPath'           => '/i18n/languages/',
					'Network'              => false,
					'RequiresWP'           => '5.9',
					'RequiresPHP'          => '7.2',
					'UpdateURI'            => '',
					'Title'                => 'WooCommerce',
					'AuthorName'           => 'Automattic',
				),
		);

		wp_cache_set( 'plugins', $plugins_array, 'plugins' );

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;
		};

		global $wp_plugin_paths;
		$wp_plugin_paths['/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger'] = '/Users/brianhenry/Sites/bh-wp-logger';

		$result = $sut->get_plugin_slug();

		$this->assertEquals( 'bh-wp-logger', $result );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_get_plugin_slugssss(): void {

		$this->markTestIncomplete( 'TODO' );

		global $wp_plugin_paths;
		$wp_plugin_paths['/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger-development-plugin'] = realpath( '/Users/brianhenry/Sites/bh-wp-logger/development-plugin' );

		$test_file = '/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger-development-plugin/admin/class-admin.php';

		$realpath_test_file = realpath( $test_file );

		// Returns `bh-wp-logger-development-plugin/admin/class-admin.php`.
		$plugin_basename = plugin_basename( $realpath_test_file );

		$plugin_slug = explode( '/', $plugin_basename )[0];
		self::assertEquals( 'bh-wp-logger-development-plugin', $plugin_slug );
	}


	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_discover_symlinked_plugin_relative_directory(): void {

		$this->markTestIncomplete( 'TODO' );

		global $wp_plugin_paths;
		$wp_plugin_paths = array(
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/admin-menu-editor' => '/Users/brianhenry/Sites/bh-wp-logger/wp-content/plugins/admin-menu-editor',
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger' => '/Users/brianhenry/Sites/bh-wp-logger',
		);

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;
		};

		$normal_plugin_file = '/Users/brianhenry/Sites/bh-wp-logger/subdir/file.php';

		$result = $sut->discover_plugin_relative_directory( $normal_plugin_file );

		$this->assertEquals( 'bh-wp-logger', $result );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_discover_plugin_data_simple_null(): void {

		$this->markTestIncomplete( 'TODO' );

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;
		};

		// __DIR__ is /Users/brianhenry/Sites/bh-wp-logger/includes/WP_Includes.
		// And $wp_plugin_paths is empty.

		$result = $sut->discover_plugin_data();

		$this->assertNull( $result );
	}

	/**
	 * @covers ::get_plugin_basename
	 */
	public function test_discover_plugin_data_not_found_null(): void {

		$this->markTestIncomplete( 'TODO' );

		global $wp_plugin_paths;
		$wp_plugin_paths = array(
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/admin-menu-editor' => '/Users/brianhenry/Sites/bh-wp-logger/wp-content/plugins/admin-menu-editor',
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger' => '/Users/brianhenry/Sites/bh-wp-logger',
		);

		$sut = new class() {
			use \BrianHenryIE\WP_Logger\Logger_Settings_Trait;
		};

			// __DIR__ is /Users/brianhenry/Sites/bh-wp-logger/includes/WP_Includes.

		$cache_plugins = array(
			'bh-wp-logger' => array(),
		);

		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		$result = $sut->discover_plugin_data();

		$this->assertNull( $result );
	}
}
