
<?php
/**
 * This creates server list very quickly and outputs JSON
 * to servers.json file as well as prints to console
 * 
 * Utilizes amphp/parallel package (requires thread safety) 
 * and pthreads extension
 * 
 * This is the fastest and most reliable way of scanning
 * the masters server in with multiple threads.
 */

use Amp\Parallel\Worker;
use Amp\Promise;
use jacklul\q3serverlist\MasterServer;

// Thread safe PHP is required!
if (!ZEND_THREAD_SAFE) {
    exit('Thread safety is required' . PHP_EOL);
}

require __DIR__ . '/vendor/autoload.php';
$start = microtime(true);

// Fetch the server list
$servers = (new MasterServer('master.quake3arena.com', 27950, 68))->getServers();
if (!$servers) {
    exit;
}

// Scan only 100 servers
$serversOriginal = count($servers);
shuffle($servers);
$servers = array_splice($servers, 0, 100);

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
    if (
        (isset($result['status']) && is_array($result['status'])) || 
        (isset($result['info']) && is_array($result['info']))
    ) {
        $results[] = $result;
    }
}

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
