<?php
/**
 * This echo's basic server listing to console, scanned procedurally one-by-one.
 */

use jacklul\q3serverlist\MasterServer;
use jacklul\q3serverlist\Server;

require __DIR__ . '/../vendor/autoload.php';

$ms      = new MasterServer('master.jkhub.org', 29060, 26);
$servers = $ms->getServers('empty full', 1);

print 'Servers: ' . count($servers) . PHP_EOL;

/** @var Server $server */
foreach ($servers as $server) {
    $server->getStatus();

    if (!empty($server->getSvHostname())) {
        print $server->getAddress() . ':' . $server->getPort() . "\t" .
            $server->getNumplayers() . '/' . $server->getSvMaxclients() . "\t" .
            $server->getSvHostname() . "\t" .
            $server->getMapname() . PHP_EOL;
    }
}
