<?php
/**
 * A standard WordPress table to show the time, severity, message and context of each log entry.
 *
 * The dream would someday to have complex filtering on this table. e.g. filter all logs to one request, to one user...
 *
 * Time should show (UTC, local and "five hours ago")
 *
 * @package  brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API\Parsed_Log_File;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use DateTime;
use Exception;
use Psr\Log\LoggerInterface;
use WP_List_Table;
use WP_Post_Type;
use WP_User;

/**
 * WordPress list table for displaying the logs.
 */
class Logs_List_Table extends WP_List_Table {

	/**
	 * Only the most recent entries of a large log file are displayed, keeping memory use (and the
	 * rendered page size) bounded. The full file remains available via the download link.
	 */
	public const MAX_DISPLAYED_ENTRIES = 1000;

	/**
	 * Each displayed entry is capped at this many bytes of log text (an entry's context can contain
	 * enormous data, e.g. a whole email), with a truncation note appended to its message.
	 */
	public const MAX_ENTRY_BYTES = 65536;

	/**
	 * The logs are displayed one day at a time. The most recent day's log file, or the optionally specified date.
	 *
	 * @used-by Logs_List_Table::get_data()
	 *
	 * @var string|null
	 */
	protected ?string $selected_date = null;

	/**
	 * The parsed (capped) log file for the selected date, set by {@see Logs_List_Table::prepare_items()}.
	 */
	protected ?Parsed_Log_File $parsed_log_file = null;

	/**
	 * Logs_Table constructor.
	 *
	 * @see WP_List_Table::__construct()
	 *
	 * @param API_Interface                                                       $api The logger API.
	 * @param Logger_Settings_Interface                                           $settings The logger settings.
	 * @param BH_WP_PSR_Logger|LoggerInterface                                    $logger The logger itself, to use for actual logging. Also passed into a filter.
	 * @param array{plural?:string, singular?:string, ajax?:bool, screen?:string} $args Arguments array from parent class.
	 */
	public function __construct(
		protected API_Interface $api,
		protected Logger_Settings_Interface $settings,
		protected BH_WP_PSR_Logger|LoggerInterface $logger,
		array $args = array()
	) {
		parent::__construct( $args );
	}

	/**
	 * Called before prepare_items() to set for which date logs should be displayed.
	 *
	 * @used-by Logs_Page::display_page()
	 *
	 * @param ?string $ymd_date Date in format 2022-09-28.
	 */
	public function set_date( ?string $ymd_date ): void {
		$this->selected_date = $ymd_date;
	}

	/**
	 * The path to the log file for the selected date, or the most recent log file, or null when there are none.
	 */
	public function get_selected_log_filepath(): ?string {

		$log_files = $this->api->get_log_files();

		if ( empty( $log_files ) ) {
			// TODO: "No logs yet." message. Maybe with "current log level is:".
			return null;
		} elseif ( ! is_null( $this->selected_date ) && isset( $log_files[ $this->selected_date ] ) ) {
			return $log_files[ $this->selected_date ];
		} else {
			return array_pop( $log_files );
		}
	}

	/**
	 * Read the log file and parse the data.
	 *
	 * TODO: Move out of here. This should be a generic PSR-Log-Data class.
	 *
	 * @return array<array{time:string,datetime:?DateTime,level:string,message:string,context:?string}>
	 */
	public function get_data(): array {

		$filepath = $this->get_selected_log_filepath();

		if ( is_null( $filepath ) ) {
			return array();
		}

		return $this->api->parse_log_file( $filepath, self::MAX_DISPLAYED_ENTRIES, self::MAX_ENTRY_BYTES )->entries;
	}

	/**
	 * The parsed (capped) log file, for its entry/level counts. Only set after
	 * {@see Logs_List_Table::prepare_items()} has run.
	 */
	public function get_parsed_log_file(): ?Parsed_Log_File {
		return $this->parsed_log_file;
	}

