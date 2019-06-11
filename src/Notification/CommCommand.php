<?php
/**
 * Created by claudio on 2018-12-22
 */

namespace Catenis\WP\Notification;

use stdClass;
use Exception;
use Catenis\WP\Evenement\EventEmitterInterface;
use Catenis\WP\Evenement\EventEmitterTrait;

class CommCommand implements EventEmitterInterface
{
    use EventEmitterTrait;

    const INIT_CMD = 'init';
    const PING_CMD = 'ping';
    const OPEN_NOTIFY_CHANNEL_CMD = 'open_notify_channel';
    const CLOSE_NOTIFY_CHANNEL_CMD = 'close_notify_channel';
    const INIT_RESPONSE_CMD = 'init_response';
    const NOTIFICATION_CMD = 'notification';
    const NOTIFY_CHANNEL_OPENED_CMD = 'notify_channel_opened';
    const NOTIFY_CHANNEL_ERROR_CMD = 'notify_channel_error';
    const NOTIFY_CHANNEL_CLOSED_CMD = 'notify_channel_closed';

    private static $commandSeparator = '|';

    private $commPipe;
    private $receivedCommands;

    /**
     * @param $command
     * @param mixed|null $data
     * @throws Exception
     */
    private function sendCommand($command, $data = null)
    {
        $cmdObj = new stdClass();

        $cmdObj->cmd = $command;

        if (!empty($data)) {
            $cmdObj->data = $data;
        }

        $this->commPipe->send(self::$commandSeparator . json_encode($cmdObj));
    }

    public static function commandType(stdClass $command)
    {
        return !empty($command->cmd) && is_string($command->cmd) ? $command->cmd : '';
    }

    /**
     * CommCommand constructor.
     * @param CommPipe $commPipe
     */
    public function __construct(CommPipe $commPipe)
    {
        $this->commPipe = $commPipe;
        $this->receivedCommands = [];
    }

    /**
     * @param stdClass $ctnClientData
     * @throws Exception
     */
    public function sendInitCommand(stdClass $ctnClientData)
    {
        $this->sendCommand(self::INIT_CMD, $ctnClientData);
    }

    /**
     * @throws Exception
     */
    public function sendPingCommand()
    {
        $this->sendCommand(self::PING_CMD);
    }

    /**
     * @param string $eventName
     * @throws Exception
     */
    public function sendOpenNotifyChannelCommand($eventName)
    {
        $this->sendCommand(self::OPEN_NOTIFY_CHANNEL_CMD, [
            'eventName' => $eventName
        ]);
    }

    /**
     * @param string $eventName
     * @throws Exception
     */
    public function sendCloseNotifyChannelCommand($eventName)
    {
        $this->sendCommand(self::CLOSE_NOTIFY_CHANNEL_CMD, [
            'eventName' => $eventName
        ]);
    }

    /**
     * @param bool $success
     * @param string|null $error
     * @throws Exception
     */
    public function sendInitResponseCommand($success = true, $error = null)
    {
        $cmdData = new stdClass();
        $cmdData->success = $success;

        if (!$success && !empty($error)) {
            $cmdData->error = $error;
        }

        $this->sendCommand(self::INIT_RESPONSE_CMD, $cmdData);
    }

    /**
     * @param string $eventName
     * @param stdClass $eventData
     * @throws Exception
     */
    public function sendNotificationCommand($eventName, stdClass $eventData)
    {
        $this->sendCommand(self::NOTIFICATION_CMD, [
            'eventName' => $eventName,
            'eventData' => $eventData
        ]);
    }

    /**
     * @param $eventName
     * @param bool $success
     * @param string|null $error
     * @throws Exception
     */
    public function sendNotifyChannelOpenedCommand($eventName, $success = true, $error = null)
    {
        $cmdData = new stdClass();
        $cmdData->eventName = $eventName;
        $cmdData->success = $success;

        if (!$success && !empty($error)) {
            $cmdData->error = $error;
        }

        $this->sendCommand(self::NOTIFY_CHANNEL_OPENED_CMD, $cmdData);
    }

    /**
     * @param string $eventName
     * @param string $error
     * @throws Exception
     */
    public function sendNotifyChannelErrorCommand($eventName, $error)
    {
        $this->sendCommand(self::NOTIFY_CHANNEL_ERROR_CMD, [
            'eventName' => $eventName,
            'error' => $error
        ]);
    }

    /**
     * @param string $eventName
     * @param int $code
     * @param string $reason
     * @throws Exception
     */
    public function sendNotifyChannelClosedCommand($eventName, $code, $reason)
    {
        $this->sendCommand(self::NOTIFY_CHANNEL_CLOSED_CMD, [
            'eventName' => $eventName,
            'code' => $code,
            'reason' => $reason
        ]);
    }

    /**
     * @param string $data
     */
    public function parseCommands($data)
    {
        if (is_string($data) && !empty($data)) {
            $dataChunks = explode(self::$commandSeparator, $data);

            foreach ($dataChunks as $idx => $jsonCmd) {
                if (!empty($jsonCmd)) {
                    $command = json_decode($jsonCmd);

                    if (!empty($command) && $command instanceof stdClass && !empty($command->cmd)
                            && is_string($command->cmd)) {
                        switch ($command->cmd) {
                            case self::INIT_CMD:
                            case self::PING_CMD:
                            case self::OPEN_NOTIFY_CHANNEL_CMD:
                            case self::CLOSE_NOTIFY_CHANNEL_CMD:
                            case self::INIT_RESPONSE_CMD:
                            case self::NOTIFICATION_CMD:
                            case self::NOTIFY_CHANNEL_OPENED_CMD:
                            case self::NOTIFY_CHANNEL_ERROR_CMD:
                            case self::NOTIFY_CHANNEL_CLOSED_CMD:
                                // Store received command
                                $this->receivedCommands[] = $command;
                                break;
                        }
                    }
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    public function receive()
    {
        $this->parseCommands($this->commPipe->receive());

        return $this->hasReceivedCommand();
    }

    public function hasReceivedCommand()
    {
        return !empty($this->receivedCommands);
    }

    public function getNextCommand()
    {
        return array_shift($this->receivedCommands);
    }
}
