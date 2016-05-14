<?php
/* Librus <-> Facebook announcement copier script
 * Copyright (c) 2016 Jakub Bartmiński
 *
 * This script copies announcements from the announcement page on Librus 
 * (synergia.librus.com) into a page's feed on Facebook.
 *
 * To achieve this it logs in into the Librus site, downloads the announcement
 * page, stores the data in a MySQL database (comparing it with existing data)
 * and then calls a facebook app, connected with a facebook page, through 
 * the Graph api, using a permanent page token.
 * 
 * It is supposed to be set up to run in regular intervals, for example
 * by scheduling a cron job.
 *
 * A reset-mode deleting every post on the facebook page and all data in the
 * database can be activated, by calling the script with a '--reset' parameter
 * or with a 'reset' GET variable defined (eg. 127.0.0.1/RunScript.php?reset).
 */
 
require_once( 'includes/ResetMode.php' );
require_once( 'includes/UpdateAnnouncements.php' );
require_once( 'includes/Logging.php' );

ini_set( 'max_execution_time', 300 );
mb_internal_encoding( 'UTF-8' );
date_default_timezone_set( 'Europe/Warsaw' );

if( isset( $_GET['reset'] ) || ( isset( $argv ) && count( $argv ) > 1 && $argv[1] == '--reset' ) )
	resetMode();
else
	updateAnnouncements();

echo 'Done!';
