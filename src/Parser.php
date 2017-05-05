<?php

namespace Undelete\SiteStat;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\TransferStats;
use GuzzleHttp\Pool;
use GuzzleHttp\Client;

class Parser
{
    private $urls;

    private $index;

    private $concurrency;

    private $strategy;

    private $stat;

    public function __construct($url, $concurrency = 10)
    {
        $this->urls = [$url];
        $this->index = 0;
        $this->concurrency = $concurrency;

        $this->strategy = new ContinuousParserStrategy($this);

        $this->stat = new StatService();
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
    }
}
