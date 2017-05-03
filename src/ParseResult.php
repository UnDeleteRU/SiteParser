<?php

namespace Undelete\SiteStat;

class ParseResult
{
    public $count;

    public $sizes = [];

    public $codes = [];

    public function addCode($code)
    {
        if (!isset($this->codes[$code])) {
            $this->codes[$code] = 1;
        } else {
            $this->codes[$code]++;
        }
    }
}
