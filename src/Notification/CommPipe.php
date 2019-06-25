<?php
/**
 * Created by claudio on 2018-12-21
 */

namespace Catenis\WP\Notification;

use \Exception;

class CommPipe
{
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
     * @param bool $createPipes - Indicates whether communication pipes should be created if they do not exist yet
     * @throws Exception
     */
    public function __construct(
        $clientUID,
        $isParent = true,
        $commMode = self::SEND_COMM_MODE | self::RECEIVE_COMM_MODE,
        $createPipes = false
    ) {
        $this->clientUID = $clientUID;
        $this->commMode = $commMode;

        // Make sure that directory used for interprocess communication exists
        $ipcDir = __DIR__ . '/../../io';
        
        if (!file_exists($ipcDir)) {
            if (!mkdir($ipcDir, 0700)) {
                // Make sure that it has not failed because directory already exists (errno = 17 (EEXIST))
                $lastError = posix_get_last_error();

                if ($lastError != 17) {
                    throw new Exception(sprintf(
                        'Error creating interprocess communication directory (%s): %s',
                        $ipcDir,
                        posix_strerror($lastError)
                    ));
                }
            }
        }
    
        $baseFifoPathName = $ipcDir . '/' . $clientUID;

        if ($isParent) {
            $this->inputFifoPath = $baseFifoPathName . '.down';
            $this->outputFifoPath = $baseFifoPathName . '.up';
        } else {
            $this->inputFifoPath = $baseFifoPathName . '.up';
            $this->outputFifoPath = $baseFifoPathName . '.down';
        }

        $this->inputFifoDidNotExist = true;

        // Check if fifos exist and create them as required
        if (!file_exists($this->inputFifoPath)) {
            if ($createPipes) {
                if (!posix_mkfifo($this->inputFifoPath, 0600)) {
                    // Make sure that it has not failed because file already exists (errno = 17 (EEXIST))
                    $lastError = posix_get_last_error();

                    if ($lastError != 17) {
                        throw new Exception(sprintf(
                            'Error creating communication input fifo (%s): %s',
                            $this->inputFifoPath,
                            posix_strerror($lastError)
                        ));
                    } else {
                        $this->inputFifoDidNotExist = false;
                    }
                }
            }
        } else {
            $this->inputFifoDidNotExist = false;
        }

        $this->outputFifoDidNotExist = true;

        if (!file_exists($this->outputFifoPath)) {
            if ($createPipes) {
                if (!posix_mkfifo($this->outputFifoPath, 0600)) {
                    // Make sure that it has not failed because file already exists (errno = 17 (EEXIST))
                    $lastError = posix_get_last_error();

                    if ($lastError != 17) {
                        // Delete other fifo to be consistent
                        @unlink($this->inputFifoPath);
                        throw new Exception(sprintf(
                            'Error creating communication output fifo (%s): %s',
                            $this->outputFifoPath,
                            posix_strerror($lastError)
                        ));
                    } else {
                        $this->outputFifoDidNotExist = false;
                    }
                }
            }
        } else {
            $this->outputFifoDidNotExist = false;
        }

        // Open fifos as required
        if (($this->commMode & self::RECEIVE_COMM_MODE) && file_exists($this->inputFifoPath)) {
            $this->inputFifo = fopen($this->inputFifoPath, 'r+');

            if ($this->inputFifo === false) {
                if ($createPipes && !$this->werePipesAlreadyCreated()) {
                    // Delete pipes to be consistent
                    $this->delete();
                }

                throw new Exception('Error opening communication input fifo: ' . error_get_last()['message']);
            }

            stream_set_blocking($this->inputFifo, false);
        }

        if (($this->commMode & self::SEND_COMM_MODE) && file_exists($this->outputFifoPath)) {
            $this->outputFifo = fopen($this->outputFifoPath, 'w+');

            if ($this->outputFifo === false) {
                if ($createPipes && !$this->werePipesAlreadyCreated()) {
                    // Delete pipes to be consistent
                    $this->delete();
                }

                throw new Exception('Error opening communication output fifo: ' . error_get_last()['message']);
            }

            stream_set_blocking($this->outputFifo, false);
        }
    }
    
    public function __destruct()
    {
        $this->close();
    }

    public function getInputPipe()
    {
        return $this->inputFifo;
    }

    public function pipesExist()
    {
        return file_exists($this->inputFifoPath) && file_exists($this->outputFifoPath);
    }

    public function werePipesAlreadyCreated()
    {
        return !$this->inputFifoDidNotExist || !$this->outputFifoDidNotExist;
    }

    public function close()
    {
        if (is_resource($this->inputFifo)) {
            @fclose($this->inputFifo);
            $this->inputFifo = null;
        }

        if (is_resource($this->outputFifo)) {
            @fclose($this->outputFifo);
            $this->outputFifo = null;
        }
    }

    public function delete()
    {
        $this->close();

        if (file_exists($this->inputFifoPath)) {
            @unlink($this->inputFifoPath);
        }

        if (file_exists($this->outputFifoPath)) {
            @unlink($this->outputFifoPath);
        }
    }

    /**
     * @param int $timeoutSec - Seconds component of timeout for waiting on data to receive
     * @param int $timeoutUSec - Microseconds component of timeout for waiting on data to receive
     * @return string - The data read
     * @throws Exception
     */
    public function receive($timeoutSec = 0, $timeoutUSec = 0)
    {
        if (!$this->inputFifo) {
            throw new Exception('Cannot receive data; input pipe not open');
        }

        $read = [$this->inputFifo];
        $write = null;
        $except = null;

        $result = stream_select($read, $write, $except, $timeoutSec, $timeoutUSec);

        if ($result === false) {
            throw new Exception('Error waiting on input pipe to receive data: ' . error_get_last()['message']);
        } else {
            $data = '';

            if ($result > 0) {
                // Data available. Read it
                do {
                    $dataRead = fread($this->inputFifo, 1024);
    
                    if ($dataRead === false) {
                        throw new Exception('Error reading data from pipe: ' . error_get_last()['message']);
                    }
    
                    $data .= $dataRead;
                } while (strlen($dataRead) > 0);
            }
        }
        
        return $data;
    }

    /**
     * @param string $data - Data to send
     * @param int $timeoutSec - Seconds component of timeout for waiting for pipe to be ready to send data
     * @param int $timeoutUSec - Microseconds component of timeout for waiting for pipe to be ready to send data
     * @throws Exception
     */
    public function send($data, $timeoutSec = 15, $timeoutUSec = 0)
    {
        if (!$this->outputFifo) {
            throw new Exception('Cannot send data; output pipe not open');
        }

        $dataLength = strlen($data);
        $bytesToSend = $dataLength;

        do {
            $read = null;
            $write = [$this->outputFifo];
            $except = null;

            $result = stream_select($read, $write, $except, $timeoutSec, $timeoutUSec);

            if ($result === false) {
                throw new Exception('Error waiting on output pipe to send data: ' . error_get_last()['message']);
            } elseif ($result > 0) {
                // Pipe ready. Send data
                $bytesSent = fwrite($this->outputFifo, $data);

                if ($bytesSent === false) {
                    throw new Exception('Error writing data to pipe: ' . error_get_last()['message']);
                }

                $bytesToSend -= $bytesSent;
            } else {
                // Pipe did not become available. Data not sent
                throw new Exception('Pipe not available; data not sent');
            }
        } while ($bytesToSend > 0);
    }
}
