Adminer - Database management in a single PHP file
Adminer Editor - Data manipulation for end-users

http://www.adminer.org/
Supports: MySQL, PostgreSQL, SQLite, MS SQL, Oracle, SimpleDB, Elasticsearch
Requirements: PHP 5+
Apache License 2.0 or GPL 2

adminer/index.php - Run development version of Adminer
editor/index.php - Run development version of Adminer Editor
editor/example.php - Example customization
plugins/readme.txt - Plugins for Adminer and Adminer Editor
adminer/plugin.php - Plugin demo
compile.php - Create a single file version
lang.php - Update translations
tests/selenium.html - Selenium test suite

Modifications from the v4.0 development repo:
Mongo DB driver changes:
Added support for SQL command window to accept Mongo DB commnads
Added dump and export data in CSV from selected rows and entire Mongo DB tables
Added field rename, data updates, field remove functionality
Added conditional tests to support js commands for non-relational databases.
Added pagination for the Mongo db records
Added sorting of fields
Added search functionality
