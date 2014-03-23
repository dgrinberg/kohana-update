#kohana-update


This is a Kohana (3.3) module that assists with the deployment of application updates, especially the database updates

It keeps track of the database version, by creating a table 'db_version'.  Every update is tracked in the database table.

## Usage

### Script files

 * save all scripts within a 'scripts' folder in the application
 * version each script appropriately, placing the version in the filename
 * one file can hold multiple queries, however separate all SQL statements with a semicolon (;)

### Filename structure

    {type}_{major}.{minor}.{build}.sql
	
Type = 
 * complete
 * update
 

## Installation

Clone the Git repository into your modules directory:

    $ git clone git://github.com/dgrinberg/kohana-update.git modules/update

*Or*, clone the repository as a submodule:

    $ git submodule add git://github.com/dgrinberg/kohana-update.git modules/update

You can now enable the module in your application's `bootstrap.php`:

```php
<?php
Kohana::modules(array(
    // ...
    'update' => MODPATH.'update', // Partial templates
    // ...
));
```
