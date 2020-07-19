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
 * @method int    getProtocol()
 * @method array  getPlayers()
 */
class Server
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
     * @var array
     */
    private $info = [];

    /**
     * @var array
     */
    private $status = [];

    /**
     * @param string $address
     * @param int    $port
     */
    public function __construct($address, $port)
    {
        if (!is_string($address)) {
            throw new InvalidArgumentException('Address must be a STRING!');
        }

        if (!is_int($port)) {
            throw new InvalidArgumentException('Port must be a NUMBER!');
        }

        $this->address = $address;
        $this->port    = $port;
    }

    /**
     * @param int $timeout
     * @param int $length
     *
     * @return array|bool
     */
    public function getInfo($timeout = 1, $length = 10000)
    {
        if (!empty($this->info)) {
            return $this->info;
        }

        if (!is_int($timeout)) {
            throw new InvalidArgumentException('Timeout must be a NUMBER!');
        }

        if ($socket = fsockopen('udp://' . $this->address, $this->port)) {
            stream_set_timeout($socket, $timeout);
            fwrite($socket, str_repeat(chr(255), 4) . 'getinfo' . "\n");
            $data = fread($socket, $length);
            fclose($socket);

            if ($data) {
                $vars = explode("\n", $data);

                if (isset($vars[1])) {
                    $ret = explode("\\", substr($vars[1], 1, strlen($vars[1])));

                    for ($i = 0, $iMax = count($ret); $i <= $iMax; $i += 2) {
                        $list[strtolower(@$ret[$i])] = @$ret[$i + 1];
                    }
                    array_pop($list);

                    $list['address'] = $this->address;
                    $list['port']    = $this->port;

                    return $this->info = $list;
                }
            }
        }

        return false;
    }

    /**
     * @param int $timeout
     * @param int $length
     *
     * @return array|bool
     */
    public function getStatus($timeout = 1, $length = 10000)
    {
        if (!empty($this->status)) {
            return $this->status;
        }

        if (!is_int($timeout)) {
            throw new InvalidArgumentException('Timeout must be a NUMBER!');
        }

        if ($socket = fsockopen('udp://' . $this->address, $this->port)) {
            stream_set_timeout($socket, $timeout);
            fwrite($socket, str_repeat(chr(255), 4) . 'getstatus' . "\n");
            $data = fread($socket, $length);
            fclose($socket);

            if ($data) {
                $vars = explode("\n", $data);

                if (isset($vars[1])) {
                    $ret = explode("\\", substr($vars[1], 1, strlen($vars[1])));

                    for ($i = 0, $iMax = count($ret); $i <= $iMax; $i += 2) {
                        $list[strtolower(@$ret[$i])] = @$ret[$i + 1];
                    }
                    array_pop($list);

                    $list['address'] = $this->address;
                    $list['port']    = $this->port;

                    $players = array();
                    for ($i = 2, $iMax = sizeof($vars); $i < $iMax; $i++) {
                        $infos = explode(' ', $vars[$i], 3);

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

                        $players[] = ['score' => $score, 'ping' => $ping, 'name' => $name];
                    }

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
        }

        return false;
    }
}
