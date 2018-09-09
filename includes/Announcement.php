<?php
require_once( dirname(__FILE__) . '/Logging.php' );

class Announcement
{
	public $id;
	public $title;
	public $author;
	public $contents;
	public $contents_md5;
	public $date_posted;
	public $date_modified;
	public $fb_id;
		
	public function __toString()
	{
		return( 
			"$this->title\r\n" .
			"Dodał: $this->author\r\n" .
			"Data publikacji: $this->date_posted\r\n" .
			"Ostatnia zmiana: $this->date_modified\r\n" .
			"\r\n$this->contents" );
	}
		
	public function publish( &$mysql_connection, &$facebook_handle )
	{	
		$databases = parse_ini_file( dirname(__FILE__) . '/../config/databases.ini' );
		$link_data = [
			'message' 	=> mb_convert_encoding( $this, 'UTF-8' ),
			'created_time' 	=> $this -> date_posted,
		];
		$response = $facebook_handle -> post( "/me/feed", $link_data );
		$graph_node = $response -> getGraphNode();
		if( array_key_exists( 'id', $graph_node -> asArray() ) )
		{
			$this -> fb_id = $graph_node[ 'id' ];
			
			$statement = $mysql_connection -> prepare( "INSERT INTO {$databases['announcements_table']}( id, title, author, contents, contents_md5, date_posted, date_modified, fb_id ) VALUES( :id, :title, :author, :contents, :contents_md5, :date_posted, :date_modified, :fb_id )" );
			$statement -> execute([
				':id' 				=> $this -> id, 
				':title'			=> $this -> title, 
				':author' 			=> $this -> author,
				':contents' 		=> $this -> contents,
				':contents_md5' 	=> $this -> contents_md5,
				':date_posted' 		=> $this -> date_posted,
				':date_modified'	=> $this -> date_modified,
				':fb_id' 			=> $this -> fb_id, 
			]);
		}
		
	}
	
	public function unpublish( &$mysql_connection, &$facebook_handle )
	{
		$databases = parse_ini_file( 'config/databases.ini' );
		$link_data = array();
		$response = false;
		$graph_node = false;
		try
		{
			$response = $facebook_handle -> delete( "/{$this->fb_id}", $link_data );
			$graph_node = $response -> getGraphNode();
		}
		catch( Facebook\Exceptions\FacebookResponseException $e ) 
		{
			if( $e->getMessage() == '(#100) This post could not be loaded' )
			{
				errorLog( 'Announcement publication: Graph returned an error: ' . $e->getMessage() . '. Removing leftover database entry.' );
				$statement = $mysql_connection -> prepare( "DELETE FROM {$databases['announcements_table']} WHERE fb_id = '{$this->fb_id}'" );
				$statement -> execute();
			}
			else
			{
				errorLog( 'Announcement unpublication: Graph returned an error: ' . $e->getMessage() . '. Removing database entry.' );
				$statement = $mysql_connection -> prepare( "DELETE FROM {$databases['announcements_table']} WHERE fb_id = '{$this->fb_id}'" );
				$statement -> execute();
			}		
		}

		if( $graph_node != false && array_key_exists( 'success', $graph_node -> asArray() )  )
		{
			$statement = $mysql_connection -> prepare( "DELETE FROM {$databases['announcements_table']} WHERE fb_id = '{$this->fb_id}'" );
			$statement -> execute();
		}
	}
}
	
function databaseFetchAnnouncements( &$mysql_connection )
{
	$databases = parse_ini_file( 'config/databases.ini' );
	$database_data = array();

	$statement = $mysql_connection -> prepare( "SELECT * FROM {$databases['announcements_table']}" );
	$statement -> execute();
	$i = 0;
	while( $result = $statement->fetch(PDO::FETCH_ASSOC) )
	{
		$database_data[] 					= new Announcement();
		$database_data[$i] -> id 			= $result['id'];
		$database_data[$i] -> title 		= $result['title'];
		$database_data[$i] -> author 		= $result['author'];
		$database_data[$i] -> contents 		= $result['contents'];
		$database_data[$i] -> contents_md5 	= $result['contents_md5'];
		$database_data[$i] -> date_posted 	= $result['date_posted'];
		$database_data[$i] -> date_modified = $result['date_modified'];
		$database_data[$i] -> fb_id 		= $result['fb_id'];
		$i++;
	}
	
	return $database_data;
}

