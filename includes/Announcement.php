<?php

	class Announcement
	{
		public $title;
		public $id;
		public $author;
		public $date_posted;
		public $date_modified;
		public $contents;
		public $contents_md5;
		public $fb_id;
		
		public function __toString()
		{
			return 
			"$this->title\r\n".
			"Dodał: $this->author\r\n".
			"Data publikacji: $this->date_posted\r\n".
			"Ostatnia aktualizacja: $this->date_modified\r\n".
			"\r\n$this->contents";
		}
	}