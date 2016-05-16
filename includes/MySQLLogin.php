<?php

function mySQLLogin()
{
	$mySQL_ini = parse_ini_file( 'config/mySQL.ini' );

	$mysql_connection = new PDO(
		"mysql:host={$mySQL_ini['host']}; dbname={$mySQL_ini['database']}; charset=utf8",
		$mySQL_ini[ 'username' ],
		$mySQL_ini[ 'password' ] );
		
	$mysql_connection -> setAttribute( PDO::ATTR_EMULATE_PREPARES, false );
	$mysql_connection -> setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
	
	return $mysql_connection;
}	