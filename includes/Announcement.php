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
	
	curl_setopt( $curl_handle, CURLOPT_URL, 'https://api.librus.pl/OAuth/Token' );
	curl_setopt( $curl_handle, CURLOPT_POST, 1 );
	$post_data = "grant_type=password&username={$librus_ini['login']}&password={$librus_ini['password']}&librus_rules_accepted=true&librus_mobile_rules_accepted=true";
	$content_length = strlen( $post_data );
	curl_setopt( 
		$curl_handle, 
		CURLOPT_POSTFIELDS, 
		$post_data
	);
	curl_setopt( 
		$curl_handle, 
		CURLOPT_HTTPHEADER, 
		array(
			"Authorization: Basic {$librus_ini['default_token']}",
			'Content-Type: application/x-www-form-urlencoded',
			"Content-Length: {$content_length}",
			'User-Agent: Dalvik/2.1.0 (Linux; U; Android 5.1.1; A0001 Build/LMY48B)',
			'Host: api.librus.pl',
			'Connection: Keep-Alive',
			'Accept-Encoding: gzip',
		)
	);
	
	$json_raw = curl_exec( $curl_handle );
	if( $json_raw === FALSE )
	{
		errorLog( 'Error obtaining token! Curl error: ' . curl_error( $curl_handle ) );
		return null;
	}
	
	$response = json_decode( $json_raw );
	if( isset( $response -> error ) )
	{
		errorLog( 'Error obtaining token! Librus API Error: ' . $response -> error );
		return null;
	}
	
	return [
		'access_token' 	=> $response -> access_token,
		'token_type'	=> $response -> token_type,
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

	//These Aren't the Droids You're Looking For
	curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYHOST, 0 );
	curl_setopt( $curl_handle, CURLOPT_SSL_VERIFYPEER, 0 );

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
