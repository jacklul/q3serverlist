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

use InvalidArgumentException;

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
     * Server IP/hostname
     *
     * @var string
     */
    protected $address;

    /**
     * Server port
     *
     * @var int
     */
    protected $port;

    /**
     * Connection object
     *
     * @var Connection
     */
    protected $connection;

    /**
     * Protocol version number
     *
     * @var int
     */
    protected $protocol;

    /**
     * Server list as array of Server objects
     *
     * @var Server[]
     */
    protected $servers = [];

    /**
     * Initialize class and Connection object
     *
     * @param string $address
     * @param int    $port
     * @param int    $protocol
     *
     * @throws InvalidArgumentException
     */
    public function __construct($address, $port, $protocol)
    {
        if (!is_string($address)) {
            throw new InvalidArgumentException('Address must be a STRING');
        }

        if (!is_int($port)) {
            throw new InvalidArgumentException('Port must be a NUMBER');
        }

        if (!is_int($protocol)) {
            throw new InvalidArgumentException('Protocol must be a NUMBER');
        }

        $this->address    = $address;
        $this->port       = $port;
        $this->protocol   = $protocol;
        $this->connection = new Connection($address, $port);
    }

    /**
     * Return Connection object
     *
     * @return Connection
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Parse returned data
     *
     * @param string $data
     *
     * @return array|null
     */
    private function parseData($data)
    {
        $servers = [];

        if (empty($data)) {
            return $servers;
        }

        for ($i = 0; $i < (strlen($data) - 10); $i++) {
            if ($data[$i] === "\\" && $data[$i + 7] === "\\") {
                $ip   = ord($data[$i + 1]) . '.' . ord($data[$i + 2]) . '.' . ord($data[$i + 3]) . '.' . ord($data[$i + 4]);
                $port = (ord($data[$i + 5]) << 8) + ord($data[$i + 6]);

                if ($ip === '0.0.0.0' || $port === '0') {
                    continue;
                }

                $servers[] = new Server($ip, $port);
            }
        }

        return $servers;
    }

    /**
     * Send getservers query to the server
     *
     * @param string $keywords
     * @param int    $timeout
     * @param int    $length
     *
     * @return array|bool
     * @throws InvalidArgumentException
     */
    public function getServers($keywords = 'empty full', $timeout = 1, $length = 1000000)
    {
        if (!empty($this->servers)) {
            return $this->servers;
        }

        if (!is_string($keywords) && $keywords !== null) {
            throw new InvalidArgumentException('Keywords must be a STRING');
        }

        $this->connection->setTimeout($timeout);

        if ($this->connection->write(str_repeat(chr(255), 4) . 'getservers ' . $this->protocol . ' ' . $keywords . "\n")) {
            return $this->servers = $this->parseData($this->connection->read($length));
        }

        return false;
    }
}
