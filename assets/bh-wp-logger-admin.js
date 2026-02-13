(function( $ ) {
	'use strict';

	$(function() {

			function show_hide_log_checkbox( element ) {
				let id = $(element).attr('id');
				let log_level = id.replace('log_level_display_checkbox_','');
				let css_class = 'tr.level-'+log_level;
				let display = $(element).is(':checked') ? '' : 'none';

				$(css_class).css('display', display);

				// TODO: Refresh the page height. Scrolling to the bottom currently results in lots of empty whitespace after the rows are hidden.
			}

			$('.log_level_display_checkbox').click( function( e ) {
				show_hide_log_checkbox( this );
			});

			// Respect the state upon refresh.
			$('.log_level_display_checkbox').each( function () {
				show_hide_log_checkbox( this );
			});

			// When the date is changed, reload the page.
			$('#log_date').change(function() {
				var selectedDate = $('#log_date').val();

				var urlParams = new URLSearchParams(window.location.search);
				urlParams.set('log_date',selectedDate);

				window.location = location.pathname+'?' + urlParams ;
			});

			// When the delete button is clicked...
			$( '.button.logs-page' ).on(
				'click',
				function(event) {
					event.preventDefault();

					let buttonName = event.target.name;

                    var data = {};

					// $_GET['page'] has the slug.
                    // e.g. ?page=bh-wp-logger-development-plugin-logs
                    var urlParams = new URLSearchParams(window.location.search);
                    let slug_log = urlParams.get('page');
                    if( false === slug_log.endsWith('-logs') ) {
                        return;
                    }
                    let slug = slug_log.slice(0, -5);

                    data.plugin_slug = slug;
					data._wpnonce = $('#delete_logs_wpnonce').val();

					switch ( buttonName) {
						case 'deleteButton':
							data.action = 'bh_wp_logger_logs_delete';
							// let deleteButton = document.getElementById( 'deleteButton' ).data;
							// let dateToDelete = deleteButton.dataset.date;
							let dateToDelete = event.target.dataset.date;
							data.date_to_delete = dateToDelete;
							break;
						case 'deleteAllButton':
							data.action = 'bh_wp_logger_logs_delete_all';
							break;
						default:
							return;
					}

					$.post(
						ajaxurl,
						data,
						function (response) {
							var urlParams = new URLSearchParams(window.location.search);
							// TODO: it should change to the closest date.
							urlParams.delete('log_date');
							window.location = location.pathname+'?' + urlParams ;

						}
					);

				}
			);

			// Set the column-time width to only what's needed.
			$('.column-time').css('width',$('td.column-time').first().children().first().width()+25);

			$('.wp-list-table').colResizable();

			renderjson.set_icons('+', '-');
			renderjson.set_show_to_level(2);

			$('.log-context-pre').each( function () {

				let json_context = $(this).data('json');
				let rendered_context = renderjson(json_context);

				$(this).parent().append(rendered_context);

				// Leave the existing content in the TD so it can be searched.
				$(this).css('height','0');
				$(this).css('font-size','0');
			});

		}
	);

})( jQuery );
