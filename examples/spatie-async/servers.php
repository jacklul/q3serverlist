<?php
/**
 * This creates server list and outputs JSON
 * to servers.json file as well as prints to console
 * 
 * Utilizes spatie/async package
 */

use jacklul\q3serverlist\MasterServer;
use jacklul\q3serverlist\Server;
use Spatie\Async\Pool;

require __DIR__ . '/vendor/autoload.php';
$start = microtime(true);

function scanServer(Server $server, $print = false): ?array
{
    $result = [
        //'info' => $server->getInfo(),
        'status' => $server->getStatus(),
    ];

    if ($print) {
        if (
            (isset($result['status']) && is_array($result['status'])) || 
            (isset($result['info']) && is_array($result['info']))
        ) {
            $address = $result['info']['address'] ?? $result['status']['address'] ?? null;
            $port    = $result['info']['port'] ?? $result['status']['port'] ?? null;

            if (empty($address) || empty($port)) {
                return null;
            }

            $hostname   = $result['info']['hostname'] ?? $result['status']['sv_hostname'] ?? '?';
            $game       = $result['info']['game'] ?? $result['status']['gamename'] ?? '?';
            $map        = $result['info']['mapname'] ?? $result['status']['mapname'] ?? '?';
            $numPlayers = $result['info']['clients'] ?? $result['status']['numplayers'] ?? 0;
            $maxPlayers = $result['info']['sv_maxclients'] ?? $result['status']['sv_maxclients'] ?? '?';

            $hostname = preg_replace('/\^[0-9]{1}/', '', $hostname); // No color codes
            $hostname = preg_replace('/\s+/', ' ', $hostname); // No repeated whitespace

            $output[] = trim($hostname);
            $output[] = $game;
            $output[] = $map;
            $output[] = $numPlayers . '/' . $maxPlayers;

            $maxLen = 30;
            foreach ($output as &$part) {
                $part = substr($part, 0, $maxLen);

                if (strlen($part) < $maxLen) {
                    $part .= str_repeat(' ', $maxLen - strlen($part));
                }
            }

            print $address . ':' . $port . "\t" . implode("\t\t", $output) . PHP_EOL;
        } else {
            print $server->getAddress() . ':' . $server->getPort() . PHP_EOL;
        }
    }

    return $result;
}

// Fetch the server list
$servers = (new MasterServer('master.quake3arena.com', 27950, 68))->getServers();
if (!$servers) {
    exit;
}

// Scan only 100 servers
$serversOriginal = count($servers);
shuffle($servers);
$servers = array_splice($servers, 0, 100);

$pool = Pool::create();
$promises = [];
$results = [];
foreach ($servers as $server) {
    $pool[] = async(function () use ($server) {
        return scanServer($server, true);
    })->then(function ($output) use (&$results) {
        if (
            (isset($output['status']) && is_array($output['status'])) || 
            (isset($output['info']) && is_array($output['info']))
        ) {
            $results[] = $output;
        }
    })->catch(function (Exception $exception) {
        print $exception->getMessage();
    });
}

await($pool);

print str_repeat('-', 100) . PHP_EOL;
print 'Servers: ' . count($results) . '/' . count($servers) . ' (' . $serversOriginal . ')' . PHP_EOL;
print 'Time: ' . round(microtime(true) - $start, 2) . ' seconds' . PHP_EOL;

if (!empty($results)) {
    // No need for JSON_PRETTY_PRINT, it's good for debugging purposes only
    // Can also use JSON_INVALID_UTF8_SUBSTITUTE which will output weird characters like � in hostnames
    file_put_contents(
        __DIR__ . '/servers.json',
        json_encode((array) $results, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE)
    );
}
