
<?php
/**
 * This creates server list very quickly and outputs JSON
 * to servers.json file as well as prints to console
 * 
 * Utilizes amphp/parallel package (requires thread safety) 
 */

use Amp\Parallel\Worker;
use Amp\Promise;
use jacklul\q3serverlist\MasterServer;

// Thread safe PHP is required!
if (!ZEND_THREAD_SAFE) {
    exit('Thread safety is required' . PHP_EOL);
}

require __DIR__ . '/vendor/autoload.php';

// Fetch the server list
$servers = (new MasterServer('master.jkhub.org', 29060, 26))->getServers();

// Prepare promises
$promises = [];
foreach ($servers as $server) {
    $promises[$server->getAddress() . ':' . $server->getPort()] = Worker\enqueueCallable('scanServer', $server, true);
}

// Wait for results
$responses = Promise\wait(Promise\all($promises));

// Collect results
$results = [];
foreach ($responses as $result) {
    $results[] = $result;
}

print 'Servers: ' . count($results) . '/' . count($servers) . PHP_EOL;

if (!empty($results)) {
    // No need for JSON_PRETTY_PRINT, it's good for debugging purposes only
    // Can also use JSON_INVALID_UTF8_SUBSTITUTE which will output weird characters like � in hostnames
    file_put_contents(
        __DIR__ . '/servers.json',
        json_encode((array) $results, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE)
    );
}
