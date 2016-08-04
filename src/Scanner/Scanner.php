<?php

namespace Doody\Crawler\Scanner;

use Dgame\HttpClient\HttpClient;
use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Mongo\Mongo;
use Doody\Crawler\Url\RelationProcedure;
use Doody\Crawler\Url\Url;
use Doody\Crawler\Url\UrlGuardian;

/**
 * Class Scanner
 * @package Doody\Crawler\Scanner
 */
final class Scanner
{
    /**
     * @var Url|null
     */
    private $url = null;
    /**
     * @var null
     */
    private $content = null;
    /**
     * @var array
     */
    private $links = [];

    /**
     * ScanProcedure constructor.
     *
     * @param string $url
     *
     * @throws \Exception
     */
    public function __construct(string $url)
    {
        $this->url = new Url($url);

        //enforce($this->url->isValid())->orThrow('Invalid URL: ' . $url);
        if (!$this->url->isValid()) {
            throw new \Exception('Invalid URL: ' . $url);
        }

        $this->crawl();
    }

    /**
     * @return array
     */
    public function getLinks(): array
    {
        return $this->links;
    }

    /**
     *
     */
    private function crawl()
    {
        if (UrlGuardian::Instance()->shouldCrawl($this->url)) {
            FileLogger::Instance()->log('Scanne die Seite "%s"', $this->url->asString());

            $this->scan();
        } else {
            FileLogger::Instance()->log('Die Seite "%s" (childs: %d) wurde bereits besucht',
                                        $this->url->asString(),
                                        UrlGuardian::Instance()->countChildsOf($this->url));
        }
    }

    /**
     *
     */
    private function scan()
    {
        $client = new HttpClient();
        $client->verbose(false);

        $response = $client->get($this->url->asString())->send();
        if ($response->getStatusCode() < 300) {
            $doc = new \DOMDocument('1.0', 'utf-8');
            $doc->loadHTML($response->getBody());

            $this->content = base64_encode(gzdeflate($doc->getElementsByTagName('body')->item(0)->textContent, 9));

            $links = $doc->getElementsByTagName('a');
            FileLogger::Instance()->log('Die Seite "%s" hat %d Links', $this->url->asString(), $links->length);

            $this->traverseLinks($links);
        } else {
            FileLogger::Instance()->log('Die Seite "%s" hat keinen Content', $this->url->asString());
        }
    }

    /**
     * @param \DOMNodeList $list
     */
    private function traverseLinks(\DOMNodeList $list)
    {
        for ($i = 0; $i < $list->length; ++$i) {
            /** @var \DOMElement $link */
            $link = $list->item($i);
            if ($link->hasAttribute('href')) {
                $url = new Url($link->getAttribute('href'));
                if (UrlGuardian::Instance()->shouldCrawl($url)) {
                    $this->links[] = $url->asString();

                    $relation = RelationProcedure::Link($this->url)->with($url);
                    if (UrlGuardian::Instance()->shouldInsert($relation)) {
                        FileLogger::Instance()->log('Insert "%s" (parent war "%s")',
                                                    $relation->getChild()->asString(),
                                                    $relation->getParent()->asString());

                        Mongo::Instance()->insert($relation, $this->content);
                    }
                }
            }
        }
    }
}