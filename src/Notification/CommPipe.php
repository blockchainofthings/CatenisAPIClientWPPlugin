<?php
/**
 * Created by claudio on 2018-12-21
 */

namespace Catenis\WP\Notification;

use \Exception;


class CommPipe {
    const SEND_COMM_MODE = 0x1;
    const RECEIVE_COMM_MODE = 0x2;
    
    private $clientUID;
    private $isParent;
    private $commMode;
    private $inputFifoPath;
    private $outputFifoPath;
    private $inputFifo;
    private $outputFifo;
    private $inputFifoDidNotExist;
    private $outputFifoDidNotExist;

    /**
     * CommPipe constructor.
     * @param string $clientUID - Unique ID identifying a specific instance of a WordPress page using the
     *                             Catenis API client plugin
     * @param bool $isParent - Indicates whether this is being called from the plugin's process
     * @param int $commMode - Type of communication required
     * @throws Exception
     */
    function __construct($clientUID, $isParent = true, $commMode = self::SEND_COMM_MODE | self::RECEIVE_COMM_MODE) {
        $this->clientUID = $clientUID;
        $this->isParent = $isParent;
        $this->commMode = $commMode;
        
        $baseFifoPathName = __DIR__ . '/../../io/' . $clientUID;

        if ($isParent) {
            $this->inputFifoPath = $baseFifoPathName . '.down';
            $this->outputFifoPath = $baseFifoPathName . '.up';
        }
        else {
            $this->inputFifoPath = $baseFifoPathName . '.up';
            $this->outputFifoPath = $baseFifoPathName . '.down';
        }

        $this->inputFifoDidNotExist = true;

        // Make sure that fifos are created
        if (!file_exists($this->inputFifoPath)) {
            if ($isParent) {
                if (!posix_mkfifo($this->inputFifoPath, 0600)) {
                    throw new Exception(sprintf('Error creating communication input fifo: ' . posix_strerror(posix_get_last_error())));
                }
            }
        }
        else {
            $this->inputFifoDidNotExist = false;
        }

        $this->outputFifoDidNotExist = true;

        if (!file_exists($this->outputFifoPath)) {
            if ($isParent) {
                if (!posix_mkfifo($this->outputFifoPath, 0600)) {
                    // Delete other fifo to be consistent
                    @unlink($this->inputFifoPath);
                    throw new Exception('Error creating communication output fifo: ' . posix_strerror(posix_get_last_error()));
                }
            }
        }
        else {
            $this->outputFifoDidNotExist = false;
        }

        // Open fifos as required
        if ($this->commMode & self::RECEIVE_COMM_MODE) {
            $this->inputFifo = fopen($this->inputFifoPath, 'r+');

            if ($this->inputFifo === false) {
                if ($isParent && !$this->werePipesAlreadyCreated()) {
                    // Delete pipes to be consistent
                    $this->delete();
                }

                throw new Exception('Error opening communication input fifo: ' . error_get_last()['message']);
            }

            stream_set_blocking($this->inputFifo, false);
        }

        if ($this->commMode & self::SEND_COMM_MODE) {
            $this->outputFifo = fopen($this->outputFifoPath, 'w+');

            if ($this->outputFifo === false) {
                if ($isParent && !$this->werePipesAlreadyCreated()) {
                    // Delete pipes to be consistent
                    $this->delete();
                }

                throw new Exception('Error opening communication output fifo: ' . error_get_last()['message']);
            }

            stream_set_blocking($this->outputFifo, false);
        }
    }
    
    function __destruct() {
        $this->close();
    }

    function getInputPipe() {
        return $this->inputFifo;
    }

    function werePipesAlreadyCreated() {
        return !$this->inputFifoDidNotExist || !$this->outputFifoDidNotExist;
    }

    function close() {
        if (is_resource($this->inputFifo)) {
            @fclose($this->inputFifo);
            $this->inputFifo = null;
        }

        if (is_resource($this->outputFifo)) {
            @fclose($this->outputFifo);
            $this->outputFifo = null;
        }
    }

    function delete() {
        $this->close();

        @unlink($this->inputFifoPath);
        @unlink($this->outputFifoPath);
    }

    /**
     * @param int $timeout - Timeout (in seconds) for waiting on data to receive
     * @return string - The data read
     * @throws Exception
     */
    function receive($timeout = 5) {
        if (!$this->inputFifo) {
            throw new Exception('Cannot receive data; input pipe not open');
        }

        $waitTimeout = 500000; // (0.5 sec) in microseconds
        $errorMsg = '';
        $dataReady = false;

        do {
            $read = [$this->inputFifo];
            $write = null;
            $except = null;

            $result = stream_select($read, $write, $except, 0, $waitTimeout);

            if ($result === false) {
                $errorMsg = error_get_last()['message'];
            }
            elseif ($result > 0) {
                $dataReady = true;
            }

            $timeout -= $waitTimeout * 0.000001;
        }
        while (!$dataReady && $timeout > 0 && empty($errorMsg));

        if (!empty($errorMsg)) {
            throw new Exception('Error waiting on input pipe to receive data: ' . $errorMsg);
        }
        elseif ($dataReady) {
            // Data available. Read it
            $data = '';

            do {
                $dataRead = fread($this->inputFifo, 1024);

                if ($dataRead === false) {
                    throw new Exception('Error reading data from pipe: ' . error_get_last()['message']);
                }

                $data .= $dataRead;
            }
            while (strlen($dataRead) > 0);
        }
        else {
            // No data available
            $data = '';
        }
        
        return $data;
    }

    /**
     * @param string $data - Data to send
     * @param int $timeout - Timeout (in seconds) for waiting for pipe to be ready to send data
     * @throws Exception
     */
    function send($data, $timeout = 15) {
        if (!$this->outputFifo) {
            throw new Exception('Cannot send data; output pipe not open');
        }

        $waitTimeout = 500000; // (0.5 sec) in microseconds
        $errorMsg = '';
        $pipeReady = false;
        $dataLength = strlen($data);
        $bytesToSend = $dataLength;

        do {
            do {
                $read = null;
                $write = [$this->outputFifo];
                $except = null;

                $result = stream_select($read, $write, $except, 0, $waitTimeout);

                if ($result === false) {
                    $errorMsg = error_get_last()['message'];
                }
                elseif ($result > 0) {
                    $pipeReady = true;
                }

                $timeout -= $waitTimeout * 0.000001;
            }
            while (!$pipeReady && $timeout > 0 && empty($errorMsg));

            if (!empty($errorMsg)) {
                throw new Exception('Error waiting on output pipe to send data: ' . $errorMsg);
            }
            elseif ($pipeReady) {
                // Pipe ready. Send data
                $bytesSent = fwrite($this->outputFifo, $data);

                if ($bytesSent === false) {
                    throw new Exception('Error writing data to pipe: ' . error_get_last()['message']);
                }

                $bytesToSend -= $bytesSent;
            }
            else {
                // Pipe did not become available. Data not sent
                throw new Exception('Pipe not available; data not sent');
            }
        }
        while ($bytesToSend > 0);
    }
}