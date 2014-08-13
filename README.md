# Quake 3 Server List Script #

Simple command-line script to fetch all servers from a Quake 3 based master server and then save it to a file for easier parsing. [Example](http://my.jacklul.com/mb2servers) of how it can be used :)

## Requirements:
* php-cli
* [GameQ](http://gameq.sourceforge.net)

## Usage:
Script is divided into 3 seperated actions:
* getservers - fetch all servers from master server and put addresses of online ones to DB file
* refreshlist - get data from all servers that are present in DB file and write it to list file
* cleanup - clean DB from all offline servers

_For best results set up crontab rules, usually executing getservers every 5 minutes and cleanup once a week is sufficient._

## Master Server Compatibility:
This script is compatible with most of the Quake 3 based master servers, including:
* Quake 3 Arena
* Enemy Territory
* Jedi Knight 2
* Jedi Knight: Jedi Academy
* Return to Castle Wolfenstein
* Call of Duty
* Call of Duty 2
* Call of Duty United Offensive
