<?php

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
		$link_data = [
			'message' 	=> mb_convert_encoding( $this, 'UTF-8' ),
			'created_time' 	=> $this -> date_posted,
		];
		$response = $facebook_handle -> post( "/me/feed", $link_data );
		$graph_node = $response -> getGraphNode();
		if( array_key_exists( 'id', $graph_node -> asArray() ) )
		{
			$this -> fb_id = $graph_node[ 'id' ];
			
			$statement = $mysql_connection->prepare( "INSERT INTO librus_announcements( id, title, author, contents, contents_md5, date_posted, date_modified, fb_id ) VALUES( :id, :title, :author, :contents, :contents_md5, :date_posted, :date_modified, :fb_id )" );
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
		$link_data = array();
		$response = $facebook_handle -> delete( "/{$this->fb_id}", $link_data );
		$graph_node = $response -> getGraphNode();
		
		if( array_key_exists( 'success', $graph_node -> asArray() )  )
		{
			$statement = $mysql_connection -> prepare( "DELETE FROM librus_announcements WHERE fb_id = '{$this->fb_id}'" );
			$statement -> execute();
		}
	}
}
	
function databaseFetchAnnouncements( &$mysql_connection )
{
	$database_data = array();

	$statement = $mysql_connection -> prepare( "SELECT * FROM librus_announcements" );
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

/* !!! This function will be broken if site layout is changed !!! */
function librusRipAnnouncementsFromSource( $html )
{
	//Rip the relevant part of the announcement page, contained between <form></form> tags
	$pos1 = strpos ( $html, '<form' );
	$pos2 = strpos ( $html, '</form', $pos1 );
	$html = substr( $html, $pos1, $pos2 - $pos1 + 7 );

	//Rip the announcements from between the <td></td> tags into an array of Announcement objects
	$librus_data = array();
	$pos1 = strpos( $html, '<td' );
	$i = 0;
	while( $pos1 !== FALSE )
	{
		$librus_data[] = new Announcement();
		
		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus_data[$i] -> title = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2 - $pos1 ) ) );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus_data[$i] -> author = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2 - $pos1 ) ) );
		
		$librus_data[$i] -> id = hash( "md5", $librus_data[$i]->title . $librus_data[$i]->author );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus_data[$i] -> date_posted = substr( $html, $pos1, $pos2 - $pos1 );
		$librus_data[$i] -> date_modified = '-';

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		$librus_data[$i] -> contents = html_entity_decode( strip_tags( substr( $html, $pos1, $pos2 - $pos1 ) ) );
		$librus_data[$i] -> contents_md5 = hash( "md5", $librus_data[$i]->contents );

		$pos1 = strpos ( $html, '<td', $pos1 );
		$pos1 = strpos ( $html, '>', $pos1 ) + 1;
		$pos2 = strpos ( $html, '</td', $pos1 );
		
		$pos1 = strpos( $html, '<td', $pos1 );
		$i++;
	}
	
	//The array is in reverse chronological order (most recent announcements first), so it has to be reversed
	return array_reverse( $librus_data );
}
