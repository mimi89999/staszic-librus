<?php

function errorLog( $message )
{
	error_log( date( 'Y-m-d G:i:s' ) . " - $message\r\n" , 3, 'logs/error.log' );
}

function debugLog( $message )
{
	error_log( date( 'Y-m-d G:i:s' ) . " - $message\r\n" , 3, 'logs/debug.log' );
}