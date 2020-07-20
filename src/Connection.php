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
use RuntimeException;

/**
 * This class represent single connection
 */
class Connection
{
    /**
     * Destination IP/hostname
     * 
     * @var string
     */
    protected $address;

    /**
     * Destination port
     * 
     * @var int
     */
    protected $port;

    /**
     * Socket handle
     * 
     * @var resource
     */
    protected $socket;

    /**
     * Whenever we are connected or not
     * 
     * @var bool
     */
    protected $isConnected = false;

    /**
     * Is custom timeout enabled
     * 
     * @var bool
     */
    protected $timeout = false;

    /**
     * Current read buffer
     * 
     * @var string
     */
    protected $buffer = '';

    /**
     * Constructor
     *
     * @param string $address
     * @param int    $port
     * 
     * @throws RuntimeException
     */
    public function __construct($address, $port)
    {
        if (!function_exists('socket_create')) {
            throw new RuntimeException('Function \'socket_create\' does not exist!');
        }

        $this->address = $address;
        $this->port    = $port;
        $this->socket  = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    }

    /**
     * Class destruction
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Returns socket resource
     *
     * @return resource
     */
    public function getSocket()
    {
        return $this->socket;
    }

    /**
     * Set socket timeout
     *
     * @param integer $seconds
     * @param integer $microseconds
     * 
     * @return void
     * @throws InvalidArgumentException
     */
    public function setTimeout($seconds = 1, $microseconds = 0)
    {
        if ($seconds === null) {
            $default_socket_timeout = ini_get('default_socket_timeout');
            socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec'=> $default_socket_timeout, 'usec' => 0]);
            socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec'=> $default_socket_timeout, 'usec' => 0]);
    
            $this->timeout = false;

            return;
        }

        if (!is_int($seconds)) {
            throw new InvalidArgumentException('Seconds must be an INTEGER!');
        }

        if (!is_int($microseconds)) {
            throw new InvalidArgumentException('Microseconds must be an INTEGER!');
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec'=> $seconds, 'usec' => $microseconds]);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec'=> $seconds, 'usec' => $microseconds]);

        $this->timeout = true;
    }

    /**
     * Initiate connection on a socket
     *
     * @return bool
     */
    public function connect()
    {
        $status = @socket_connect($this->socket, $this->address, $this->port);

        if ($status) {
            $this->isConnected = true;
        }

        return $status;
    }

    /**
     * Write to socket
     *
     * @param string $data
     * 
     * @return bool
     * @throws RuntimeException
     */
    public function write($data)
    {
        if ($this->isConnected === false) {
            throw new RuntimeException('Connection not established');
        }

        return @socket_write($this->socket, $data);
    }

    /**
     * Read all data from the socket
     *
     * @param integer $length
     * 
     * @return string|null
     * @throws RuntimeException
     * @throws InvalidArgumentException
     */
    public function read($length = 1000)
    {
        if ($this->isConnected === false) {
            throw new RuntimeException('Connection not established');
        }

        if (!is_numeric($length)) {
            throw new InvalidArgumentException('Length must be a NUMBER!');
        }

        // https://bugs.php.net/bug.php?id=48326
        if (!defined('MSG_DONTWAIT')) { 
            define('MSG_DONTWAIT', 0x20);
        }

        $this->buffer = '';
        $len = 0;

        while ($recvLen = @socket_recv($this->socket, $buffer, $length, MSG_DONTWAIT)) {
            $this->buffer .= $buffer;
            $bufferEnd = substr($buffer, -10);

            // EOF marks the end
            if (strpos($bufferEnd, 'EOF') !== false) {
                break;
            }

            // If $buffer length is equal or exceeds previous then there is probably more data coming
            if ($recvLen >= $len) {
                $len = $recvLen;

                // Assume more data is coming when $buffer ends with EOT and timeout is set
                if ($this->timeout !== false && preg_match('/EOT/', $bufferEnd)) {
                    continue;
                }
            }

            break;
        }

        if (!empty($this->buffer)) {
            return $this->buffer;
        }

        return null;
    }

    /**
     * Close the socket
     */
    public function close()
    {
        return @socket_close($this->socket);
    }

    /**
     * Return raw data from the buffer
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }
}