function obtainLibrusToken( &$curl_handle )
{
	$librus_ini = parse_ini_file( 'config/librus.ini' );

	$client_id = 'wmSyUMo8llDAs4y9tJVYY92oyZ6h4lAt7KCuy0Gv';

	curl_setopt( $curl_handle, CURLOPT_COOKIEFILE, '' );
	curl_setopt( $curl_handle, CURLOPT_FOLLOWLOCATION, 1 );
	curl_setopt( $curl_handle, CURLOPT_HEADER, 1 );

	curl_setopt( $curl_handle, CURLOPT_URL,
		'https://portal.librus.pl/oauth2/authorize?' .
		"client_id={$client_id}&" .
		'redirect_uri=http://localhost/bar&response_type=code' );

	$portal_login_page = curl_exec( $curl_handle );

	preg_match( "'<meta name=\"csrf-token\" content=\"(.*?)\">'si",
		$portal_login_page,
		$csrf );

	curl_setopt( $curl_handle, CURLOPT_POST, 1 );
	curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array(
		'Content-Type: application/json',
		"X-CSRF-TOKEN: {$csrf[1]}") );
	curl_setopt( $curl_handle, CURLOPT_URL, 'https://portal.librus.pl/rodzina/login/action' );
	curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, json_encode( array(
		'email' => $librus_ini['login'],
		'password' => $librus_ini['password']) ) );

	curl_exec( $curl_handle );

	curl_setopt( $curl_handle, CURLOPT_POST, 0 );
	curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array() );
	curl_setopt( $curl_handle, CURLOPT_FOLLOWLOCATION, 0 );
	curl_setopt( $curl_handle, CURLOPT_URL,
		'https://portal.librus.pl/oauth2/authorize?' .
		"client_id={$client_id}&" .
		'redirect_uri=http://localhost/bar&response_type=code' );

	$oauth_redirect = curl_exec( $curl_handle );

	preg_match( "'code=(.*?)\n'si", $oauth_redirect, $oauth_code );

	curl_setopt( $curl_handle, CURLOPT_POST, 1 );
	curl_setopt( $curl_handle, CURLOPT_HEADER, 0 );
	curl_setopt( $curl_handle, CURLOPT_URL, 'https://portal.librus.pl/oauth2/access_token' );
	curl_setopt( $curl_handle, CURLOPT_POSTFIELDS, array(
		'grant_type' => 'authorization_code',
		'code' => $oauth_code[1],
		'redirect_uri' => 'http://localhost/bar',
		'client_id' => $client_id) );

	$portal_tokens = curl_exec( $curl_handle );

	$portal_access_token = json_decode( $portal_tokens, true )['access_token'];

	curl_setopt( $curl_handle, CURLOPT_POST, 0 );
	curl_setopt( $curl_handle, CURLOPT_URL, 'https://portal.librus.pl/api/SynergiaAccounts' );
	curl_setopt( $curl_handle, CURLOPT_HTTPHEADER, array(
		"Authorization: Bearer {$portal_access_token}") );

	$synergia_accounts = curl_exec( $curl_handle );

	$synergia_account = json_decode( $synergia_accounts, true )['accounts'][0];
	$user_token = $synergia_account['accessToken'];

	return [
		'access_token' 	=> $user_token,
		'token_type'	=> 'Bearer',
	];
}

