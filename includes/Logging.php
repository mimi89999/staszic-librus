<?php

function errorLog( $message )
{
	$error_msg = date( 'Y-m-d G:i:s' ) . " - $message\r\n";
	error_log( $error_msg, 3, 'logs/error.log' );
	echo $error_msg;
}

function debugLog( $message )
{
	$debug_msg = date( 'Y-m-d G:i:s' ) . " - $message\r\n";
	error_log( $debug_msg, 3, 'logs/debug.log' );
	echo $debug_msg;
}