<?php

namespace BrianHenryIE\WP_Logger\WP_Includes;

/**
 * @coversDefaultClass \BrianHenryIE\WP_Logger\WP_Includes\Plugins
 */
class Plugins_WPUnit_Test extends \Codeception\TestCase\WPTestCase {

	/**
	 * @covers ::get_plugin_data_from_slug
	 */
	public function test_get_plugin_data_from_slug():void {

		$cache_plugins = array(
			'bh-wp-logger-test-plugin/bh-wp-logger-test-plugin.php' =>
				array(
					'WC requires at least' => '',
					'WC tested up to'      => '',
					'Woo'                  => '',
					'Name'                 => 'BH WP Logger Test Plugin',
					'PluginURI'            => 'http://github.com/username/bh-wp-logger-test-plugin/',
					'Version'              => '1.0.0',
					'Description'          => 'This is a short description of what the plugin does. It\'s displayed in the WordPress admin area.',
					'Author'               => 'Brian Henry',
					'AuthorURI'            => 'http://example.com/',
					'TextDomain'           => 'bh-wp-logger-test-plugin',
					'DomainPath'           => '/languages',
					'Network'              => false,
					'RequiresWP'           => '',
					'RequiresPHP'          => '',
					'UpdateURI'            => '',
					'Title'                => 'BH WP Logger Test Plugin',
					'AuthorName'           => 'Brian Henry',
				),
			'woocommerce/woocommerce.php' =>
				array(
					'WC requires at least' => '',
					'WC tested up to'      => '',
					'Woo'                  => '',
					'Name'                 => 'WooCommerce',
					'PluginURI'            => 'https://woocommerce.com/',
					'Version'              => '6.1.1',
					'Description'          => 'An eCommerce toolkit that helps you sell anything. Beautifully.',
					'Author'               => 'Automattic',
					'AuthorURI'            => 'https://woocommerce.com',
					'TextDomain'           => 'woocommerce',
					'DomainPath'           => '/i18n/languages/',
					'Network'              => false,
					'RequiresWP'           => '5.6',
					'RequiresPHP'          => '7.0',
					'UpdateURI'            => '',
					'Title'                => 'WooCommerce',
					'AuthorName'           => 'Automattic',
				),
		);

		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		$sut = new Plugins();

		$result = $sut->get_plugin_data_from_slug( 'bh-wp-logger-test-plugin' );

		$this->assertIsArray( $result );
		$this->assertEquals( 'BH WP Logger Test Plugin', $result['Name'] );

	}

	/**
	 * PhpStan is insisting it returns array always.
	 * "Method BrianHenryIE\WP_Logger\WP_Includes\Plugins::get_plugin_data_from_slug() should return array<string, mixed>|null but returns array."
	 *
	 * @covers ::get_plugin_data_from_slug
	 */
	public function test_get_plugin_data_from_slug_null():void {

		$cache_plugins = array(
			'bh-wp-logger-test-plugin/bh-wp-logger-test-plugin.php' =>
				array(
					'Name'        => 'BH WP Logger Test Plugin',
					'PluginURI'   => 'http://github.com/username/bh-wp-logger-test-plugin/',
					'Version'     => '1.0.0',
					'Description' => 'This is a short description of what the plugin does. It\'s displayed in the WordPress admin area.',
					'Author'      => 'Brian Henry',
					'AuthorURI'   => 'http://example.com/',
					'TextDomain'  => 'bh-wp-logger-test-plugin',
					'DomainPath'  => '/languages',
					'Title'       => 'BH WP Logger Test Plugin',
					'AuthorName'  => 'Brian Henry',
				),
			'woocommerce/woocommerce.php' =>
				array(
					'Name'        => 'WooCommerce',
					'PluginURI'   => 'https://woocommerce.com/',
					'Version'     => '6.1.1',
					'Description' => 'An eCommerce toolkit that helps you sell anything. Beautifully.',
					'Author'      => 'Automattic',
					'AuthorURI'   => 'https://woocommerce.com',
					'TextDomain'  => 'woocommerce',
					'DomainPath'  => '/i18n/languages/',
					'Network'     => false,
					'RequiresWP'  => '5.6',
					'RequiresPHP' => '7.0',
					'Title'       => 'WooCommerce',
					'AuthorName'  => 'Automattic',
				),
		);

		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		$sut = new Plugins();

		$result = $sut->get_plugin_data_from_slug( 'not-bh-wp-logger-test-plugin' );

		$this->assertNull( $result );
	}

	/**
	 * @covers ::discover_plugin_relative_directory
	 */
	public function test_discover_plugin_relative_directory(): void {

		global $wp_plugin_paths;
		$wp_plugin_paths = array(
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/admin-menu-editor' => '/Users/brianhenry/Sites/bh-wp-logger/wp-content/plugins/admin-menu-editor',
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger' => '/Users/brianhenry/Sites/bh-wp-logger',
		);

		$sut = new Plugins();

		$normal_plugin_file = WP_PLUGIN_DIR . '/bh-wp-logger/subdir/file.php';

		$result = $sut->discover_plugin_relative_directory( $normal_plugin_file );

		$this->assertEquals( 'bh-wp-logger', $result );

	}

	/**
	 * @covers ::discover_plugin_relative_directory
	 */
	public function test_discover_symlinked_plugin_relative_directory(): void {

		global $wp_plugin_paths;
		$wp_plugin_paths = array(
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/admin-menu-editor' => '/Users/brianhenry/Sites/bh-wp-logger/wp-content/plugins/admin-menu-editor',
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger' => '/Users/brianhenry/Sites/bh-wp-logger',
		);

		$sut = new Plugins();

		$normal_plugin_file = '/Users/brianhenry/Sites/bh-wp-logger/subdir/file.php';

		$result = $sut->discover_plugin_relative_directory( $normal_plugin_file );

		$this->assertEquals( 'bh-wp-logger', $result );

	}

	/**
	 * @covers ::discover_plugin_data
	 */
	public function test_discover_plugin_data_simple_null(): void {

		$sut = new Plugins();

		// __DIR__ is /Users/brianhenry/Sites/bh-wp-logger/src/WP_Includes.
		// And $wp_plugin_paths is empty.

		$result = $sut->discover_plugin_data();

		$this->assertNull( $result );
	}

	/**
	 * @covers ::discover_plugin_data
	 */
	public function test_discover_plugin_data_not_found_null(): void {

		global $wp_plugin_paths;
		$wp_plugin_paths = array(
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/admin-menu-editor' => '/Users/brianhenry/Sites/bh-wp-logger/wp-content/plugins/admin-menu-editor',
			'/Users/brianhenry/Sites/bh-wp-logger/wordpress/wp-content/plugins/bh-wp-logger' => '/Users/brianhenry/Sites/bh-wp-logger',
		);

		$sut = new Plugins();

		// __DIR__ is /Users/brianhenry/Sites/bh-wp-logger/src/WP_Includes.

		$cache_plugins = array(
			'bh-wp-logger' => array(),
		);

		wp_cache_set( 'plugins', $cache_plugins, 'plugins' );

		$result = $sut->discover_plugin_data();

		$this->assertNull( $result );
	}
}
