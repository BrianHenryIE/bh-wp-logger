# Saved Data

Everywhere bh-wp-logger persists data, for a plugin whose slug is `my-plugin` (so `get_plugin_slug_underscored()` is `my_plugin`).

The library has no uninstall or deactivation routine: none of the following is removed when the plugin using it is deactivated or deleted.

## Files

| Path | Written by | Notes |
|---|---|---|
| `wp-content/uploads/logs/my-plugin/my-plugin-YYYY-MM-DD.log` | Monolog `StreamHandler` in `Logger::__construct()` | One file per UTC day. Not used when the settings implement `WooCommerce_Logger_Settings_Interface` and WooCommerce is active: WooCommerce's logger writes to its own log storage (by default `wp-content/uploads/wc-logs/`) instead. Files older than a month, and empty files, are deleted by the daily cron job (`API::delete_old_logs()`). Individual files, or all of them, can be deleted from the logs page or with WP-CLI. |
| `wp-content/uploads/logs/my-plugin/index.php` | bh-wp-private-uploads `API::create_directory()` | Guard file to prevent directory listing. The directory itself is created by this or by Monolog, whichever writes first. |
| `.htaccess` (site root) | bh-wp-private-uploads `WP_Rewrite` | A rewrite rule routing requests for the logs directory through WordPress so they are not served publicly. Written by flushing rewrite rules on an admin page load when the rule is missing, only on single-site Apache installs using pretty permalinks with a writable `.htaccess`. |

Log entries contain the message and context the plugin logs, which can include personal data. `error` and `warning` entries, and every entry when the log level is `debug`, also include a `debug_backtrace` and the current WordPress filters.

## wp_options

| Option | Written by | Notes |
|---|---|---|
| `my_plugin_recent_error_data` | `BH_WP_PSR_Logger::log()` | `array{message: string, timestamp: int}` of the most recent `error` level log (not `critical`, `alert` or `emergency`). Read by `Admin_Notices` to show a "recent error" admin notice. Deleted when the logs page is viewed or the notice is dismissed. |
| `my_plugin_last_logs_view_time` | `API::set_last_logs_view_time()` | ATOM date string of when the logs page was last viewed. Used to highlight the plugins page Logs link and decide whether to show the admin notice. |
| `wptrt_notice_dismissed_my_plugin_recent_error` | wptrt/admin-notices, on dismissing the recent-error notice | Never actually saved: a `pre_update_option_` filter in `Admin_Notices` deletes it and `my_plugin_recent_error_data` instead. |
| `my_plugin_log_level` | Not written by this library | Read by `Logger_Settings_Trait::get_log_level()`. The consuming plugin may save it, e.g. from its own settings page. |
| `rewrite_rules` | WordPress, when bh-wp-private-uploads flushes rewrite rules | Regenerated to include the logs directory rule; see `.htaccess` above. |
| `cron` | WordPress cron | Holds the scheduled events listed under [Cron events](#cron-events). |

## Transients

All transients are stored in `wp_options` (as `_transient_{name}` / `_transient_timeout_{name}`) unless an external object cache is in use.

| Transient | Written by | Expiry | Notes |
|---|---|---|---|
| `my_plugin_last_log_time` | `API::get_last_log_time()` | 1 day | ATOM date string of the modified time of the most recent log file, cached to avoid filesystem calls. Deleted every time a log is written. Only set for users with `activate_plugins`. |
| `my_plugin_logged_{psr-level}_{md5(errstr)}` | `PHP_Error_Handler::plugin_error_handler()` | 1 day | JSON of the PHP error handler arguments. Used so each distinct PHP error from the plugin is logged at most once per day. |
| `log_deprecated_function_{function}_my_plugin` | `Functions::log_deprecated_functions_only_once_per_day()` | 1 day | The log message. Limits `_deprecated_function()` logs to once per day. |
| `log_deprecated_argument_{function}_my_plugin` | `Functions::log_deprecated_arguments_only_once_per_day()` | 1 day | As above, for `_deprecated_argument()`. |
| `log_doing_it_wrong_run_{function}_my_plugin` | `Functions::log_doing_it_wrong_only_once_per_day()` | 1 day | As above, for `_doing_it_wrong()`. |
| `log_deprecated_hook_run_{hook}_my_plugin` | `Functions::log_deprecated_hook_only_once_per_day()` | 1 day | As above, for `_deprecated_hook()`. |
| `bh_wp_private_uploads__is_private` | bh-wp-private-uploads `API::check_and_update_is_url_private()` | 1 hour + 60s | Serialized `Is_Private_Result` of the check that the logs directory is not publicly accessible. |

## Cron events

Stored in the `cron` option.

| Hook | Scheduled by | Notes |
|---|---|---|
| `delete_logs_my_plugin` | `Cron::register_delete_logs_cron_job()` | Daily, from the next midnight UTC. Deletes old and empty log files. Not scheduled for the WooCommerce logger. |
| `my_plugin_logger_private_uploads_check_url_` | bh-wp-private-uploads `Cron` | Hourly, plus single events, to check the logs directory is not publicly accessible. |
| `my_plugin_logger_private_uploads_unsnooze_dismissed_notice_` | bh-wp-private-uploads `Admin_Notices::on_dismiss()` | Single event one week after the "directory is publicly accessible" notice is dismissed. |

## bh-wp-private-uploads instance

The logger runs its own instance of [bh-wp-private-uploads](https://github.com/BrianHenryIE/bh-wp-private-uploads) to protect the logs directory, configured in `Logger::__construct()` with plugin slug `my-plugin_logger`, uploads subdirectory `logs/my-plugin` and an empty post type name. Its cron hooks and wp_options are listed above, along with:

| Option | Notes |
|---|---|
| `wptrt_notice_dismissed_-private-uploads-url-is-public` | Saved when the "directory is publicly accessible" notice is dismissed; deleted by the unsnooze cron event. |

Because the post type name is empty, the `bh_wp_private_uploads__is_private` transient and the `wptrt_notice_dismissed_-private-uploads-url-is-public` option do not include the plugin slug. Every plugin using this logger shares them.
