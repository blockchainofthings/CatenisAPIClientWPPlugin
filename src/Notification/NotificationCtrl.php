<?php
/**
 * Created by claudio on 2018-12-21
 */

namespace Catenis\WP\Notification;

use Exception;
use DateTime;
use Catenis\WP\React\EventLoop\LoopInterface;
use Catenis\WP\React\Stream\ReadableResourceStream;
use Catenis\WP\Catenis\ApiClient as CatenisApiClient;
use Catenis\WP\Catenis\Exception\WsNotifyChannelAlreadyOpenException;

class NotificationCtrl
{
    private $clientUID;
    private $eventLoop;
    private $commPipe;
    private $inputPipeStream;
    private $checkParentAliveTimer;
    private $commCommand;
    private $isParentAlive = false;
    private $ctnApiClient;
    private $wsNofifyChannels = [];

    private static $logLevels = [
        'ERROR' => 10,
        'WARN' => 20,
        'INFO' => 30,
        'DEBUG' => 40,
        'TRACE' => 50,
        'ALL' => 100
    ];

    public static function execProcess($clientUID)
    {
        return exec('php ' . __DIR__ . '/catenis_notify_proc.php ' . $clientUID . ' > /dev/null &');
    }

    public static function logError($message)
    {
        self::logMessage('ERROR', $message);
    }

    public static function logWarn($message)
    {
        self::logMessage('WARN', $message);
    }

    public static function logInfo($message)
    {
        self::logMessage('INFO', $message);
    }

    public static function logDebug($message)
    {
        self::logMessage('DEBUG', $message);
    }

    public static function logTrace($message)
    {
        self::logMessage('TRACE', $message);
    }

    private static function logMessage($level, $message)
    {
        global $LOGGING, $LOG_LEVEL, $LOG;
        static $pid;

        if ($LOGGING && self::$logLevels[$level] <= self::$logLevels[$LOG_LEVEL]) {
            if (empty($pid)) {
                $pid = getmypid();
            }

            try {
                fwrite(
                    $LOG,
                    sprintf(
                        "%s - %-5s [%d]: %s\n",
                        (new DateTime())->format('Y-m-d\Th:i:s.u\Z'),
                        $level,
                        $pid,
                        $message
                    )
                );
                fflush($LOG);
            } catch (Exception $ex) {
            }
        }
    }

    private function setParentAlive()
    {
        $this->isParentAlive = true;
    }

    private function resetParentAlive()
    {
        $this->isParentAlive = false;
    }

    private function processParentDeath()
    {
        // Parent process (WordPress page using Catenis API client) has stopped responded.
        //  Just terminate process
        $this->terminate('Parent process stopped responding');
    }

    private function terminate($reason = '', $exitCode = 0)
    {
        global $EXIT_CODE;

        // Stop event loop and delete communication pipes before terminating process
        if ($this->checkParentAliveTimer) {
            $this->eventLoop->cancelTimer($this->checkParentAliveTimer);
        }

        $this->eventLoop->stop();
        $this->commPipe->delete();

        if (!empty($reason)) {
            if ($exitCode !== 0) {
                self::logError('Terminated: ' . $reason);
            } else {
                self::logInfo('Terminated: ' . $reason);
            }
        }

        $EXIT_CODE = $exitCode;
    }

    private function processCommands()
    {
        while ($this->commCommand->hasReceivedCommand()) {
            $command = $this->commCommand->getNextCommand();
            self::logDebug('Process command: ' . print_r($command, true));

            switch (($commandType = CommCommand::commandType($command))) {
                case CommCommand::INIT_CMD:
                    $this->processInitCommand($command);
                    break;

                case CommCommand::PING_CMD:
                    // Nothing to do
                    break;

                case CommCommand::OPEN_NOTIFY_CHANNEL_CMD:
                    $this->processOpenNotifyChannelCommand($command);
                    break;

                case CommCommand::CLOSE_NOTIFY_CHANNEL_CMD:
                    $this->processCloseNotifyChannelCommand($command);
                    break;

                default:
                    self::logDebug('Unknown communication command received: ' . $commandType);
            }
        }
    }

    private function processInitCommand($command)
    {
        $errorMsg = '';
        $terminate = false;

        if (!isset($this->ctnApiClient)) {
            try {
                // Convert client options from stdClass object to array
                $ctnClientOptions = json_decode(json_encode($command->data->ctnClientOptions), true);
                $ctnClientOptions['eventLoop'] = $this->eventLoop;

                // Instantiate new Catenis API client
                $this->ctnApiClient = new CatenisApiClient(
                    $command->data->ctnClientCredentials->deviceId,
                    $command->data->ctnClientCredentials->apiAccessSecret,
                    $ctnClientOptions
                );
                self::logDebug('Process init command: Catenis API client successfully instantiated');
            } catch (Exception $ex) {
                $errorMsg = $ex->getMessage();
                $terminate = true;
            }
        } else {
            $errorMsg = 'Notification process already initialized';
        }

        try {
            // Send response
            if (!empty($errorMsg)) {
                $this->commCommand->sendInitResponseCommand(false, $errorMsg);

                if ($terminate) {
                    $this->terminate('Failure to initialize process', -5);
                }
            } else {
                $this->commCommand->sendInitResponseCommand(true);
            }
        } catch (Exception $ex) {
            // Error sending init response. Terminate process
            $this->terminate('Error sending init response: ' . $ex->getMessage(), -4);
        }
    }

