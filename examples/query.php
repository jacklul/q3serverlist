<?php
/**
 * This allows to simply query any server from command line.
 *
 * Usage: php query.php 127.0.0.1:29070
 */

use jacklul\q3serverlist\Server;

require __DIR__ . '/../vendor/autoload.php';

if (!isset($argv[1]) && (!isset($argv[2]) || strpos($argv[1], ':') === false)) {
    exit('Provide server IP and PORT as two arguments!' . PHP_EOL);
}

if (strpos($argv[1], ':') !== false) {
    $argv[1] = explode(':', $argv[1]);
    $argv[2] = $argv[1][1];
    $argv[1] = $argv[1][0];
}

$server = new Server($argv[1], (int) $argv[2]);

$data = $server->getInfo();
if ($data) {
    print 'GetInfo: ' . print_r($data, true);
}

$data = $server->getStatus();
if ($data) {
    print 'GetStatus: ' . print_r($data, true);
}
