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
 * This class represents single server
 *
 * @method string getAddress()
 * @method int    getPort()
 */
class Server
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
     * Data from getinfo query
     * 
     * @var array
     */
    protected $info = [];

    /**
     * Data from getstatus query
     * 
     * @var array
     */
    protected $status = [];

    /**
     * Initialize class and Connection object
     *
     * @param string $address
     * @param int    $port
     *
     * @throws InvalidArgumentException
     */
    public function __construct($address, $port)
    {
        if (!is_string($address)) {
            throw new InvalidArgumentException('Address must be a STRING!');
        }

        if (!is_int($port)) {
            throw new InvalidArgumentException('Port must be a NUMBER!');
        }

        $this->address    = $address;
        $this->port       = $port;
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
     * Send query to the server
     *
     * @param string $data
     * @param int    $timeout
     * @param int    $length
     *
     * @return string|bool
     */
    public function query($data, $timeout = 1, $length = 1000000)
    {
        $this->connection->setTimeout($timeout);

        if ($this->connection->connect() && $this->connection->write($data)) {
            return $this->connection->read($length);
        }

        return false;
    }

    /**
     * Send getinfo query to the server
     * 
     * @param int $timeout
     * @param int $length
     *
     * @return array|bool
     */
    public function getInfo($timeout = 1, $length = 1000000)
    {
        if (!empty($this->info)) {
            return $this->info;
        }

        if ($data = $this->query(str_repeat(chr(255), 4) . 'getinfo' . "\n", $timeout, $length)) {
            $vars = explode("\n", $data);

            if (isset($vars[1])) {
                $list['address'] = $this->address;
                $list['port']    = $this->port;

                $ret = explode("\\", substr($vars[1], 1, strlen($vars[1])));

                for ($i = 0, $iMax = count($ret); $i <= $iMax; $i += 2) {
                    if (isset($ret[$i], $ret[$i + 1])) {
                        $list[strtolower($ret[$i])] = $ret[$i + 1];
                    }
                }
                array_pop($list);

                return $this->info = $list;
            }
        }

        return false;
    }

    /**
     * Parse players data in the array
     * 
     * @param array $data
     *
     * @return array
     */
    private function parsePlayersData($data)
    {
        $players = [];

        for ($i = 2, $iMax = sizeof($data); $i < $iMax; $i++) {
            $infos = explode(' ', $data[$i], 3);

            $name = '';
            if (isset($infos[2])) {
                $name = explode('"', $infos[2]);

                if (isset($name[1])) {
                    $name = $name[1];
                }
            }

            $score = 0;
            if (isset($infos[0])) {
                $score = $infos[0];
            }

            $ping = 999;
            if (isset($infos[1])) {
                $ping = $infos[1];
            }

            $players[] = [
                'score' => $score,
                'ping'  => $ping,
                'name'  => $name,
            ];
        }

        return $players;
    }

    /**
     * Send getstatus query to the server
     * 
     * @param int $timeout
     * @param int $length
     *
     * @return array|bool
     */
    public function getStatus($timeout = 1, $length = 1000000)
    {
        if (!empty($this->status)) {
            return $this->status;
        }

        if ($data = $this->query(str_repeat(chr(255), 4) . 'getstatus' . "\n", $timeout, $length)) {
            $vars = explode("\n", $data);

            if (isset($vars[1])) {
                $list['address'] = $this->address;
                $list['port']    = $this->port;

                $ret = explode("\\", substr($vars[1], 1, strlen($vars[1])));

                for ($i = 0, $iMax = count($ret); $i <= $iMax; $i += 2) {
                    if (isset($ret[$i], $ret[$i + 1])) {
                        $list[strtolower($ret[$i])] = $ret[$i + 1];
                    }
                }
                array_pop($list);

                $players = $this->parsePlayersData($vars);
                array_pop($players);

                $list['players'] = $players;

                $list['numplayers'] = 0;
                if (isset($players[0]['ping'])) {
                    $list['numplayers'] = sizeof($players);
                }

                $list['numbots'] = 0;
                for ($i = 0, $iMax = sizeof($players); $i < $iMax; $i++) {
                    if ($players[$i]['ping'] === 0) {
                        $list['numbots']++;
                    }
                }

                return $this->status = $list;
            }
        }

        return false;
    }
}
