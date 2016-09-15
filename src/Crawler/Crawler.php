<?php

namespace Doody\Crawler\Crawler;

use Dgame\HttpClient\HttpClient;
use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Url\RelationProcedure;
use Doody\Crawler\Url\Url;
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
        FileLogger::Instance()->log('Crawle die Seite "%s"', $url);

        $this->url = new Url($url);
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
            $url           = new Url($href);
            $this->links[] = $url->asString();

            $relation = RelationProcedure::Link($this->url)->with($url);
            FileLogger::Instance()->log('Insert "%s" (parent war "%s")',
                                        $relation->getChild()->asString(),
                                        $relation->getParent()->asString());
            DataRecorder::Instance()->append($relation, $filter);
        }
    }
}
