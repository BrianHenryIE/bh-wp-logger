
(function( $ ) {
	'use strict';

	$(function() {

		$('.button.log-test').on('click', function(event) {
			event.preventDefault();

			window.console.log("You clicked on: " + event.target.name);

			var data = {
				'action': 'log',
				'log-test-action': event.target.name,
				'_wpnonce': bh_wp_logger_development_plugin.__wp_nonce
			};

			let message = document.getElementById('log_message').value;
			if(message) {
				data.message = message;
			}
			let context = document.getElementById('log_context').value;
			if(context) {
				data.context = context;
			}


			$.post(ajaxurl, data, function (response) {


				// TODO: Show & refresh the log table below.


			});

		});

	});

})( jQuery );
