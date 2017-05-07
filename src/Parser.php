<?php

namespace Undelete\SiteStat;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;
use Ratchet\Client\WebSocket;

class Parser
{
    private $urls;
    private $index;

    private $concurrency;

    private $strategy;
    private $stat;

    /**
     * @var WebSocket|null
     */
    private $socket;

    public function __construct($concurrency = 10)
    {
        $this->concurrency = $concurrency;
        $this->reset();

        $this->strategy = new ContinuousParserStrategy($this);
        $this->stat = new StatService();

        \Ratchet\Client\connect('ws://127.0.0.1:8080')->then(
            [$this, 'socketConnect'],
            function ($e) {
                echo "Could not connect: {$e->getMessage()}\n";
            }
        );
    }

    private function reset()
    {
        $this->urls = [];
        $this->index = 0;
    }

    public function socketConnect(WebSocket $connection)
    {
        $this->socket = $connection;

        $connection->on('message', function($message) use ($connection) {
            $request = json_decode($message, true);

            if (!$request || !isset($request['status']) || $request['status'] != 'nobot') {
                $connection->close();
            }

            $connection->removeAllListeners('message');
            $connection->on('message', function($message) use ($connection) {
                $request = json_decode($message, true);

                if (!is_array($request)) {
                    return;
                }

                if (!isset($request['cmd'])) {
                    return;
                }

                if ($request['cmd'] == 'parse' && isset($request['site'])) {
                    $host = parse_url($request['site'], PHP_URL_HOST);
                    $scheme = parse_url($request['site'], PHP_URL_SCHEME);

                    if (!$host || !$scheme) {
                        return;
                    }

                    $this->add($scheme . '://' . $host);
                    $this->run();
                }
            });

            $connection->send(json_encode(['kind' => 'bot', 'cmd' => 'init']));
        });

        $connection->send(json_encode(['cmd' => 'info']));
    }

    public function run()
    {
        $client = new Client();

        do {
            $pool = new Pool($client, $this->generator(), [
                'concurrency' => $this->concurrency,
                'options' => [
                    'on_stats' => [$this, 'statistic'],
                ]
            ]);

            $promise = $pool->promise();
            $promise->wait();
        } while ($this->index < count($this->urls));

        $this->stat->publishStat($this->socket, true);
        $this->reset();
    }

    public function add($url)
    {
        if (in_array($url, $this->urls)) {
            return;
        }

        if (count($this->urls) >= 150) {
            return;
        }

        $this->urls[] = $url;
    }

    public function generator()
    {
        while (isset($this->urls[$this->index])) {
            yield $this->index => new Request('GET', $this->urls[$this->index]);
            $this->index++;
        }
    }

    public function statistic(TransferStats $stats)
    {
        $this->stat->addStat($stats->getTransferTime(), $stats->getHandlerStat('size_download'));

        if (!$response = $stats->getResponse()) {
            return;
        }

        $contentType = $response->getHeader('Content-Type');

        if ($contentType) {
            $list = explode(";", $contentType[0]);
            if (stripos($list[0], 'text') === 0) {
                $type = 'text';
            } elseif (stripos($list[0], 'application') === 0) {
                $type = 'application';
            } elseif (stripos($list[0], 'image') === 0) {
                $type = 'image';
            } else {
                $type = 'unknown';
            }
        } else {
            $type = 'unknown';
        }

        if ($type == 'text' && 200 == $response->getStatusCode()) {
            $this->strategy->processResponse($response, (string) $stats->getEffectiveUri());
        }

        $this->stat->addResult($type, strlen($response->getBody()), $response->getStatusCode());
        $this->stat->publishStat($this->socket);
    }
}
