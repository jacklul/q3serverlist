<?php

use jacklul\q3serverlist\Server;

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
