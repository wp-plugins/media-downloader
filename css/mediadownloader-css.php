<?php
// Load WordPress "framework"
require_once( '../../../../wp-load.php' );

header( 'Content-Type: text/css' );
//header( 'Cache-Control: no-cache' );
//header( 'Pragma: no-cache' );

// Throw "custom css" option value
echo get_option( 'customcss' );

?>
