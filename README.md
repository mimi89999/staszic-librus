# staszic-librus
This script copies announcements from the announcement page on Librus (synergia.librus.com) into a page's feed on Facebook.

To achieve this it logs in into the Librus site, downloads the announcement page, stores the data in a MySQL database (comparing it with existing data) and then calls a facebook app (connected with a facebook page) through the Graph api, using a facebook page token.

It is supposed to be set up to run in regular intervals, for example by scheduling a cron job.

A reset-mode deleting every post on the facebook page and all data in the database can be activated, by calling the script with a '--reset' parameter or with a 'reset' GET variable defined (eg. 127.0.0.1/RunScript.php?reset).

Useful links:

How to generate a permanent facebook page token:
http://stackoverflow.com/questions/32876100/get-page-access-token-with-facebook-api-5-0-php