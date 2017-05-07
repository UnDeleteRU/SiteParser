<?php

namespace Undelete\SiteStat;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class SocketComponent implements MessageComponentInterface
{
    private $clients;

    /**
     * @var ConnectionInterface|null
     */
    private $bot;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);
    }

    public function onMessage(ConnectionInterface $conn, $msg)
    {
        $request = json_decode($msg, true);

        if (!is_array($request)) {
            return;
        }

        if (!isset($request['cmd'])) {
            return;
        }

        if (isset($request['kind']) && $request['kind'] == 'bot') {
            if ($request['cmd'] == 'init') {
                $this->bot = $conn;
            } elseif ($request['cmd'] == 'stat') {
                foreach ($this->clients as $client) {
                    if ($client != $this->bot) {
                        $client->send($msg);
                    }
                }
            }
        } else {
            $botActive = $this->bot instanceof ConnectionInterface;

            if ($request['cmd'] == 'info') {
                $conn->send(json_encode(['status' => $botActive ? 'ready' : 'nobot']));
            } elseif ($request['cmd'] == 'parse' && isset($request['site'])) {
                if ($botActive) {
                    $this->bot->send($msg);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        if ($this->bot == $conn) {
            $this->bot = null;
        }

        $this->clients->detach($conn);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
    }
}
