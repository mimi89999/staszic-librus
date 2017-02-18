<?php

function errorLog( $message )
{
	$error_msg = date( 'Y-m-d G:i:s' ) . " - $message\r\n";
	error_log( $error_msg, 3, dirname(__FILE__) . '/../logs/error.log' );
	fwrite( STDERR, $error_msg );
}

function debugLog( $message )
{
	$debug_msg = date( 'Y-m-d G:i:s' ) . " - $message\r\n";
	error_log( $debug_msg, 3, dirname(__FILE__) . '/../logs/debug.log' );
	echo $debug_msg;
}
