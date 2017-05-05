<?php

namespace Undelete\SiteStat;

class StatService
{
    const REFRESH_TIME = 0.5;
    const HORIZON_TIME = 5;

    private $stats;
    private $results;
    private $lastPublish;

    public function __construct()
    {
        $this->stats = [];
        $this->results = [];
        $this->lastPublish = 0;
    }

    public function addStat($time, $size)
    {
        $this->stats[] = [
            'time' => $time,
            'start' => microtime(true) - $time,
            'size' => $size,
        ];

        $this->publishStat();
    }

    public function addResult($type, $length, $code)
    {
        if (!isset($this->results[$type])) {
            $this->results[$type] = new ParseResult();
        }

        $this->results[$type]->count++;
        $this->results[$type]->sizes[] = $length;
        $this->results[$type]->addCode($code);
    }

    public function publishStat()
    {
        if ($this->lastPublish + self::REFRESH_TIME > microtime(true)) {
            return;
        }

        $index = count($this->stats) - 1;

        if ($index < 0) {
            return;
        }

        $this->lastPublish = microtime(true);
        $time = microtime(true) - self::HORIZON_TIME;
        $weight = 0;
        $sum = 0;
        $count = 0;

        do {
            $koef = $this->stats[$index]['start'] - $time;
            $sum += $koef * ($this->stats[$index]['size'] / $this->stats[$index]['time']);
            $weight += $koef;

            $count++;

            $index--;
        } while ($this->stats[$index]['start'] > $time);

        $result = [
            'speed' => $sum / $weight,
            'count' => $count / self::HORIZON_TIME,
        ];
    }
}
