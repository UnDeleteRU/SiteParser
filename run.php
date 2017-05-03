<?php

require_once 'vendor/autoload.php';

if (!isset($argv[1])) {
    echo "you must specify site to parse";
    die;
}

$parser = new Undelete\SiteStat\Parser($argv[1]);
$parser->run();
