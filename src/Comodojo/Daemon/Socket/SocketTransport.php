<?php namespace Comodojo\Daemon\Socket;

use \Comodojo\RpcClient\Interfaces\Transport as TransportInterface;
use \Comodojo\Httprequest\Httprequest;
use \phpseclib\Crypt\AES;
use \Psr\Log\LoggerInterface;
use \Comodojo\Exception\RpcException;
use \Comodojo\Exception\SocketException;
use \Exception;

class SocketTransport extends AbstractSocket implements TransportInterface {

    private $aes = null;

    /**
     * {@inheritdoc}
     */
    public function performCall(
        LoggerInterface $logger,
        $data,
        $content_type,
        $encrypt = false
    ) {

        try {

            $logger->debug("Connecting to socket");

            $this->connect();

            $logger->debug("Sending RPC data");

            $data = $this->can($data, $encrypt);

            $response = $this->send($content_type, $data);

            $this->close();

            $logger->debug("Decoding RPC response");

            $return = $this->uncan($response, $encrypt);

        } catch (SocketException $se) {

            $logger->error("Socket Transport error: ".$se->getMessage());

            throw $se;

        } catch (RpcException $re) {

            $logger->error("RPC Client error: ".$re->getMessage());

            throw $re;

        } catch (Exception $e) {

            $logger->critical("Generic Client error: ".$e->getMessage());

            throw $e;

        }

        return $return;

    }

    public static function create($handler, $read_buffer = null) {

        return new SocketTransport($handler, $read_buffer);

    }

    public function connect() {

        $this->socket = @socket_create(
            $this->socket_domain,
            $this->socket_type,
            $this->socket_protocol
        );

        if ( $this->socket === false ) {
            $error = self::getSocketError();
            throw new SocketException("Socket unavailable: $error");
        }

        $connect = @socket_connect(
            $this->socket,
            $this->socket_resource,
            $this->socket_port
        );

        if ( $connect === false ) {
            $error = self::getSocketError($this->socket);
            throw new SocketException("Cannot connect to socket: $error");
        }

        $greeter = $this->readGreeter();

        if ( $greeter->status != 'connected' ) {
            throw new SocketException("Socket connect failed: ".$greeter->status);
        }

        if ( $greeter->version != self::VERSION ) {
            throw new SocketException("Socket connect failed: socket interface version mismatch");
        }

        return $this;

    }

    public function close() {

        return socket_close($this->socket);

    }

    protected function send($content_type, $data) {

        $sent = $this->write($content_type, $data);

        // TODO: manage exceptions!

        $received = $this->read();

        if ( $received->status === false ) {
            throw new Exception($received->message);
        }

        return $received->message;

    }

    protected function write($content_type, $data) {

        $request = new Request();

        $request->content_type = $content_type;
        $request->message = $data;

        $datagram = $request->serialize()."\r\n";

        return socket_write($this->socket, $datagram, strlen($datagram));

    }

    protected function read() {

        $response = new Response();

        $datagram = $this->rawRead();

        if ( is_null($datagram) ) {
            $response->status = false;
            $response->message = "Server has gone away";
        } else if ( empty($datagram) ) {
            $response->status = false;
            $response->message = "No response received";
        } else {
            $response->unserialize($datagram);
        }

        return $response;

    }

    protected function readGreeter() {

        $greeter = new Greeter();

        $datagram = $this->rawRead();

        if ( is_null($datagram) ) {
            $greeter->status = 'greeter not received';
            return $greeter;
        }

        if ( $datagram === false ) {
            $greeter->status = 'server has gone away';
            return $greeter;
        }

        return $greeter->unserialize($datagram);

    }

    protected function rawRead() {

        $datagram = '';

        while ( true ) {
            $recv = @socket_read($this->socket, $this->read_buffer, PHP_NORMAL_READ);
            if ( $recv === false ) break;
            if ( $recv === 0 ) return null;
            $datagram .= $recv;
            if ( strstr($recv, PHP_EOL) ) break;
        }

        return trim($datagram);

    }

    private function can($data, $key) {

        if ( !empty($key) && is_string($key) ) {

            $this->aes = new AES();

            $this->aes->setKey($key);

            $return = 'comodojo_encrypted_request-'.base64_encode($this->aes->encrypt($data));

        } else {

            $return = $data;

        }

        return $return;

    }

    private function uncan($data, $key) {

        if ( !empty($key) && is_string($key) ) {

            if ( self::checkEncryptedResponseConsistency($data) === false ) throw new RpcException("Inconsistent encrypted response received");

            $return = $this->aes->decrypt(base64_decode(substr($data, 28)));

        } else {

            $return = $data;

        }

        return $return;

    }

    /**
     * Check if an encrypted envelope is consisent or not
     *
     * @param   string    $data
     *
     * @return  bool
     */
    private static function checkEncryptedResponseConsistency($data) {

        return substr($data, 0, 27) == 'comodojo_encrypted_response' ? true : false;

    }

}
