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
use Socket;

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
     * @var Socket|resource
     */
    protected $socket;

    /**
     * Timeout value in seconds
     *
     * @var bool
     */
    protected $timeout;

    /**
     * Whenever we are connected or not
     *
     * @var bool
     */
    protected $isConnected = false;

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
     * @return void
     * @throws RuntimeException
     */
    public function __construct($address, $port)
    {
        if (!extension_loaded('sockets')) {
            throw new RuntimeException('Sockets extension not loaded');
        }

        $this->address = $address;
        $this->port    = $port;
    }

    /**
     * Set socket timeout
     *
     * @param integer $seconds
     *
     * @return void
     * @throws InvalidArgumentException
     */
    public function setTimeout($seconds)
    {
        if (!is_int($seconds)) {
            throw new InvalidArgumentException('Seconds must be an INTEGER');
        }

        $this->timeout = $seconds;
    }

    /**
     * Set/unset the timeout option on socket handle
     *
     * @param integer $seconds
     * @param integer $microseconds
     *
     * @return void
     */
    private function setTimeoutOption($seconds = 1, $microseconds = 0)
    {
        if (!is_resource($this->socket) && !$this->socket instanceof Socket) {
            throw new RuntimeException('Socket handle is not valid');
        }

        if (!socket_set_option($this->socket, SOL_SOCKET, SO_RCVTIMEO, ['sec' => $seconds, 'usec' => $microseconds])) {
            throw new RuntimeException('Unable to set option on socket: ' . socket_strerror(socket_last_error()));
        }

        if (!socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, ['sec' => $seconds, 'usec' => $microseconds])) {
            throw new RuntimeException('Unable to set option on socket: ' . socket_strerror(socket_last_error()));
        }
    }

    /**
     * Initiate connection on a socket
     *
     * @return bool
     */
    public function connect()
    {
        if ($this->isConnected === true) {
            return true;
        }

        $this->socket = @socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);

        if (!is_resource($this->socket) && !$this->socket instanceof Socket) {
            throw new RuntimeException('Unable to create socket: ' . socket_strerror(socket_last_error()));
        }

        if (!socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, true)) {
            throw new RuntimeException('Unable to set option on socket: ' . socket_strerror(socket_last_error()));
        }

        $status = @socket_connect($this->socket, $this->address, $this->port);

        if ($this->timeout === null) {
            $this->setTimeoutOption(ini_get('default_socket_timeout'));

            return;
        }

        $this->setTimeoutOption($this->timeout);

        if ($status === true) {
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
        if ($this->isConnected === false && $this->connect() === false) {
            return false;
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
        if ($this->isConnected === false && $this->connect() === false) {
            throw new InvalidArgumentException('Connection not established');
        }

        if (!is_numeric($length)) {
            throw new InvalidArgumentException('Length must be a NUMBER');
        }

        $this->buffer = '';
        $len          = 0;

        while ($recvLen = @socket_recv($this->socket, $buffer, $length, 0)) {
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
                if ($this->timeout !== null && preg_match('/EOT/', $bufferEnd)) {
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
     * Return raw data from the buffer
     *
     * @return string
     */
    public function getBuffer()
    {
        return $this->buffer;
    }

    /**
     * Class destruction
     *
     * @return void
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Close the socket
     *
     * @return bool
     */
    public function close()
    {
        if (is_resource($this->socket) || $this->socket instanceof Socket) {
            return @socket_close($this->socket);
        }

        return false;
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
}
