<?php
/**
 * This echo's basic server listing to console, scanned procedurally one-by-one.
 */

use jacklul\q3serverlist\MasterServer;
use jacklul\q3serverlist\Server;

require __DIR__ . '/../vendor/autoload.php';
$start = microtime(true);

$ms = new MasterServer('master.quake3arena.com', 27950, 68);
if ($servers = $ms->getServers()) {
    $serversOriginal = count($servers);
    shuffle($servers);
    $servers = array_splice($servers, 0, 10); // Scan only 10 servers max
    
    /** @var Server $server */
    foreach ($servers as $server) {
        $server->getStatus();

        if (!empty($server->getSvHostname())) {
            print $server->getAddress() . ':' . $server->getPort() . "\t" .
                $server->getNumplayers() . '/' . $server->getSvMaxclients() . "\t" .
                $server->getSvHostname() . "\t" .
                $server->getMapname() . PHP_EOL;
        } else {
            print $server->getAddress() . ':' . $server->getPort() . PHP_EOL;
        }
    }

    print str_repeat('-', 100) . PHP_EOL;
    print 'Servers: ' . count($servers) . '/' . $serversOriginal . PHP_EOL;
    print 'Time: ' . round(microtime(true) - $start, 2) . ' seconds' . PHP_EOL;
}
