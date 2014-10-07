# Quake 3 Server List Script #

Simple command-line script to fetch all servers from a Quake 3 based master server and then save it to a file for easier parsing. [Example](http://my.jacklul.com/mb2servers) of how it can be used. :)

## Requirements
* php-cli _OR_ decent web hosting

## Usage

Fetch all servers from master server and put addresses of online ones to DB file

CLI:
```
$ php q3serverlist.php getservers
```
Webspace:
```
/q3serverlist.php?action=getservers
```

-----------------

Get data from all servers that are present in DB file and write it to list file

CLI:
```
$ php q3serverlist.php refreshlist
```
Webspace:
```
/q3serverlist.php?action=refreshlist
```

-----------------

Clean DB from all offline servers or those not meeting filtering criteria

CLI:
```
$ php q3serverlist.php cleanup
```
Webspace:
```
/q3serverlist.php?action=cleanup
```

-----------------

Additionally to secure access to the script on webspace set *$secret* variable to anything that only you will know, then pass it in GET like this:
```
/q3serverlist.php?secret=mysecret&action=refreshlist
```

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
