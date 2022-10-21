<?php
/**
 * A standard WordPress table to show the time, severity, message and context of each log entry.
 *
 * The dream would someday to have complex filtering on this table. e.g. filter all logs to one request, to one user...
 *
 * Time should show (UTC,local and "five hours ago")
 *
 * @package  brianhenryie/bh-wp-logger
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\BH_WP_PSR_Logger;
use BrianHenryIE\WP_Logger\API_Interface;
use BrianHenryIE\WP_Logger\Logger_Settings_Interface;
use DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use WP_List_Table;

/**
 * Class Logs_Table
 */
class Logs_List_Table extends WP_List_Table {
	use LoggerAwareTrait;

	protected Logger_Settings_Interface $settings;

	protected API_Interface $api;

	protected ?string $selected_date = null;

	/**
	 * Logs_Table constructor.
	 *
	 * @see WP_List_Table::__construct()
	 *
	 * @param API_Interface                                                       $api The logger API.
	 * @param Logger_Settings_Interface                                           $settings The logger settings.
	 * @param BH_WP_PSR_Logger                                                    $logger The logger itself, to use for actual logging.
	 * @param array{plural?:string, singular?:string, ajax?:bool, screen?:string} $args Arguments array from parent class.
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, BH_WP_PSR_Logger $logger, array $args = array() ) {
		parent::__construct( $args );

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
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
	 * Read the log file and parse the data.
	 *
	 * TODO: Move out of here. This should be a generic PSR-Log-Data class.
	 *
	 * @return array<array{time:string,datetime:?DateTime,level:string,message:string,context:?stdClass}>
	 */
	public function get_data(): array {

		$log_files = $this->api->get_log_files();

		if ( empty( $log_files ) ) {
			// TODO: "No logs yet." message. Maybe with "current log level is:".
			return array();
		} elseif ( ! is_null( $this->selected_date ) && isset( $log_files[ $this->selected_date ] ) ) {
			$filepath = $log_files[ $this->selected_date ];
		} else {
			$filepath = array_pop( $log_files );
		}

		return $this->api->parse_log( $filepath );
	}

	/**
	 * Get the list of columns in this table.
	 *
	 * @overrides WP_List_Table::get_columns()
	 * @see WP_List_Table::get_columns()
	 *
	 * @return array{level:string, time:string, message:string, context:string} array<column identifier, column title>
	 */
	public function get_columns() {
		$columns = array(
			'level'   => '',
			'time'    => 'Time',
			'message' => 'Message',
			'context' => 'Context',
		);
		return $columns;
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
		$this->items           = $this->get_data();
	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @see WP_List_Table::single_row()
	 *
	 * @param array{time:string, level:string, message:string, context:array<mixed>} $item The current item.
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
	 * @param array{time:string, level:string, message:string, context:array<mixed>} $item ...whatever type get_data returns.
	 * @param string                                                                 $column_name The specified column.
	 *
	 * @return string|true|void
	 * @see WP_List_Table::column_default()
	 *
	 * @see Logs_List_Table::get_data()
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

					// Output in format: 20:02, Saturday, 14 November, 2020 (PST).
					$date_formatted = $datetime->format( 'H:i, l, d F, Y (T)' );
					$column_output .= $date_formatted;
					$column_output .= '<br/>';
				} catch ( \Exception $e ) {
					$column_output .= 'Could not parse date: ';
				}
				$column_output .= $time;
				break;
			case 'context':
				if ( ! empty( $item['context'] ) ) {
					$column_output = esc_html( trim( wp_json_encode( $item['context'], JSON_PRETTY_PRINT ), "'\"" ) );
				}
				break;
			case 'message':
				// The "message" is just text.
				$column_output = $item['message'];
				$column_output = esc_html( $column_output );
				$column_output = $this->replace_wp_user_id_with_link( $column_output );
				$column_output = $this->replace_shop_order_id_with_link( $column_output );
				break;
			case 'level':
				// The "level" column is just a color bar.
			default:
				// TODO: Log unexpected column name.
				break;
		}

		$logger_settings = $this->settings;
		$logger          = $this->logger;

		/**
		 * Filter to modify what is printed for the column.
		 * e.g. find and replace wc_order:123 with a link to the order.
		 *
		 * @param string $column_output
		 * @param array{time:string, level:string, message:string, context:array<string,mixed>} $item The log entry row.
		 * @param string $column_name
		 * @param Logger_Settings_Interface $settings
		 * @param BH_WP_PSR_Logger $bh_wp_psr_logger
		 */
		$column_output = apply_filters( $this->settings->get_plugin_slug() . '_bh_wp_logger_column', $column_output, $item, $column_name, $logger_settings, $logger );

		return $column_output;
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

		$callback = function( array $matches ): string {

			$user = get_user_by( 'ID', $matches[1] );

			if ( $user instanceof \WP_User ) {
				// TODO: wpcs.
				$url  = admin_url( "user-edit.php?user_id={$matches[1]}" );
				$link = "<a href=\"{$url}\">{$user->user_nicename}</a>";
				return $link;
			}

			return $matches[0];
		};

		$message = preg_replace_callback( '/wp_user:(\d+)/', $callback, $message ) ?? $message;

		return $message;
	}

	/**
	 * Update `shop_order:123` with links to the order.
	 *
	 * TODO: Make this generic for all post types.
	 *
	 * @param string $column_output The column output so far.
	 *
	 * @return string
	 */
	public function replace_shop_order_id_with_link( string $column_output ): string {

		$callback = function( array $matches ): string {

			$url  = admin_url( "post.php?post={$matches[1]}&action=edit" );
			$link = "<a href=\"{$url}\">Order {$matches[1]}</a>";

			return $link;
		};

		$message = preg_replace_callback( '/shop_order:(\d+)/', $callback, $column_output ) ?? $column_output;

		return $message;
	}
}
