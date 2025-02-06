#!/bin/bash
#
# WineHQ AppDB Setup Script - Bugzilla Dev Tables
# Note: Optional script to create fake bugzilla tables
# so a development environment can work.
#

# EDIT for your DB settings
$DBNAME=bugs
$DBUSER=bugs
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

# Create Tables
echo Creating fake Bugzilla tables...
echo NOTE: When prompted, enter your bugzilla database password
cat bugzilla_tables.sql | mysql -u root -h $DBHOST -p $DBNAME
echo Done!