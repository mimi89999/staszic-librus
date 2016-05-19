<?php

function downloadLibrusAnnouncementPage()
{
	$librus_ini = parse_ini_file( 'config/librus.ini' );
	
	$curl_handle = curl_init();

	curl_setopt( $curl_handle, CURLOPT_COOKIEJAR, "cookie.txt" );

	//These Aren't the Droids You're Looking For
	curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, 0 );
		
	curl_setopt( $curl_handle, CURLOPT_FOLLOWLOCATION, 0 );

	curl_setopt( $curl_handle, CURLOPT_USERAGENT, 
			"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7" );
	curl_setopt( $curl_handle, CURLOPT_REFERER, $_SERVER[ 'REQUEST_URI' ] );
		
	curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );

	//Obtain 'TestCookie' and 'DZIENNIKSID' cookies from the login page
	curl_setopt( $curl_handle, CURLOPT_URL, $librus_ini['login_page'] );
	curl_exec( $curl_handle );

	//After obtaining the cookies it is now possible to login with a POST request to the login page
	$postinfo = [
		'login'				=> $librus_ini['login'],
		'passwd'			=> $librus_ini['password'],
		'ed_pass_keydown' 	=> '',
		'ed_pass_keyup' 	=> '',
		'captcha'		 	=> '',
		'jest_captcha' 		=> '1',
		'czy_js' 			=> '0',
	];

	curl_setopt( $curl_handle, CURLOPT_POST, 1 );
	curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, $postinfo );
	curl_exec( $curl_handle );

	curl_setopt( $curl_handle, CURLOPT_POST, 0 );
	curl_setopt( $curl_handle, CURLOPT_URL, $librus_ini[ 'announcement_page' ] );
	$html = curl_exec( $curl_handle );
		
	curl_close( $curl_handle );
	
	return $html;
}
