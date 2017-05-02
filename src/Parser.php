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

    private $concurrency;

    private $index;

    public function __construct($url, $concurrency = 10)
    {
        $this->urls = [$url];
        $this->index = 0;
        $this->concurrency = $concurrency;
    }

    public function run()
    {
        $client = new Client();

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
    }

    public function add($uri) {
        $this->urls[] = $uri;
    }

    public function generator()
    {
        while (isset($this->urls[$this->index])) {
            $uri = $this->urls[$this->index];
            yield new Request('GET', $uri);
            $this->index++;
        }
    }

    public function success(Response $response, $index)
    {
        echo "sucess $index \r\n";
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
