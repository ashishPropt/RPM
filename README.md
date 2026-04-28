# PropTXChange — RPM

Rental Property Management platform built with PHP + MySQL.

## Setup
1. Create MySQL database and run `database/schema.sql`
2. Copy `config/config.php` and fill in your DB credentials and admin invite code
3. Upload all files to your server
4. Visit the site and register an admin account

## Structure
```
index.php          Front controller — single entry point
config/config.php  Database credentials and app settings
core/db.php        MySQLDB class (PDO)
core/auth.php      AppAuth class (bcrypt + sessions)
core/flash.php     One-time flash messages
pages/             One file per page
assets/            CSS and JS
database/          schema.sql and seed.sql
```
