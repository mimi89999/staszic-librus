<?php

require_once( 'includes/Announcement.php' );
	
/* librusRipAnnouncements() 
 *
 * Logins into Librus and returns the announcements in an array of Announcement objects.
 * 
 * Uses curl for connecting to Librus.
 * Uses the address and credentials from the 'librus.ini' file.
 * !!! Ripping the announcements depends on the site layout, which might change in the future, breaking this function !!!
 * Uses the Announcement class to store the announcements.
 * 
 * Returns an array of Announcement objects with every field except for 'fb_id' initialized.
 * Returned array is in chronological order (oldest announcements first).
 */
function librusRipAnnouncements()
{
	$librus_ini = parse_ini_file( 'config/librus.ini' );
	
	$ch = curl_init();

	curl_setopt( $ch, CURLOPT_COOKIEJAR, "cookie.txt" );

	curl_setopt( $ch, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, 0 );

	curl_setopt( $ch, CURLOPT_FOLLOWLOCATION, 0 );

	curl_setopt( $ch, CURLOPT_USERAGENT, 
		"Mozilla/5.0 (Windows; U; Windows NT 5.0; en-US; rv:1.7.12) Gecko/20050915 Firefox/1.0.7" );
	curl_setopt( $ch, CURLOPT_REFERER, $_SERVER['REQUEST_URI'] );

	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );

	//Obtain 'TestCookie' and 'DZIENNIKSID' cookies from the login page
	curl_setopt( $ch, CURLOPT_URL, $librus_ini['login_page'] );
	curl_exec( $ch );

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

	curl_setopt( $ch, CURLOPT_POST, 1 );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $postinfo );
	curl_exec( $ch );

	curl_setopt( $ch, CURLOPT_POST, 0 );
	curl_setopt($ch, CURLOPT_URL, $librus_ini['announcement_page'] );
	$html = curl_exec($ch);
	
	curl_close($ch);

	//Rip the relevant part of the announcement page, contained between <form></form> tags
	$pos1 = strpos ( $html, '<form' );
	$pos2 = strpos ( $html, '</form', $pos1 );
	$html = substr( $html, $pos1, $pos2-$pos1+7 );

	//Rip the announcements from between the <td></td> tags into an array of Announcement objects
	$librus = array();
	$pos1 = strpos( $html, '<td' );
	$i = 0;
	while( $pos1 !== FALSE )
	{
		$librus[] = new Announcement();
		
		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus[$i]->title = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2-$pos1 ) ) );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus[$i]->author = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2-$pos1 ) ) );
		
		$librus[$i]->id = hash( "md5", $librus[$i]->title . $librus[$i]->author );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus[$i]->date_posted = substr( $html, $pos1, $pos2-$pos1 );
		$librus[$i]->date_modified = '-';

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus[$i]->contents = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2-$pos1 ) ) );
		$librus[$i]->contents_md5 = hash( "md5", $librus[$i]->contents );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		
		$pos1 = strpos( $html, '<td', $pos1 );
		$i++;
	}
	
	//The array is in reverse chronological order (most recent announcements first), so it has to be reversed
	return array_reverse( $librus );
}