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

    public function __construct($url, $concurrency = 10)
    {
        $this->urls = [$url];
        $this->index = 0;
        $this->concurrency = $concurrency;

        $this->strategy = new ContinuousParserStrategy($this);
    }

    public function run()
    {
        $client = new Client();


        do {
            $pool = new Pool($client, $this->generator(), [
                'concurrency' => $this->concurrency,
                'fulfilled' => [$this, 'success'],
                'rejected' =>  [$this, 'fail'],
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

        if (count($this->urls) >= 20) {
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

    public function success(Response $response, $index)
    {
        $this->strategy->processResponse($response, $this->urls[$index]);
        echo "succes " . $this->urls[$index] . "\r\n";
    }

    public function fail($reason, $index)
    {
        echo "fail $reason\r\n";
    }

    public function statistic(TransferStats $stats)
    {
        echo "stats\r\n";
    }
}
