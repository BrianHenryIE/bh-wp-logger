# Changelog

## Unreleased

* Fix: out-of-memory error viewing large log files – the logs page now parses the file once, keeps only the most recent 1,000 entries, and caps each entry at 64KB (`API::parse_log_file()`), showing "Showing the most recent X of Y entries" when truncated
* Change: parsed log entries' `context` is now the raw JSON string (decoded per-row for display) instead of a decoded `stdClass` – decoded object trees used ~10x the memory
* Add: `API_Interface::parse_log_file( $filepath, $max_entries, $max_entry_bytes ): Parsed_Log_File` – full-file entry count and per-level counts with bounded memory
* Add: log file size and entry counts displayed on the logs page
* Fix: form-handler redirects used `menu_page_url()`, which returns an empty string in `admin-post.php` context
* Fix: the download link always pointed at the most recent date rather than the displayed date
* Fix: log levels beyond the five displayed (e.g. `critical`) no longer cause an undefined-array-key warning when counting

## 0.3.4 – 2026-07-17

* Require PHP 8.1
* Update: bh-wp-private-uploads
* Fix: infinite loop when an error occurs in a delegate logger
* Fix: display of log entries in table
* Fix: deprecation warnings for `Reflection*::setAccessible()`

## 0.3.3 – 2026-06-12

* `public function error(Stringable|string $message, array $context = []): void`

## 0.3.2 – 2026-06-12

* `composer require psr/log:"^2.0 || ^3.0"`

## 0.3.1 – 2026-06-12

* Loosen `bh-wp-cli-logger` requirement to any newer version (the psr/log requirement will dictate what is installed)

## 0.3.0 – 2026-06-12

* Add: Monolog (replacing KLogger)
* Mute downstream BH Private Uploads debug logs
* Don't show recent error admin_notice on plugin install page

## 0.2.1 - 2026-02-18

* Pass `''` to `strip_tags()` instead of `null`

== 0.0.4 ==

* Fix: fatal error in brianhenryie/bh-wp-private-uploads
* Fix: fatal error when using `Logger_Settings_Trait` default plugin name
* Add: WP CLI command to log context
* Add: WP CLI commands to delete logs

== 0.0.3 == 2023-04-11 ==

* Use new WPTT Admin Notices bugfix patch https://github.com/WPTT/admin-notices/pull/15
* Fix: JavaScript dependencies (`wp_enqueue_script` `$deps` array)
* Fix: Move `alleyinteractive/wordpress-autoloader` to `autoload-dev`
* Do not add actions and filters when log level is none (temp fix, needs finesse)
* Removed `Logger_Settings` and `Plugins` classes in favour of much improved/simplified `Logger_Settings_Trait` to infer defaults (WIP)
* Performance: Conditionally add WordPress `doing_it_wrong`, `deprecated_function`, etc. logging
* Performance: Cache all backtraces, and share caches between all `bh-wp-logger` instances
* Add: WordPress `doing_it_wrong`, `deprecated_function` etc. test buttons in development-plugin
* Improved WPCS, PhpStan

74 PhpUnit tests, ~48% coverage.

== 0.0.2 == 2023-03-02 ==

* Fix: Test plugin loading assets
* Add: auto-size date column, allow resizing all columns
* Add: Format context JSON with show/hide controls
* Add: Checkboxes to filter rows by log level

https://github.com/caldwell/renderjson

https://github.com/alvaro-prieto/colResizable

