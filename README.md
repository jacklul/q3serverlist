# Quake 3 Server List Script #

Simple library for querying Quake 3 based master servers and it's game servers.

_For older version check [old](https://github.com/jacklul/q3serverlist/tree/old) branch._

##### Master Server Compatibility:
* Quake 3 Arena
* Enemy Territory
* Jedi Knight 2
* Jedi Knight: Jedi Academy
* Return to Castle Wolfenstein
* Call of Duty
* Call of Duty 2
* Call of Duty United Offensive

... and any other Quake 3 based master server.

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

$ms = new MasterServer('master.jkhub.org', 29060, 26);
$servers = $ms->getServers(); // Second call will always return cached data, same with Server->getInfo and Server->getStatus

// Find first japlus server and print it's status
foreach($servers as $server) {
	$info = $server->getInfo();	// 'getinfo' request usually returns mod name/directory
	
	if (isset($info['game']) && $info['game'] === 'japlus') {
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
