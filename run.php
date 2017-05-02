<?php

require_once 'vendor/autoload.php';

$parser = new Undelete\SiteStat\Parser('https://www.restoclub.ru/spb/search');
$parser->run();
