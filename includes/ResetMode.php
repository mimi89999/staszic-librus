<?php

require_once( 'includes/MySQLLogin.php' );
require_once( 'includes/FacebookLogin.php' );

require_once( 'includes/Announcement.php' );

require_once( 'includes/Logging.php' );
	
	
/*
 * Removes all announvements from the facebook page and the database.
 * !!! THIS IS IRREVERSIBLE !!!
 *
 */
function resetMode()
{
	$facebook_handle = facebookLogin();
	$mysql_connection = mySQLLogin();
	
	$database_data = databaseFetchAnnouncements( $mysql_connection );
	
	foreach( $database_data as $in_database )
		$in_database -> unpublish( $mysql_connection, $facebook_handle );
		
	unset( $facebook_handle );
	unset( $mysql_connection );
	
	debugLog( "Executed Reset Mode!" );
}