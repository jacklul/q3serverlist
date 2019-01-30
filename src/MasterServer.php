<?php
/**
 * This file is part of the q3serverlist package.
 *
 * (c) Jack'lul <jacklulcat@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace jacklul\q3serverlist;

/**
 * This class represents single master server
 *
 * @method string getAddress()
 * @method int    getPort()
 * @method int    getProtocol()
 */
class MasterServer
{
    use MagicGetterTrait;

    /**
     * @var string
     */
    private $address;

    /**
     * @var int
     */
    private $port;

    /**
     * @var int
     */
    private $protocol;

    /**
     * @var Server[]
     */
    private $servers = [];

    /**
     * @param string $address
     * @param int    $port
     * @param int    $protocol
     */
    public function __construct($address, $port, $protocol)
    {
        if (!is_string($address)) {
            throw new \InvalidArgumentException('Address must be a STRING!');
        }

        if (!is_int($port)) {
            throw new \InvalidArgumentException('Port must be a NUMBER!');
        }

        if (!is_int($protocol)) {
            throw new \InvalidArgumentException('Protocol must be a NUMBER!');
        }

        $this->address = $address;
        $this->port = $port;
        $this->protocol = $protocol;
    }

    /**
     * @param string $keywords
     * @param int    $timeout
     *
     * @return array|bool
     */
    public function getServers($keywords = "empty full", $timeout = 1)
    {
        if (!empty($this->servers)) {
            return $this->servers;
        }

        if (!is_string($keywords)) {
            throw new \InvalidArgumentException('Keywords must be a STRING!');
        }

        if (!is_int($timeout)) {
            throw new \InvalidArgumentException('Protocol must be a NUMBER!');
        }

        if ($socket = fsockopen('udp://' . $this->address, $this->port)) {
            stream_set_blocking($socket, 0);
            stream_set_timeout($socket, $timeout);
            
            fwrite($socket, str_repeat(chr(255), 4) . 'getservers ' . $this->protocol . ' ' . $keywords . "\n");
            
            $time = time() + $timeout;
            $returned = "";
            while ($time > time()) {
                $returned .= fgets($socket);
            }
            
            $servers = array();
            for ($i = 0; $i < (strlen($returned) - 10); $i++) {
                if ($returned[$i] == "\\" && $returned[$i+7] == "\\") {
                    $ip = ord($returned[$i+1]) . "." . ord($returned[$i+2]) . "." . ord($returned[$i+3]) . "." . ord($returned[$i+4]);
                    $port = (ord($returned[$i+5])<<8) + ord($returned[$i+6]);

                    array_push($servers, new Server($ip, $port));
                }
            }
            
            return $this->servers = $servers;
        }
        
        return false;
    }
}
