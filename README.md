# WineHQ Application Database
https://gitlab.winehq.org/winehq/appdb

Maintainer:
    Jeremy Newman <jnewman@codeweavers.com>

Requirements:
 * PHP 8 or higher
 * MySQL (or MariaDB)
 * PHP-Curl
 * PHP-GD
 * PHP-Mail
 * PHP-Mail-Mime

Reccommended:
 * Apache 2.4+
 * PHP-FPM

## To install locally for testing/hacking:

1. Copy AppDB files to somewhere where your web server can access them.

2. ```cp include/config.php.sample include/config.php```

3. Edit ```include/config.php``` as you see fit, the default name of the database 
    used in the table creation step below is "apidb", you'll have to modify 
    these files if you change this in config.php

    **NOTE:** You must update the ```APPDB_ROOT``` entry to be YOUR url to the application database
    If this the APPDB_ROOT isn't correct your urls will point to the wrong pages.

    **NOTE:** if you've changed the name of the database by changing the define of
    *APPS_DB* in ```include/config.php``` you'll have to modify the *create database* and *use*
    entries in pretty much every file in the /tables/ directory. The recommendation is to
    leave the default value of APPS_DB to keep things simple

4. to create the database tables: ```cd tables; ./create_tables```

5. Look in include/config.php at the ```APPS_DBUSER``` and ```APPS_DBPASS``` defines. 
    Ensure that this user and password exist in your mysql users list and that this user
    has been granted 'SELECT', 'INSERT', 'UPDATE' and 'DELETE' privileges on the database
    defined by the value of ```APPS_DB``` in ```include/config.php```

6. Ensure your appdb directory has *AllowOverride* set to *All* in Apache config to let
    .htaccess use required settings.

7. An AppDB admin user has been automatically created while running the create_tables script

    **email:** admin@localhost **password:** testing

8. You can either set up a real bugzilla database or a fake one.
to create the fake bugzilla tables: ```cd tables; ./create_bugzilla_tables```
