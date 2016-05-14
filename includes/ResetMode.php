<?php

require_once( 'includes/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php' );
require_once( 'includes/DatabaseFetchAnnouncements.php' );
require_once( 'includes/MySQLLogin.php' );
	
/* resetMode()
 *
 * Removes all facebook posts and clears the database.
 * 
 * Uses the databaseFetchAnnouncements() function to obtain facebook post ids.
 * Uses the mySQLLogin() function to obtain a database conenction.
 * Uses host, database and credentials from 'mySQL.ini' file.
 * Uses facebook api, configured with data from 'facebook.ini' file.
 *
 * !!! THIS IS IRREVERSIBLE !!!
 * !!! If a PDOException, a Graph API exception or a Facebook SDK exception is caught, the script is terminated !!!
 */
function resetMode()
{
	$facebook_ini = parse_ini_file( 'config/facebook.ini' );

	$fb = new Facebook\Facebook([
		'app_id' => $facebook_ini['app_id'],
		'app_secret' => $facebook_ini['app_secret'],
		'default_access_token' => $facebook_ini['page_token'],
		'default_graph_version' => 'v2.6',
	]);
	
	$database = databaseFetchAnnouncements();
	foreach( $database as $in_database )
	{
		try 
		{
			$linkData = array();
			$fb->delete( "/{$in_database->fb_id}", $linkData );
		}
		catch( Facebook\Exceptions\FacebookResponseException $e ) 
		{
			errorLog( 'Graph returned an error: ' . $e->getMessage() );
			exit( 1 );
		}
		catch( Facebook\Exceptions\FacebookSDKException $e ) 
		{
			errorLog( 'Facebook SDK returned an error: ' . $e->getMessage() );
			exit( 1 );
		}
	}
	
	$connection = mySQLLogin();
	try
	{
		$statement = $connection->prepare( "TRUNCATE librus_announcements" );
		$statement->execute();
	}
	catch ( PDOException $error )
	{
		unset( $connection );
		errorLog( $error->getMessage() );
		exit( 1 );
	}
	unset( $connection );
	
	debugLog( "Executed Reset Mode!" );
}