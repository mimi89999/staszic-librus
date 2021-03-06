# staszic-librus
## Version 2.1

This script copies announcements from Librus Synergia into a page's feed on Facebook.



Here's how it works: <br />
1. Download the announcements using the Librus API.<br />
2. Store the data in a MySQL database (Compare with existing data).<br />
3. Use a page token to keep a Facebook page synchronized with the data in the database.<br />



It is supposed to be set up to run in regular intervals, for example by scheduling a cron job. <br />
The required database structure is detailed in 'config/databases.ini' file.



A reset-mode deleting every post on the facebook page and all data in the database can be activated, by calling the script with a `--reset` parameter or with a `reset` GET variable defined (eg. `127.0.0.1/RunScript.php?reset`).



Useful links:<br />
- [How to generate a permanent facebook page token](http://stackoverflow.com/questions/32876100/get-page-access-token-with-facebook-api-5-0-php)