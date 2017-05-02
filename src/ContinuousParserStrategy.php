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

    public function processResponse(ResponseInterface $response)
    {
        $dom = HtmlDomParser::str_get_html($response->getBody());
        $elements = $dom->find('a[href]');

        foreach ($elements as $element) {
            $url = $element->href;
            $parts = parse_url($url);

            if (!isset($parts['path']) || isset($parts['query'])) {
                continue;
            }

            if (isset($parts['host']) && ($parts['host'] != $this->parser->getHost())) {
                continue;
            }

            if (!isset($parts['host'])) {
                $url = $this->parser->getHost() . ($url{0} == '/' ? '' : '/') . $url;
            }

            if (!isset($parts['scheme'])) {
                $url = $this->parser->getScheme() . '://' . $url;
            }

            $this->parser->add($url);
        }
    }
}
