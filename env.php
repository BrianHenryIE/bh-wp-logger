<?php
// A failed attempt to set PHP define()s in .htaccess.
// auto_prepend won't work without the absolute path.
echo 'hello';

error_log( 'hello' );


// define( 'WP_DEBUG', true ); // already true in htaccess



define( 'WP_DEBUG_DISPLAY', true );
// causes -> ini_set( 'display_errors', 1 );



// define( 'WP_DEBUG_LOG', true); // already true by default

