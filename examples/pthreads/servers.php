<?php
/**
 * This creates server list very quickly and outputs JSON
 * to servers.json file as well as prints to console
 * 
 * Utilizes pthreads extension (requires thread safety) 
 */

use jacklul\q3serverlist\MasterServer;
use jacklul\q3serverlist\Server;

// Thread safe PHP is required!
if (!ZEND_THREAD_SAFE) {
    exit('Thread safety is required' . PHP_EOL);
}

require __DIR__ . '/vendor/autoload.php';
$start = microtime(true);

class ServerScan extends Thread
{
    private $server;
    private $print;
    public $result;

    public function __construct(Server $server, $print = false)
    {
        $this->server = $server;
        $this->print  = $print;
    }

    public function run(): void
    {
        // It's important to cast to specific type, otherwise object might be destroyed
        // before you're able to fetch it
        $this->result = (array) [
            //'info' => $this->server->getInfo(),
            'status' => $this->server->getStatus(),
        ];

        if ($this->print) {
            if (
                (isset($this->result['status']) && is_array($this->result['status'])) || 
                (isset($this->result['info']) && is_array($this->result['info']))
            ) {
                $address = $this->result['info']['address'] ?? $this->result['status']['address'] ?? null;
                $port    = $this->result['info']['port'] ?? $this->result['status']['port'] ?? null;

                if (empty($address) || empty($port)) {
                    return;
                }

                $hostname   = $this->result['info']['hostname'] ?? $this->result['status']['sv_hostname'] ?? '?';
                $game       = $this->result['info']['game'] ?? $this->result['status']['gamename'] ?? '?';
                $map        = $this->result['info']['mapname'] ?? $this->result['status']['mapname'] ?? '?';
                $numPlayers = $this->result['info']['clients'] ?? $this->result['status']['numplayers'] ?? 0;
                $maxPlayers = $this->result['info']['sv_maxclients'] ?? $this->result['status']['sv_maxclients'] ?? '?';

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
                print $this->server->getAddress() . ':' . $this->server->getPort() . PHP_EOL;
            }
        }
    }
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

// This does not have to be equal to number of CPUs, needs to be kept reasonable
// The maximum I observed that was possible is NUMBER_OF_PROCESSORS * 6 (on a 6/12 processor)
// anything above that doesn't run threads at all
$threads = getenv('NUMBER_OF_PROCESSORS') ? getenv('NUMBER_OF_PROCESSORS') : 4;

// Pass number of threads as argument to the script to override
if (isset($argv[1]) && is_numeric($argv[1])) {
    $threads = $argv[1];
}

$results = [];
for ($i = 0, $iMax = count($servers); $i < $iMax; $i++) {
    echo ($i + 1) . '/' . $iMax . "\r"; // Progress display

    // Initialize thread object
    $stack[] = new ServerScan($servers[$i], true);

    // When stack exceeds maximum number of threads...
    if ($i >= $iMax || count($stack) >= $threads) {
        // Launch them all...
        for ($j = 0, $jMax = count($stack); $j < $jMax; $j++) {
            $stack[$j]->start();
        }

        // Then collect the results...
        for ($j = 0, $jMax = count($stack); $j < $jMax; $j++) {
            $stack[$j]->join();

            if (
                isset($stack[$j]) && is_object($stack[$j]) &&
                ($result = $stack[$j]->result) !== null
            ) {
                $result = (array) $stack[$j]->result;

                if (
                    (isset($result['status']) && is_array($result['status'])) || 
                    (isset($result['info']) && is_array($result['info']))
                ) {
                    $results[] = $result;
                }
            }
        }

		// Clear the stack and repeat...
        $stack = [];
    }
}

print str_repeat('-', 100) . PHP_EOL;
print 'Servers: ' . count($results) . '/' . count($servers) . ' (' . $serversOriginal . ')' . PHP_EOL;
print 'Time: ' . round(microtime(true) - $start, 2) . ' seconds' . PHP_EOL;
print 'Threads: ' . (count($servers) * $threads) . '/' . $threads . PHP_EOL;

if (!empty($results)) {
    // No need for JSON_PRETTY_PRINT, it's good for debugging purposes only
    // Can also use JSON_INVALID_UTF8_SUBSTITUTE which will output weird characters like � in hostnames
    file_put_contents(
        __DIR__ . '/servers.json',
        json_encode((array) $results, JSON_PRETTY_PRINT | JSON_INVALID_UTF8_IGNORE)
    );
}
