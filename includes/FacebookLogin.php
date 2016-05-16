<?php

require_once( "includes/facebook-php-sdk-v4-5.0.0/src/Facebook/autoload.php" );

function facebookLogin()
{
	$facebook_ini = parse_ini_file( 'config/facebook.ini' );

	$facebook_handle = new Facebook\Facebook([
		'app_id' 				=> $facebook_ini[ 'app_id' ],
		'app_secret'			=> $facebook_ini[ 'app_secret' ],
		'default_access_token' 	=> $facebook_ini[ 'page_token' ],
		'default_graph_version' => 'v2.6',
	]);
	
	return $facebook_handle;
}