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

    private $host;

    private $scheme;

    private $concurrency;

    private $strategy;

    public function __construct($url, $concurrency = 10)
    {
        $this->urls = [$url];
        $this->host = parse_url($url, PHP_URL_HOST);
        $this->scheme = parse_url($url, PHP_URL_SCHEME);
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

    public function getHost()
    {
        return $this->host;
    }

    public function getScheme()
    {
        return $this->scheme;
    }

    public function add($url)
    {
        if (count($this->urls) >= 200) {
            return;
        }

        $this->urls[] = $url;
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
        $this->strategy->processResponse($response);
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
