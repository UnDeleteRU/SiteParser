<?php

namespace Undelete\SiteStat;

use Psr\Http\Message\ResponseInterface;
use Sunra\PhpSimple\HtmlDomParser;

class ContinuousParserStrategy
{
    private $parser;

    public function __construct(Parser $parser)
    {
        $this->parser = $parser;
    }

    public function processResponse(ResponseInterface $response, $baseUrl)
    {
        $baseHost = parse_url($baseUrl, PHP_URL_HOST);
        $baseScheme = parse_url($baseUrl, PHP_URL_SCHEME);
        $basePath = parse_url($baseUrl, PHP_URL_PATH);

        if (strrpos($basePath, '/') !== false) {
            $basePath = substr($basePath, 0, strrpos($basePath, '/') + 1);
        }

        $dom = HtmlDomParser::str_get_html($response->getBody());

        if (!$dom) {
            return;
        }

        $elements = $dom->find('a[href]');

        foreach ($elements as $element) {
            $url = $element->href;
            $parts = parse_url($url);

            if (empty($parts['path']) && !isset($parts['query'])) {
                continue;
            }

            if (isset($parts['host']) && ($parts['host'] != $baseHost)) {
                continue;
            }

            if (isset($parts['scheme']) && ($parts['scheme'] != $baseScheme)) {
                continue;
            }

            if (!isset($parts['host'])) {
                if ($url{0} == '/') {
                    $url = $baseScheme . '://' . $baseHost . $url;
                } else {
                    $url = $baseScheme . '://' . $baseHost . str_replace("/../", "/", $basePath . $url);
                }
            }

            $this->parser->add($url);
        }
    }
}
