# Quake 3 Server List

Simple library for querying Quake 3 based master servers and it's game servers.

- Version `1.x.x` uses `fsockopen`
- Version `2.x.x` uses `sockets` extension

_For legacy version check [old](https://github.com/jacklul/q3serverlist/tree/old) branch._

### Master Server Compatibility

Any Quake 3 based master server, including:

* Quake 3 Arena (`master.quake3arena.com:27950`, protocol 68)
* Enemy Territory (`etmaster.idsoftware.com:27950`, protocol 84)
* Return to Castle Wolfenstein (`wolfmaster.idsoftware.com:27950`, protocol 57)
* Jedi Knight 2 (`masterjk2.ravensoft.com:29060`, protocol 16)
* Jedi Knight: Jedi Academy (`masterjk3.ravensoft.com:29060`, protocol 26)
* Call of Duty 4: Modern Warfare (`cod4master.activision.com:20810`, protocol 6)

... and more!

## Installation

Install with [Composer](https://github.com/composer/composer):

```bash
$ composer require jacklul/q3serverlist
```

## Usage

```php
use jacklul\q3serverlist\MasterServer;
use jacklul\q3serverlist\Server;

require(__DIR__ . '/vendor/autoload.php');

$ms = new MasterServer('master.quake3arena.com', 27950, 68);
$servers = $ms->getServers(); // Second call will always return cached data, same with Server->getInfo and Server->getStatus

/** @var Server $server */
foreach ($servers as $server) { 
	$info = $server->getInfo();	// 'getinfo' request usually returns map name
	
	// Find first server with map 'q3dm17' (The Longest Yard) and print it's status
	if (isset($info['mapname']) && $info['mapname'] === 'q3dm17') {
		print_r($server->getStatus());
		break;
	}
}

// You can get status/info variables magically like this:
$server->getMapname();

// To get variables that include '_' in their name use capitalization:
$server->getSvMaxclients(); // (sv_maxclients)
```

More examples available in [examples](/examples) directory.

## License

See [LICENSE](LICENSE).
