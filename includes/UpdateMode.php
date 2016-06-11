<?php

require_once( 'includes/MySQLLogin.php' );
require_once( 'includes/FacebookLogin.php' );

require_once( 'includes/Announcement.php' );

require_once( 'includes/Logging.php' );

/*
 * Updates the facebook page and database to match the announcement page on Librus.
 * This should be done in regular time intervals.
 *
 */
function updateMode()
{
	$facebook_handle = facebookLogin();
	$mysql_connection = mySQLLogin();
	$databases = parse_ini_file( 'config/databases.ini' );
	
	$librus_data = librusFetchAnnouncements();
	
	if( $librus_data == null || count( $librus_data ) == 0 )
	{
		errorLog( 'Error loading announcements!' );
		unset( $facebook_handle );
		unset( $mysql_connection );
		return;
	}
	
	$database_data = databaseFetchAnnouncements( $mysql_connection );
	$last_update = null;
	
	//Remove the announcements that no longer exist on Librus
	foreach( $database_data as $in_database )
	{
		$was_deleted = true;
		
		foreach( $librus_data as $in_librus )
		{
			if( $in_database -> id == $in_librus -> id )
			{
				$was_deleted = false;
				break;
			}
		}
		
		if( $was_deleted )
		{
			$in_database -> unpublish( $mysql_connection, $facebook_handle );
			debugLog( 'Removed an announcement' );
		}
	}
	
	//Add announcements that have not yet been added
	foreach( $librus_data as $in_librus )
	{
		$is_new = true;
		
		foreach( $database_data as $in_database )
		{
			if( $in_librus -> id == $in_database -> id )
			{
				if( $in_librus ->contents_md5 != $in_database->contents_md5 )
				{
					$in_database -> unpublish( $mysql_connection, $facebook_handle );
					
					$in_librus -> date_modified = date( 'Y-m-d H:i' );
					$last_update = $in_librus -> date_modified;
					
					$in_librus -> publish( $mysql_connection, $facebook_handle );
					debugLog( 'Modified an announcement' );
				}
				
				$is_new = false;
				break;
			}
		}
		if( $is_new == true )
		{
			$last_update = date( 'Y-m-d H:i' );
			$in_librus -> publish( $mysql_connection, $facebook_handle );
			debugLog( 'Added an announcement' );
		}
	}
	
	//Update facebook description
	if( is_null( $last_update ) )
	{
		$statement = $mysql_connection -> prepare( "SELECT * FROM {$databases['last_update_table']}" );
		$statement -> execute();
		while( $result = $statement -> fetch( PDO::FETCH_ASSOC ) )
			$last_update = $result[ 'time' ];
	}
	else
	{
		$statement = $mysql_connection -> prepare( "TRUNCATE {$databases['last_update_table']}" );
		$statement -> execute();
		$statement = $mysql_connection -> prepare( "INSERT INTO {$databases['last_update_table']}( time ) VALUES( :last_update )" );
		$statement -> execute([ ':last_update' => $last_update ]);
	}
		
	$linkData = [
		'about' => 
			'Ostatnia aktualizacja: ' . date( 'Y-m-d H:i' ) . "\r\n" .
			'Ostatnia zmiana: ' . $last_update . "\r\n" ,
	];
	$facebook_handle -> post( "/me", $linkData );
		
	unset( $facebook_handle );
	unset( $mysql_connection );
}
