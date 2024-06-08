# Changelog

== 0.4 ==

* Fix: fatal error in brianhenryie/bh-wp-private-uploads
* Fix: fatal error when using `Logger_Settings_Trait` default plugin name
* Add: WP CLI command to log context
* Add: WP CLI commands to delete logs

== 0.3 == 2023-04-11 ==

* Use new WPTT Admin Notices bugfix patch https://github.com/WPTT/admin-notices/pull/15
* Fix: JavaScript dependencies (`wp_enqueue_script` `$deps` array)
* Fix: Move `alleyinteractive/wordpress-autoloader` to `autoload-dev`
* Do not add actions and filters when log level is none (temp fix, needs finesse)
* Removed `Logger_Settings` and `Plugins` classes in favour of much improved/simplified `Logger_Settings_Trait` to infer defaults (WIP)
* Performance: Conditionally add WordPress `doing_it_wrong`, `deprecated_function`, etc. logging
* Performance: Cache all backtraces, and share caches between all `bh-wp-logger` instances
* Add: WordPress `doing_it_wrong`, `deprecated_function` etc. test buttons in test-plugin
* Improved WPCS, PhpStan

74 PhpUnit tests, ~48% coverage.

== 0.2 == 2023-03-02 ==

* Fix: Test plugin loading assets
* Add: auto-size date column, allow resizing all columns
* Add: Format context JSON with show/hide controls
* Add: Checkboxes to filter rows by log level

https://github.com/caldwell/renderjson

https://github.com/alvaro-prieto/colResizable