    private function processOpenNotifyChannelCommand($command)
    {
        // Make sure that it had been successfully initialized
        if (isset($this->ctnApiClient)) {
            $eventName = $command->data->eventName;
            $wsNotifyChannel = $this->ctnApiClient->createWsNotifyChannel($eventName);
            self::logDebug('Process open notification channel: WS notify channel object successfully created');

            $this->handleNotifyChannelEvents($eventName, $wsNotifyChannel);

            // Open notification channel
            $wsNotifyChannel->open()->then(function () use ($eventName, $wsNotifyChannel) {
                self::logDebug('Process open notification channel: open channel succeeded');
                // Notification channel successfully opened. Save its reference...
                $this->wsNofifyChannels[$eventName] = $wsNotifyChannel;

                try {
                    // ... and send command notifying that notification channel was successfully opened
                    $this->commCommand->sendNotifyChannelOpenedCommand($eventName);
                } catch (Exception $ex) {
                    self::logError('Error sending notification channel opened (success) command: ' . $ex->getMessage());
                }
            }, function (Exception $ex) use ($eventName, $wsNotifyChannel) {
                self::logDebug('Process open notification channel: open channel failed');
                if ($ex instanceof WsNotifyChannelAlreadyOpenException) {
                    // Notification channel already opened. Make sure we have its reference saved...
                    $this->wsNofifyChannels[$eventName] = $wsNotifyChannel;
                } else {
                    // Error opening notification channel
                    self::logError('Error opening notification channel: ' . $ex->getMessage());
                    try {
                        // Send command notifying that there was an error while opening notification channel
                        $this->commCommand->sendNotifyChannelOpenedCommand($eventName, false, $ex->getMessage());
                    } catch (Exception $ex) {
                        self::logError('Error sending notification channel opened (failure) command: '
                            . $ex->getMessage());
                    }
                }
            });
        }
    }

    private function processCloseNotifyChannelCommand($command)
    {
        // Make sure that it had been successfully initialized
        if (isset($this->ctnApiClient)) {
            $eventName = $command->data->eventName;

            if (isset($this->wsNofifyChannels[$eventName])) {
                $this->wsNofifyChannels[$eventName]->close();
            }
        }
    }

    private function handleNotifyChannelEvents($eventName, $wsNotifyChannel)
    {
        // Wire up event handlers
        $wsNotifyChannel->on('error', function ($error) use ($eventName) {
            self::logTrace('Notification channel error');
            try {
                // Send command back to parent process notifying of notification channel error
                $this->commCommand->sendNotifyChannelErrorCommand($eventName, $error);
            } catch (Exception $ex) {
                self::logError('Error sending notification channel error command: ' . $ex->getMessage());
            }
        });

        $wsNotifyChannel->on('close', function ($code, $reason) use ($eventName) {
            self::logTrace('Notification channel close');
            // Remove notification channel reference
            unset($this->wsNofifyChannels[$eventName]);

            try {
                // Send command back to parent process notifying that notification channel has been closed
                $this->commCommand->sendNotifyChannelClosedCommand($eventName, $code, $reason);
            } catch (Exception $ex) {
                self::logError('Error sending notification channel closed command: ' . $ex->getMessage());
            }
        });

        $wsNotifyChannel->on('notify', function ($data) use ($eventName) {
            self::logTrace('Notification channel notify');
            try {
                // Send command back to parent process notifying of new notification event
                $this->commCommand->sendNotificationCommand($eventName, $data);
            } catch (Exception $ex) {
                self::logError('Error sending notification command: ' . $ex->getMessage());
            }
        });
    }

    /**
     * NotificationCtrl constructor.
     * @param string $clientUID
     * @param LoopInterface $loop - Event loop
     * @param int $keepAliveInterval - Time (in seconds) for continuously checking whether parent process is still alive
     * @throws Exception
     */
    public function __construct($clientUID, LoopInterface $loop, $keepAliveInterval = 120)
    {
        $this->clientUID = $clientUID;
        $this->eventLoop = $loop;

        try {
            $this->commPipe = new CommPipe($clientUID, false);
        } catch (Exception $ex) {
            throw new Exception('Error opening communication pipe: ' . $ex->getMessage());
        }

        $this->inputPipeStream = new ReadableResourceStream($this->commPipe->getInputPipe(), $loop);
        $this->commCommand = new CommCommand($this->commPipe);

        // Wire up event to read command from input pipe
        $this->inputPipeStream->on('data', [$this, 'receiveCommand']);

        // Start timer to check if parent process is still alive
        $this->checkParentAliveTimer = $loop->addPeriodicTimer($keepAliveInterval, [$this, 'checkParentAlive']);
    }

    public function receiveCommand($data)
    {
        self::logTrace('Receive command handler: ' . print_r($data, true));
        // Command receive. Indicate that parent is alive
        $this->setParentAlive();
        $this->commCommand->parseCommands($data);
        $this->processCommands();
    }

    public function checkParentAlive()
    {
        self::logTrace('Check parent alive handler');
        if (!$this->isParentAlive) {
            $this->processParentDeath();
        } else {
            $this->resetParentAlive();
        }
    }
}
