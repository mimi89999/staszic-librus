<?php

require_once( 'includes/Announcement.php' );
require_once( 'includes/MySQLLogin.php' );
require_once( 'includes/Logging.php' );
	
/* databaseFetchAnnouncements();
 * 
 * Downloads and returns existing announcements from the database.
 *
 * Uses the Announcement class to store the announcements.
 * Uses the mySQLLogin() function to obtain a database conenction.
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
 * Returns an array of Announcement objects fetched from the 'librus_announcements' table
 *
 * !!! If a PDOException is caught, the script is terminated !!!
 */
 
function databaseFetchAnnouncements()
{
	$connection = mySQLLogin();
	$database = array();

	try
	{
		$statement = $connection->prepare( "SELECT * FROM librus_announcements" );
		$statement->execute();
		$i = 0;
		while( $result = $statement->fetch(PDO::FETCH_ASSOC) )
		{
			$database[] = new Announcement();
			$database[$i]->title = $result['title'];
			$database[$i]->id = $result['id'];
			$database[$i]->author = $result['author'];
			$database[$i]->date_posted = $result['date_posted'];
			$database[$i]->date_modified = $result['date_modified'];
			$database[$i]->contents = $result['contents'];
			$database[$i]->contents_md5 = $result['contents_md5'];
			$database[$i]->fb_id = $result['fb_id'];
			$i++;
		}
	}
	catch ( PDOException $error )
	{
		unset( $connection );
		errorLog( $error->getMessage() );
		exit( 1 );
	}
	
	unset( $connection );
	return $database;
}
