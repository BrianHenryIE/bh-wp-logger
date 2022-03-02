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

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use DateTime;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\LoggerInterface;
use stdClass;
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
	 * @param API_Interface                                                       $api
	 * @param Logger_Settings_Interface                                           $settings
	 * @param LoggerInterface                                                     $logger
	 * @param array{plural?:string, singular?:string, ajax?:bool, screen?:string} $args
	 */
	public function __construct( API_Interface $api, Logger_Settings_Interface $settings, LoggerInterface $logger, array $args = array() ) {
		parent::__construct( $args );

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

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
	 * @param array{time:string, level:string, message:string, context:array} $item The current item.
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
	 * @param array{time:string, level:string, message:string, context:array} $item ...whatever type get_data returns.
	 * @param string                                                          $column_name The specified column.
	 *
	 * @return string|true|void
	 * @see WP_List_Table::column_default()
	 *
	 * @see Logs_List_Table::get_data()
	 */
	public function column_default( $item, $column_name ) {

		$output = '';
		switch ( $column_name ) {
			case 'level':
				// The "level" column is just a color bar.
				return $output;
			case 'time':
				$time = $item['time'];

				try {
					$datetime = new DateTime( $time );
					// TODO: Is there a way to know if the site's timezone has never been set properly?
					// TODO: Is it better to use the user's timezone rather than the server timezone?
					$datetime->setTimezone( wp_timezone() );

					// Output in format: 20:02, Saturday, 14 November, 2020 (PST).
					$date_formatted = $datetime->format( 'H:i, l, d F, Y (T)' );
					$output        .= $date_formatted;
					$output        .= '<br/>';
				} catch ( \Exception $e ) {
					$output .= 'Could not parse date: ';
				}
				$output .= $time;

				return $output;

			case 'message':
			case 'context':
				$context             = $item[ $column_name ] ?? '';
				$context_column_text = trim( wp_json_encode( $context, JSON_PRETTY_PRINT ), "'\"" );
				return is_string( $context_column_text ) ? esc_html( $context_column_text ) : '';
			default:
				// TODO: Log unexpected column name / do_action.
				return '';
		}
	}
}
