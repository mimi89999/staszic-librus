<?php

/* 
 * Librus <-> Facebook announcement copier script
 * Copyright (c) 2016 Jakub Bartmiński
 *
 * (More info in the README.md file)
 * https://github.com/xvyx/staszic-librus/
 *
 */
	
require_once( 'includes/UpdateMode.php' );
require_once( 'includes/ResetMode.php' );
require_once( "includes/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php" );

ini_set( 'max_execution_time', 300 );
mb_internal_encoding( 'UTF-8' );
date_default_timezone_set( 'Europe/Warsaw' );

try 
{
	if( isset( $_GET['reset'] ) || ( isset( $argv ) && count( $argv ) > 1 && $argv[1] == '--reset' ) )
		resetMode();
	else
		updateMode();
}
catch( Facebook\Exceptions\FacebookResponseException $e ) 
{ 
	errorLog( 'Graph returned an error: ' . $e->getMessage() ); 
}
catch( Facebook\Exceptions\FacebookSDKException $e ) 
{
	errorLog( 'Facebook SDK returned an error: ' . $e->getMessage() ); 
}
catch ( PDOException $error )
{
	errorLog( $error->getMessage() ); 
}

echo 'Done!';