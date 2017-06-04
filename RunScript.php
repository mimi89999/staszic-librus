<?php

/* 
 * Librus to Facebook announcement copying script
 * Copyright (c) 2016 Jakub Bartmiński
 *
 * (More info in the README.md file)
 * https://github.com/xvyx/staszic-librus/
 *
 */
	
require_once( dirname(__FILE__) . '/includes/UpdateMode.php' );
require_once( dirname(__FILE__) . '/includes/ResetMode.php' );
require_once( dirname(__FILE__) . '/includes/php-graph-sdk/src/Facebook/autoload.php' );

ini_set( 'max_execution_time', 300 );
ini_set( 'display_errors', 1 );
mb_internal_encoding( 'UTF-8' );
date_default_timezone_set( 'Europe/Warsaw' );

if( isset( $_GET['reset'] ) || ( isset( $argv ) && count( $argv ) > 1 && $argv[1] == '--reset' ) )
	resetMode();
else
	updateMode();

echo 'Done!';
