#!/bin/bash
#
# WineHQ AppDB Database Setup Script
#

# EDIT for your DB settings
$DBNAME=apppdb
$DBUSER=appdb
$DBHOST=localhost
$DBPASS=lemonade

# Create User
echo Create the base user...
echo NOTE: When prompted, enter your database root password
echo NOTE: It is ok for this to fail if the user already exists
echo       there does not appear to be a way to create a user only
echo       if they do not exist so we have to live with a potential
echo       error after we try.
mysql -p -u root -h $DBHOST "CREATE USER $DBUSER IDENTIFIED BY '$DBPASS';"

# Create Database
echo Creating the database...
echo NOTE: When prompted, enter your database root password
mysql -p -u root -h $DBHOST "CREATE DATABASE IF NOT EXISTS $DBNAME; GRANT ALL ON $DBNAME.* TO '$DBUSER'@'%';"

# Create Tables and Data
echo Creating the appdb database, and tables...
echo NOTE: When prompted, enter your the appdb password
cat appdb_tables.sql appdb_data.sql | mysql -p -u $DBUSER -h $DBHOST $DBNAME

# Done
echo Done!
echo Note: you may need to create bugzilla tables if needed, see create_bugzilla.sh
