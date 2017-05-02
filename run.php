<?php

namespace Undelete\SiteStat;

require_once 'vendor/autoload.php';
require_once 'src/Parser.php';

$parser = new Parser('https://www.restoclub.ru/spb/search');
$parser->run();
