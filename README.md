# Quake 3 Server List Script #

Simple command-line script to fetch all servers from a Quake 3 based master server and then save it to a file for easier parsing. [Example](http://my.jacklul.com/mb2servers) of how it can be used. :)

## Requirements
* php-cli

## Usage
```
$ php q3serverlist.php getservers
```
will fetch all servers from master server and put addresses of online ones to DB file
```
$ php q3serverlist.php refreshlist
```
will get data from all servers that are present in DB file and write it to list file
```
$ php q3serverlist.php cleanup
```
will clean DB from all offline servers or those not meeting filtering criteria

_My advice is to set up crontab rules for this script, usually executing getservers every 5 minutes, refreshlist every 1 minute and cleanup once a week is sufficient._

## Master Server Compatibility
* Quake 3 Arena
* Enemy Territory
* Jedi Knight 2
* Jedi Knight: Jedi Academy
* Return to Castle Wolfenstein
* Call of Duty
* Call of Duty 2
* Call of Duty United Offensive

... and any other Quake 3 based master server.
