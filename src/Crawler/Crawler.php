<?php

namespace Doody\Crawler\Crawler;

use Dgame\HttpClient\HttpClient;
use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Mongo\Mongo;
use Doody\Crawler\Url\RelationProcedure;
use Doody\Crawler\Url\Url;
use Doody\Crawler\Url\UrlGuardian;
use function Dgame\Time\Unit\seconds;

/**
 * Class Crawler
 *
 * @package Doody\Crawler\Crawler
 */
final class Crawler
{
    /**
     * @var Url|null
     */
    private $url = null;
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
        $this->verify();
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
    private function verify()
    {
        if (UrlGuardian::Instance()->shouldCrawl($this->url)) {
            FileLogger::Instance()->log('Scanne die Seite "%s"', $this->url->asString());

            $this->crawl();
        } elseif (VERBOSE_LOG) {
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
    private function crawl()
    {
        $client = new HttpClient();
        $client->setTimeout(seconds(2))
               ->setConnectionTimeout(seconds(2))
               ->setOption(CURLOPT_ENCODING, '')
               ->disable(CURLOPT_VERBOSE);

        $response = $client->get($this->url->asString())->send();
        if ($response->getStatus()->isSuccess()) {
            $this->traverse(new Filter($this->url, $response->getBody()));
        } else {
            FileLogger::Instance()->log('Die Seite "%s" gab keinen erfolgreichen Response zurück', $this->url->asString());
        }
    }

    /**
     * @param Filter $filter
     */
    private function traverse(Filter $filter)
    {
        foreach ($filter->getHrefs() as $href) {
            $url = new Url($href);
            if (UrlGuardian::Instance()->shouldCrawl($url)) {
                $this->links[] = $url->asString();

                $relation = RelationProcedure::Link($this->url)->with($url);
                if (UrlGuardian::Instance()->shouldInsert($relation)) {
                    FileLogger::Instance()->log('Insert "%s" (parent war "%s")',
                                                $relation->getChild()->asString(),
                                                $relation->getParent()->asString());

                    if (DB_INSERT) {
                        Mongo::Instance()->insert($relation, $filter);
                    }
                }
            }
        }
    }
}
