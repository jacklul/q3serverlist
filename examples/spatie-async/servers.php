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

function scanServer(Server $server, $print = false): ?array
{
    $result = [
        //'info' => $server->getInfo(),
        'status' => $server->getStatus(),
    ];

    if ($print && is_array($result)) {
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
    }

    return $result;
}

// Fetch the server list
$servers = (new MasterServer('master.moviebattles.org', 29060, 26))->getServers();

$pool = Pool::create();
$promises = [];
$results = [];
foreach ($servers as $server) {
    $pool[] = async(function () use ($server) {
        return scanServer($server, true);
    })->then(function ($output) use (&$results) {
        if (is_array($output)) {
            $results[] = $output;
        }
    })->catch(function (Exception $exception) {
        print $exception->getMessage();
    });
}

await($pool);

print 'Servers: ' . count($results) . '/' . count($servers) . PHP_EOL;

if (!empty($results)) {
    // No need for JSON_PRETTY_PRINT, it's good for debugging purposes only
    // Can also use JSON_INVALID_UTF8_SUBSTITUTE which will output weird characters like � in hostnames
    file_put_contents(
        __DIR__ . '/servers.json',
        json_encode((array) $results, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE)
    );
}
