# BH WP Logger

Zero-config logger UI for WordPress plugins.

```php
$logger = Logger::instance();
```

Wraps existing [PSR-3](https://www.php-fig.org/psr/psr-3/) loggers and adds some UI.

* [KLogger](https://github.com/katzgrau/KLogger)
* [~~WC_Logger~~](https://github.com/woocommerce/woocommerce/blob/trunk/includes/class-wc-logger.php) (currently unhooked)
* [PSR-3 NullLogger](https://github.com/php-fig/log/blob/master/Psr/Log/NullLogger.php)

Uses KLogger by default, ~~WC_Logger when WooCommerce is active~~, NullLogger when log level is set to "none".

~~Uses PHP's `set_error_handler()` to catch PHP deprecated/warning/notice/errors.~~ (currently unhooked)

## UI 

Displays logs in `WP_List_Table`.

![Logs WP_List_Table](./assets/logs-wp-list-table.png "Logs WP_List_Table")

Show a dismissable admin error notice each time there is a new error.

![Admin Error Notice](./assets/admin-error-notice.png "Admin error notice")

Adds a link to the logs view on the plugin's entry on plugins.php.

![Plugins page logs link](./assets/plugins-page-logs-link.png "Plugins page logs link")


## Use

### Composer

This relies on a fork of [WPTT/admin-notices](https://github.com/WPTT/admin-notices/issues/14) due to issue [#14 "Dismiss" button not persistently dismissing notices](https://github.com/WPTT/admin-notices/issues/14) which I've fixed but maybe not in the best way.

```json
"repositories": [
    {
      "url": "https://github.com/BrianHenryIE/bh-wp-logger",
      "type": "git"
    },
    {
        "url": "https://github.com/BrianHenryIE/admin-notices",
        "type": "git"
    },
},
"require": {
    "brianhenryie/wp-logger": "dev-master"
}
```

### Instantiate

You should use [brianhenryie/strauss](https://github.com/BrianHenryIE/strauss) to prefix the library's namespace. Then Strauss's autoloader will include the files for you. Otherwise:

Include the files:

```php
// Use a PSR-4 autoloader for the bh-wp-logger dependencies.

// Use this for its own files.
require_once '/path/to/bh-wp-logger/autoload.php';
```

The following will work, but it will be faster and more reliable to provide the settings:

```php
$logger = Logger::instance();
```

Provide the settings:

```php
$logger_settings = new class() implements BrianHenryIE\WP_Logger\API\Logger_Settings_Interface {

	public function get_log_level(): string {
		return LogLevel::INFO;
	}

	// This is used in admin notices.
	public function get_plugin_name(): string {
		return "My Plugin Name";
	}

	// This is used in option names and URLs.
	public function get_plugin_slug(): string {
		return "my-plugin-name";
	}

    // This is needed for the plugins.php logs link.
	public function get_plugin_basename(): string {
		return "my-plugin-name/my-plugin-name.php";
	}
};
$logger = Logger::instance( $logger_settings );
```

Then pass around your `$logger` instance; use `NullLogger` in your tests.

After the logger has been instantiated once, subsequent calls to `::instance()` return the existing instance and any `$logger_settings` passed is ignored.

### WooCommerce Settings

Something like this can be used for WooCommerce Settings API.

```php

$log_levels        = array( 'none', LogLevel::ERROR, LogLevel::WARNING, LogLevel::NOTICE, LogLevel::INFO, LogLevel::DEBUG );
$log_levels_option = array();
foreach ( $log_levels as $log_level ) {
    $log_levels_option[ $log_level ] = ucfirst( $log_level );
}

$setting_fields[] = array(
    'title'    => __( 'Log Level', 'text-domain' ),
    'label'    => __( 'Enable Logging', 'text-domain' ),
    'type'     => 'select',
    'options'  => $log_levels_option,
    'desc'     => __( 'Increasingly detailed logging.', 'text-domain' ),
    'desc_tip' => true,
    'default'  => 'notice',
    'id'       => 'text-domain-log-level',
);
```

## WP_Mock

If using WP_Mock for your tests, and you are instantiating this logger, the following should help:

```php
\WP_Mock::userFunction(
    'is_admin',
    array(
        'return_arg' => false
    )
);

\WP_Mock::userFunction(
    'get_current_user_id'
);

\WP_Mock::userFunction(
    'wp_normalize_path',
    array(
        'return_arg' => true
    )
);
```

### Best Practice

From my brief experience using this, I find it useful to add a `debug` log at the beginning of every function and an appropriate `info`...`error` as the function returns.

## TODO

* Auto-delete old logs
* Check log directory is not publicly accessible
* Use [Code prettify](https://github.com/googlearchive/code-prettify) on the context json
* Paging and filtering
* Hyperlinks in messages
* Log notice should dismiss when the log page is visited
* Record timestamp the logs were last viewed at, make the plugins.php link bold if new logs are present.

# Status

Very much a v0.1.