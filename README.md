# staszic-librus

This script copies announcements from the announcement page on Librus (synergia.librus.com) into a page's feed on Facebook.

Here's how it works: <br />
1. Log into the Librus site.
2. Download the announcement page.
3. Process the page and rip relevant data (+compare with existing data).
4. Store the data in a MySQL database.
5. Use a page token to publish the data on Facebook.
It is supposed to be set up to run in regular intervals, for example by scheduling a cron job.

A reset-mode deleting every post on the facebook page and all data in the database can be activated, by calling the script with a `--reset` parameter or with a `reset` GET variable defined (eg. `127.0.0.1/RunScript.php?reset`).

The database should have 2 tables: `librus_announcements` and `date_modified` formatted in a following way: <br />
**librus_announcements** <br />
8 columns <br />
>\#	Name			Type <br />
>\-------------------------------- <br />
>1.	id				VARCHAR(32) <br />
>2.	title			TEXT <br />
>3.	author			TEXT <br />
>4.	contents		TEXT <br />
>5.	contents_md5	VARCHAR(32) <br />
>6.	date_posted		VARCHAR(16) <br />
>7.	date_modified	VARCHAR(16) <br />
>8.	fb_id			TEXT
<br />
**last_update** <br />
1 column <br />
>#	Name			Type <br />
>\-------------------------------- <br />
>1.	time			VARCHAR(16) <br />

Useful links:
[How to generate a permanent facebook page token](http://stackoverflow.com/questions/32876100/get-page-access-token-with-facebook-api-5-0-php)