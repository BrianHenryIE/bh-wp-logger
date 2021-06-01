<?php
/**
 *
 *
 * TODO: Add a class per session and highlight all that session's rows when it's hovered.
 * Time should show (UTC,local and "five hours ago")
 */

namespace BrianHenryIE\WP_Logger\Admin;

use BrianHenryIE\WP_Logger\API\API_Interface;
use BrianHenryIE\WP_Logger\API\Logger_Settings_Interface;
use Psr\Log\LoggerInterface;
use WP_List_Table;

class Logs_Table extends WP_List_Table {

	/**
	 * @var LoggerInterface
	 */
	protected $logger;

	/** @var Logger_Settings_Interface  */
	protected $settings;

	/** @var API_Interface  */
	protected $api;

	/**
	 * Logs_Table constructor.
	 *
	 * @param API_Interface             $api
	 * @param Logger_Settings_Interface $settings
	 * @param LoggerInterface           $logger
	 * @param array                     $args
	 */
	public function __construct( $api, $settings, $logger, $args = array() ) {
		parent::__construct( $args );

		$this->logger   = $logger;
		$this->settings = $settings;
		$this->api      = $api;
	}

	public function get_data() {

		$link = $this->api->get_log_file();

		if ( is_null( $link ) ) {
			// TODO: "No logs yet." message. Maybe with "current log level is:".
			return array();
		}

		$data  = array();
		$entry = null;

		$file_lines = file( $link );

		// Loop through our array, show HTML source as HTML source; and line numbers too.
		foreach ( $file_lines as $line_num => $input_line ) {

			$output_array = array();

			// 2020-10-23T17:39:36+00:00 CRITICAL message
			if ( 1 === preg_match( '/(?P<time>\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}.{1}\d{2}:\d{2})\s(?P<level>\w*)\s(?P<message>.*)/im', $input_line, $output_array ) ) {

				// Save previous entry?
				if ( ! is_null( $entry ) ) {

					$context = json_decode( $entry['context'] );

					if ( isset( $context->source ) ) {
						unset( $context->source );
					}
					$entry['context'] = $context;
					$data[]           = $entry;
				}

				$entry = $output_array;
				// TODO: trim the " from each end.
				// $entry['message'] = trim( $entry['message'], " \t\n\r\0\x0B\x22" );
				$entry['context'] = '';

				// check for context (which also could be multiline)

			} else {
				// A multiline message, so just append it to the previous

				$entry['context'] .= $input_line;

			}
		}

		// Save previous entry?
		if ( ! is_null( $entry ) ) {

			$context = json_decode( $entry['context'] );

			if ( isset( $context->source ) ) {
				unset( $context->source );
			}
			$entry['context'] = $context;
			$data[]           = $entry;
		}

		return $data;
	}



	function get_columns() {
		$columns = array(
			'level'   => '',
			'time'    => 'Time',
			'message' => 'Message',
			'context' => 'Context',
		);
		return $columns;
	}

	function prepare_items() {
		$columns               = $this->get_columns();
		$hidden                = array();
		$sortable              = array();
		$this->_column_headers = array( $columns, $hidden, $sortable );
		$this->items           = $this->get_data();

	}

	/**
	 * Generates content for a single row of the table.
	 *
	 * @since 3.1.0
	 *
	 * @param object $item The current item
	 */
	public function single_row( $item ) {
		echo '<tr class="level-' . strtolower( $item['level'] ) . '">';
		$this->single_row_columns( $item );
		echo '</tr>';
	}

	/**
	 * @param object $item ...whatever type get_data returns.
	 * @param string $column_name
	 *
	 * @return string|true|void
	 */
	function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'level':
				$output = '';
				return $output;
			case 'time':
				$time = $item[ $column_name ];

				$datetime = new \DateTime( $time );

				// TODO: Is there a way to know if the site's timezone has never been set properly?
				// TODO: Is it better to use the user's timezone rather than the server timezone?

				$datetime->setTimezone( wp_timezone() );

				// Output in format: 20:02, Saturday, 14 November, 2020 (PST).
				$output  = $datetime->format( 'H:i, l, d F, Y (T)' );
				$output .= '<br/>' . $time;

				return $output;

			case 'message':
			case 'context':
				return json_encode( $item[ $column_name ], JSON_PRETTY_PRINT );
			default:
				return print_r( $item, true ); // Show the whole array for troubleshooting purposes
		}
	}


}