	/**
	 * Get the list of columns in this table.
	 *
	 * TODO: Add a filter/instructions on how to add a new column.
	 *
	 * @overrides WP_List_Table::get_columns()
	 * @see WP_List_Table::get_columns()
	 *
	 * @return array{level:string, time:string, message:string, context:string} array<column identifier, column title>
	 */
	public function get_columns() {
		return array(
			'level'   => '',
			'time'    => 'Time',
			'message' => 'Message',
			'context' => 'Context',
		);
	}

	/**
	 * @override parent::prepare_items()
	 * @see WP_List_Table::prepare_items()
	 * @return void
	 */
	public function prepare_items() {

		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );

		$filepath = $this->get_selected_log_filepath();

		$this->parsed_log_file = is_null( $filepath )
			? new Parsed_Log_File( array(), 0, array() )
			: $this->api->parse_log_file( $filepath, self::MAX_DISPLAYED_ENTRIES, self::MAX_ENTRY_BYTES );

		$this->items = $this->parsed_log_file->entries;
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @see WP_List_Table::single_row()
	 *
	 * @used-by WP_List_Table::display_rows()
	 *
	 * @param array{time:string, level:string, message:string, context:?string} $item The current item.
	 * @return void
	 */
	public function single_row( $item ) {
		echo '<tr class="level-' . esc_attr( strtolower( $item['level'] ) ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * Get the HTML for a column.
	 *
	 * @param array{time:string, level:string, message:string, context:?string} $item ...whatever type get_data returns.
	 * @param string                                                            $column_name The specified column.
	 *
	 * @see WP_List_Table::column_default()
	 * @see Logs_List_Table::get_data()
	 *
	 * @return string
	 */
	public function column_default( $item, $column_name ) {

		$column_output = '';
		switch ( $column_name ) {
			case 'time':
				$time = $item['time'];

				try {
					$datetime = new DateTime( $time );
					// TODO: Is there a way to know if the site's timezone has never been set properly?
					// TODO: Is it better to use the user's timezone rather than the server timezone?
					$datetime->setTimezone( wp_timezone() );

					// Output in format: 20:02, Saturday, 14 November 2020 (PST).
					$date_formatted = $datetime->format( 'H:i, l, d F Y (T)' );
					$column_output .= $date_formatted;
					$column_output .= '<br/>';
				} catch ( Exception ) {
					$column_output .= 'Could not parse date: ';
				}
				$column_output .= $time;
				$column_output  = '<span class="logs-time">' . $column_output . '</span>';
				break;
			case 'context':
				// The context is stored as its raw JSON string (decoded objects use ~10x the memory
				// across the whole table); decode it one row at a time, only for display.
				if ( ! empty( $item['context'] ) ) {
					$context = json_decode( $item['context'] );
					if ( $context instanceof \stdClass ) {
						unset( $context->source );
						$pretty_context = wp_json_encode( $context, JSON_PRETTY_PRINT );
						// phpcs:disable WordPress.PHP.DisallowShortTernary.Found
						$un_pretty_context = wp_json_encode( $context ) ?: '';
						$column_output     = $pretty_context
							? sprintf(
								'<pre data-json="%s" class="log-context-pre">%s</pre>',
								esc_html( $un_pretty_context ),
								esc_html( trim( $pretty_context, "'\"" ) )
							)
							: esc_html( $item['context'] );
					} else {
						$column_output = esc_html( $item['context'] );
					}
				}
				break;
			case 'message':
				// The "message" is just text.
				$column_output = $item['message'];
				if ( 0 === strpos( $column_output, 'Uncaught ' ) ) {
					$column_output = '<pre style="margin-top:0; overflow-x: auto;">' . esc_html( $column_output ) . '</pre>';
				} else {
					$column_output = esc_html( $column_output );
					$column_output = $this->replace_wp_user_id_with_link( $column_output );
					$column_output = $this->replace_post_type_id_with_link( $column_output );
				}
				break;
			case 'level':
				// The "level" column is just a color bar.
			default:
				// TODO: Log unexpected column name.
				break;
		}

		$logger_settings  = $this->settings;
		$bh_wp_psr_logger = $this->logger;

		$plugin_slug = $this->settings->get_plugin_slug();
		/**
		 * Filter to modify what is printed for the column.
		 * e.g. find and replace wc_order:123 with a link to the order.
		 *
		 * @param string $column_output
		 * @param array{time:string, level:string, message:string, context:?string} $item The log entry row.
		 * @param string $column_name
		 * @param Logger_Settings_Interface $logger_settings
		 * @param BH_WP_PSR_Logger|LoggerInterface $bh_wp_psr_logger
		 */
		$filtered_column_output = apply_filters( "{$plugin_slug}_bh_wp_logger_column", $column_output, $item, $column_name, $logger_settings, $bh_wp_psr_logger );

		return is_string( $filtered_column_output ) ? $filtered_column_output : $column_output; /** @phpstan-ignore function.alreadyNarrowedType */
	}

	/**
	 * Update `wp_user:123` with links to the user profile.
	 *
	 * Public for now. Maybe should be in another class.
	 *
	 * @param string $message The log text to search and replace in.
	 *
	 * @return string
	 */
	public function replace_wp_user_id_with_link( string $message ): string {

		$callback = function ( array $matches ): string {
			/** @var array{0:string,1:numeric-string} $matches */

			$user = get_user_by( 'ID', $matches[1] );

			if ( $user instanceof WP_User ) {
				return sprintf(
					'<a href="%s">%s</a>',
					admin_url( "user-edit.php?user_id={$matches[1]}" ),
					esc_html( $user->user_nicename )
				);
			}

			return $matches[0];
		};

		return preg_replace_callback( '/wp_user:(\d+)/', $callback, $message ) ?? $message;
	}

	/**
	 * Replace references to posts with links to the post edit screen.
	 * E.g. update `shop_order:123` with link to the order called "Order 123".
	 * E.g. update `attachment:456` with link to the attachment called "Media 456".
	 *
	 * The backticks are required.
	 *
	 * @param string $column_output The column output so far.
	 *
	 * @return string
	 */
	public function replace_post_type_id_with_link( string $column_output ): string {

		/**
		 * Get the list of valid post types registered with WordPress.
		 *
		 * @var array<string, WP_Post_Type> $post_types
		 */
		$post_types = get_post_types( array(), 'objects' );

		/**
		 * Filter the list of registered post types to only those with a UI we can link to.
		 *
		 * @var array<string, WP_Post_Type> $post_types_with_ui
		 */
		$post_types_with_ui = array_filter(
			$post_types,
			fn( WP_Post_Type $post_type ) => $post_type->show_ui
		);

		/** @param string[] $matches */
		$callback = function ( array $matches ) use ( $post_types_with_ui ): string {
			/** @var array{0:string,1:string,2:numeric-string} $matches} */

			if ( ! isset( $post_types_with_ui[ $matches[1] ] ) ) {
				return $matches[0];
			}

			$post_id   = (int) $matches[2];
			$post_type = $post_types_with_ui[ $matches[1] ];
			/** @var string $post_type_name */
			$post_type_name = $post_type->labels->singular_name;

			$url = get_edit_post_link( $post_id );
			if ( is_null( $url ) ) {
				return $matches[0];
			}

			return sprintf(
				'<a href="%s">%s %s</a>',
				esc_url( $url, null, 'href' ),
				$post_type_name,
				$post_id,
			);
		};

		return preg_replace_callback( '/`(\w+):(\d+)`/', $callback, $column_output ) ?? $column_output;
	}

	/**
	 * Print message to when there are no items to display.
	 *
	 * We add a span here to add padding via CSS.
	 *
	 * @see WP_List_Table::no_items()
	 * @used-by WP_List_Table::display_rows_or_placeholder()
	 */
	public function no_items(): void {
		echo '<span class="no-items-message">';
		echo esc_html( __( 'No items found.', 'bh-wp-logger' ) );
		echo '</span>';
	}
}