function downloadSchoolNotices( &$curl_handle, $librus_token )
{
	curl_setopt( $curl_handle, CURLOPT_URL, 'https://api.librus.pl/2.0/SchoolNotices' );
	curl_setopt( $curl_handle, CURLOPT_POST, 0 );
	
	curl_setopt( 
		$curl_handle, 
		CURLOPT_HTTPHEADER, 
		array(
			"Authorization: {$librus_token[ 'token_type' ]} {$librus_token[ 'access_token' ]}",
			'User-Agent: Dalvik/2.1.0 (Linux; U; Android 5.1.1; A0001 Build/LMY48B)',
			'Host: api.librus.pl',
			'Connection: Keep-Alive',
			'Accept-Encoding: gzip',
		)
	);

	$json_raw = curl_exec( $curl_handle );
	if( $json_raw === FALSE )
	{
		errorLog( 'Error downloading announcements! Curl error: ' . curl_error( $curl_handle ) );
		return null;
	}
	
	$response = json_decode( $json_raw );
	if( isset( $response -> error ) )
	{
		errorLog( 'Error downloading announcements! Librus API Error: ' . $response -> error );
		return null;
	}
	
	return $response;
}

function fillInTeacherNames( &$curl_handle, $librus_token, $notices_incomplete )
{
	$url = 'https://api.librus.pl/2.0/Users/';
	curl_setopt( $curl_handle, CURLOPT_POST, 0 );
	
	curl_setopt( 
		$curl_handle, 
		CURLOPT_HTTPHEADER, 
		array(
			"Authorization: {$librus_token[ 'token_type' ]} {$librus_token[ 'access_token' ]}",
			'User-Agent: Dalvik/2.1.0 (Linux; U; Android 5.1.1; A0001 Build/LMY48B)',
			'Host: api.librus.pl',
			'Connection: Keep-Alive',
			'Accept-Encoding: gzip',
		)
	);
	
	foreach( $notices_incomplete -> SchoolNotices as $notice )
		$url .= "{$notice -> AddedBy -> Id},";
	curl_setopt( $curl_handle, CURLOPT_URL, $url );
	
	$json_raw = curl_exec( $curl_handle );
	if( $json_raw === FALSE )
	{
		errorLog( 'Error downloading teacher list! Curl error: ' . curl_error( $curl_handle ) );
		return null;
	}
	
	$response = json_decode( $json_raw );
	if( isset( $response -> error ) )
	{
		errorLog( 'Error downloading teacher list! Librus API Error: ' . $response -> error );
		return null;
	}
		
	$users = array();
	foreach( $response -> Users as $user )
		$users[ $user -> Id ] = "{$user -> FirstName} {$user -> LastName}";
	foreach( $notices_incomplete -> SchoolNotices as $notice )
		$notice -> AddedBy -> Name = $users[ $notice -> AddedBy -> Id ];
	
	return $notices_incomplete;
}

function librusFetchAnnouncements( $teacher_blacklist )
{
	$curl_handle = curl_init();

	curl_setopt( $curl_handle, CURLOPT_FOLLOWLOCATION, 0 );
	curl_setopt( $curl_handle, CURLOPT_RETURNTRANSFER, 1 );
	
	$librus_token = obtainLibrusToken( $curl_handle );
	if( $librus_token == null )
		return null;
	$notices_incomplete = downloadSchoolNotices( $curl_handle, $librus_token );
	if( $notices_incomplete == null )
		return null;
	$notices = fillInTeacherNames( $curl_handle, $librus_token, $notices_incomplete );
	if( $notices == null )
		return null;
	
	$announcements = array();
	foreach( $notices -> SchoolNotices as $notice )
	{
		static $i = 0;
		
		$announcements[] = new Announcement();
		$announcements[$i] -> title = html_entity_decode( $notice -> Subject );
		$announcements[$i] -> id = $notice -> Id;
		$announcements[$i] -> date_posted = $notice -> StartDate;
		$announcements[$i] -> date_modified = '-';
		$announcements[$i] -> contents = html_entity_decode( $notice -> Content );
		$announcements[$i] -> contents_md5 = hash( "md5", $announcements[$i]->contents );
		$announcements[$i] -> author = $notice -> AddedBy -> Name;
		
		$ignored = false;
		foreach( $teacher_blacklist as $blacklisted_teacher )
		{
			if( $announcements[$i] -> author == $blacklisted_teacher )
			{
				array_pop( $announcements );
				$ignored = true;
				break;
			}
		}
		if( $ignored == false )
			$i++;
	}
	
	curl_close( $curl_handle );
	return $announcements;
}
