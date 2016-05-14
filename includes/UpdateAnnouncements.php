<?php

require_once( 'includes/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php' );
require_once( 'includes/LibrusRipAnnouncements.php' );
require_once( 'includes/DatabaseFetchAnnouncements.php' );
require_once( 'includes/MySQLLogin.php' );
require_once( 'includes/Logging.php' );

/* addToDatabase( PDO, Announcement )
 *	
 * Inserts into table 'librus_announcements' the announcement passed by the second parameter.
 * Uses the PDO connection passed by the first parameter.
 *
 * Expects a table in the database formatted in a following way:
 * Table name: 'librus_announcements'
 * 8 Columns:
 *  #	 name				type
 *	----------------------------------
 * 	1	'title' 			TEXT
 * 	2	'id' 				VARCHAR[32]
 * 	3	'author' 			TEXT
 * 	4	'date_posted' 		VARCHAR[16]
 * 	5	'date_modified' 	VARCHAR[16]
 * 	6	'contents'			TEXT
 * 	7	'condents_md5' 		VARCHAR[32]
 * 	8	'fb_id' 			TEXT
 *
 * !!! If a PDOException is caught, the script is terminated !!!
 */
function addToDatabase( &$connection, $announcement )
{
	try
	{
		$statement = $connection->prepare( "INSERT INTO librus_announcements( title, id, author, date_posted, date_modified, contents, contents_md5, fb_id ) VALUES( :title, :id, :author, :date_posted, :date_modified, :contents, :contents_md5, :fb_id )" );
		$statement->execute([
			':title' => $announcement->title, 
			':id' => $announcement->id, 
			':author' => $announcement->author,
			':date_posted' => $announcement->date_posted,
			':date_modified' => $announcement->date_modified,
			':contents' => $announcement->contents,
			':contents_md5' => $announcement->contents_md5,
			':fb_id' => $announcement->fb_id,
		]);
	}
	catch ( PDOException $error )
	{
		unset( $connection );
		errorLog( $error->getMessage() );
		exit( 1 );
	}
}

/* updateAnnouncements()
 *
 * Updates the announcements on facebook page and in database to match those on Librus.
 *
 * Uses the librusRipAnnouncements() function to download announcements from Librus.
 * Uses the databaseFetchAnnouncements() function to download existing data from database.
 * Uses the mySQLLogin() function to obtain a database conenction.
 * Uses host, database and credentials from 'mySQL.ini' file.
 * Uses facebook api, configured with data from 'facebook.ini' file.
 *
 * !!! If a PDOException, a Graph API exception or a Facebook SDK exception is caught, the script is terminated !!!
 */
function updateAnnouncements()
{
	$facebook_ini = parse_ini_file( 'config/facebook.ini' );

	$fb = new Facebook\Facebook([
		'app_id' => $facebook_ini['app_id'],
		'app_secret' => $facebook_ini['app_secret'],
		'default_access_token' => $facebook_ini['page_token'],
		'default_graph_version' => 'v2.6',
	]);
	$librus = librusRipAnnouncements();
	$database = databaseFetchAnnouncements();
	$connection = mySQLLogin();
	
	//Remove the announcements that no longer exist on Librus
	foreach( $database as $in_database )
	{
		$was_deleted = true;
		foreach( $librus as $in_librus )
		{
			if( $in_database->id == $in_librus->id )
			{
				$was_deleted = false;
				break;
			}
		}
		if( $was_deleted == true )
		{
			try 
			{
				$linkData = array();
				$fb->delete( "/{$in_database->fb_id}", $linkData );
			}
			catch( Facebook\Exceptions\FacebookResponseException $e ) 
			{
				unset( $connection );
				errorLog( 'Graph returned an error: ' . $e->getMessage() );
				exit( 1 );
			}
			catch( Facebook\Exceptions\FacebookSDKException $e ) 
			{
				unset( $connection );
				errorLog( 'Facebook SDK returned an error: ' . $e->getMessage() );
				exit( 1 );
			}
			
			try
			{
				$statement = $connection->prepare( "DELETE FROM librus_announcements WHERE id='{$in_database->id}'" );
				$statement->execute();
			}
			catch ( PDOException $error )
			{
				unset( $connection );
				errorLog( $error->getMessage() );
				exit( 1 );
			}
			debugLog( 'Removed an announcement' );
		}
	}
	
	//Add announcements that have not yet been added
	foreach( $librus as $in_librus )
	{
		$is_new = true;
		
		foreach( $database as $in_database )
		{
			if( $in_librus->id == $in_database->id )
			{
				//Announcement is not new but it should be updated if it was changed
				if( $in_librus->contents_md5 != $in_database->contents_md5 )
				{
					$in_librus->date_modified = date( 'Y-m-d G:i' );
					
					$linkData = [
						'message' => mb_convert_encoding( $in_librus, 'UTF-8' ),
						'created_time' => $in_librus->date_posted,
					];
						 
					try 
					{
						$fb->post("/{$in_database->fb_id}", $linkData );
					}
					catch(Facebook\Exceptions\FacebookResponseException $e) 
					{
						unset( $connection );
						errorLog( 'Graph returned an error: ' . $e->getMessage() );
						exit( 1 );
					}
					catch(Facebook\Exceptions\FacebookSDKException $e) 
					{
						unset( $connection );
						errorLog( 'Facebook SDK returned an error: ' . $e->getMessage() );
						exit( 1 );
					}

					$in_librus->fb_id = $in_database->fb_id;
					
					try
					{
						$statement = $connection->prepare( "DELETE FROM librus_announcements WHERE id='{$in_database->id}'" );
						$statement->execute();
					}
					catch ( PDOException $error )
					{
						unset( $connection );
						errorLog( $error->getMessage() );
						exit( 1 );
					}
					
					addToDatabase( $connection, $in_librus );
					debugLog( 'Modified an announcement' );
				}
				
				$is_new = false;
				break;
			}
		}
		if( $is_new == true )
		{
			$linkData = [
				'message' => mb_convert_encoding( $in_librus, 'UTF-8' ),
				'created_time' => $in_librus->date_posted,
			];
				 
			try 
			{
				$response = $fb->post("/{$facebook_ini['page_id']}/feed", $linkData );
			}
			catch(Facebook\Exceptions\FacebookResponseException $e) 
			{
				unset( $connection );
				errorLog( 'Graph returned an error: ' . $e->getMessage() );
				exit( 1 );
			}
			catch(Facebook\Exceptions\FacebookSDKException $e) 
			{
				unset( $connection );
				errorLog( 'Facebook SDK returned an error: ' . $e->getMessage() );
				exit( 1 );
			}

			$graphNode = $response->getGraphNode();
			$in_librus->fb_id = $graphNode['id'];
			
			addToDatabase( $connection, $in_librus );
			debugLog( 'Added an announcement' );
		}
	}
		
	unset( $connection );
}