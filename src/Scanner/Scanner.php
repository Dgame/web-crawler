<?php

namespace Doody\Crawler\Scanner;

use Dgame\HttpClient\HttpClient;
use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Mongo\Mongo;
use Doody\Crawler\Url\RelationProcedure;
use Doody\Crawler\Url\Url;
use Doody\Crawler\Url\UrlGuardian;
use function Dgame\Time\Unit\seconds;

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
        } else if (VERBOSE_LOG) {
            FileLogger::Instance()->log('Die Seite "%s" (childs: %d) wurde bereits besucht',
                                        $this->url->asString(),
                                        UrlGuardian::Instance()->countChildsOf($this->url));
        } else {
            FileLogger::Instance()->log('Die Seite "%s" wurde bereits besucht', $this->url->asString());
        }
    }

    /**
     *
     */
    private function scan()
    {
        $client = new HttpClient();
        $client->setTimeout(seconds(2))->setConnectionTimeout(seconds(2))->disable(CURLOPT_VERBOSE);

        $response = $client->get($this->url->asString())->send();
        if ($response->getStatus()->isSuccess()) {
            if (preg_match('#<body.*?>(.+?)<\/body>#isS', $response->getBody(), $matches)) {
                $this->content = base64_encode(gzdeflate($matches[1], 9));
                if (preg_match_all('#href="(.+?)"#iS', $matches[1], $matches)) {
                    FileLogger::Instance()->log('Die Seite "%s" hat %d Links', $this->url->asString(), count($matches[1]));
                    $this->traverse($matches[1]);
                } else {
                    FileLogger::Instance()->log('Die Seite "%s" hat keine Links', $this->url->asString());
                }
            } else {
                FileLogger::Instance()->log('Die Seite "%s" hat keinen body-Tag', $this->url->asString());
            }
        } else {
            FileLogger::Instance()->log('Die Seite "%s" hat keinen Content', $this->url->asString());
        }
    }

    /**
     * @param array $hrefs
     */
    private function traverse(array $hrefs)
    {
        foreach ($hrefs as $href) {
            $url = new Url($href);
            if (UrlGuardian::Instance()->shouldCrawl($url)) {
                $this->links[] = $url->asString();

                $relation = RelationProcedure::Link($this->url)->with($url);
                if (UrlGuardian::Instance()->shouldInsert($relation)) {
                    FileLogger::Instance()->log('Insert "%s" (parent war "%s")',
                                                $relation->getChild()->asString(),
                                                $relation->getParent()->asString());
                    if (!DEBUG) {
                        Mongo::Instance()->insert($relation, $this->content);
                    }
                }
            }
        }
    }
}