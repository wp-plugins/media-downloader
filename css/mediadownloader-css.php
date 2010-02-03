<?php
require_once('../../../../wp-load.php');

header('Content-Type: text/css');
//header('Cache-Control: no-cache');
//header('Pragma: no-cache');

echo get_option( 'customcss' );

?>

