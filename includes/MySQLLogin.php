<?php

require_once( 'includes/Logging.php' );

/* mySQLLogin()
 *
 * Connects to the MySQL database and returns a PDO connection.
 *
 * Uses host, database and credentials from 'mySQL.ini' file.
 *
 * Returns a PDO connection.
 * The PDO connection should be unset after it is not needed anymore.
 *
 * !!! If a PDOException is caught, the script is terminated !!!
 */
function mySQLLogin()
{
	$mySQL_ini = parse_ini_file( 'config/mySQL.ini' );
	
	try
	{
		$connection = new PDO(
			"mysql:host={$mySQL_ini['host']}; dbname={$mySQL_ini['database']}; charset=utf8",
			$mySQL_ini['username'],
			$mySQL_ini['password'] );
		$connection->setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
		$connection->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	}
	catch( PDOException $error )
	{
		unset( $connection );
		errorLog( $error->getMessage() );
		exit( 1 );
	}
	
	return $connection;
}	