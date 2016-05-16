<?php

require_once( 'includes/MySQLLogin.php' );
require_once( 'includes/FacebookLogin.php' );
require_once( 'includes/DownloadLibrus.php' );

require_once( 'includes/Announcement.php' );

require_once( 'includes/Logging.php' );

function updateMode()
{
	$facebook_handle = facebookLogin();
	$mysql_connection = mySQLLogin();
	$html = downloadLibrus();
	
	$librus_data = librusRipAnnouncements( $html );
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
					
					$in_librus -> date_modified = date( 'Y-m-d G:i' );
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
			$in_librus -> publish( $mysql_connection, $facebook_handle );
			debugLog( 'Added an announcement' );
		}
	}
	
	//Update facebook description
	if( is_null( $last_update ) )
	{
		$statement = $mysql_connection -> prepare( "SELECT * FROM last_update" );
		$statement -> execute();
		while( $result = $statement -> fetch( PDO::FETCH_ASSOC ) )
			$last_update = $result[ 'time' ];
	}
	else
	{
		$statement = $mysql_connection -> prepare( 'TRUNCATE last_update' );
		$statement -> execute();
		$statement = $mysql_connection -> prepare( 'INSERT INTO last_update( time ) VALUES( :last_update )' );
		$statement -> execute([ ':last_update' => $last_update ]);
	}
		
	$linkData = [
		'about' => 
			'Ostatnia aktualizacja: ' . $last_update . "\r\n" . 
			'Ostatnie odświeżenie: ' . date( 'Y-m-d G:i' ) . "\r\n",
	];
	$facebook_handle -> post( "/me", $linkData );
		
	unset( $facebook_handle );
	unset( $mysql_connection );
}