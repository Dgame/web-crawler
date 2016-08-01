<?php

namespace Doody\Crawler;

use DOMDocument;
use MongoDB\Client;

/**
 * Class Crawler.
 */
final class Crawler
{
    const DB_NAME       = 'mongodb';
    const DB_COLLECTION = 'pages';
    const LOG           = false;
    const LOG_FILE      = 'log.txt';

    /**
     * @var Crawler
     */
    private static $instance = null;

    /**
     * @var Url|null
     */
    private $parentUrl = null;
    /**
     * @var array
     */
    private $links = [];
    /**
     * @var array
     */
    private $content = [];
    /**
     * @var \MongoDB\Collection
     */
    private $collection = null;

    /**
     * @return Crawler
     */
    public static function Instance(): Crawler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Crawler constructor.
     */
    private function __construct()
    {
        $this->collection = (new Client())->selectCollection(self::DB_NAME, self::DB_COLLECTION);
    }

    /**
     * @return array
     */
    public function getScannedLinks(): array
    {
        return array_unique($this->links);
    }

    /**
     * @param string $input
     * @param array  ...$args
     */
    public function log(string $input, ...$args)
    {
        if (self::LOG) {
            if (!empty($args)) {
                $input = vsprintf($input, $args);
            }

            file_put_contents(self::LOG_FILE, $input . PHP_EOL, FILE_APPEND);
        }
    }

    /**
     * @param string $url
     */
    public function crawl(string $url)
    {
        $url = new Url($url);

        $this->parentUrl = $url;
        $this->links     = [];

        if ($this->shouldCrawlLink($url)) {
            $this->log('Scanne die Seite "%s"', $url->getUrl());

            $content = $url->getContent();
            if (!empty($content)) {
                $this->parseDom($content);
            } else {
                $this->log('Die Seite "%s" hat keinen Content', $url->getUrl());
            }
        } else {
            $this->log('Die Seite "%s" (childs: %d) wurde bereits besucht', $url->getUrl(), $this->countChildsOf($url));
        }
    }

    /**
     * @param string $content
     */
    private function parseDom(string $content)
    {
        $doc = new DOMDocument('1.0', 'utf-8');
        if (@$doc->loadHTML($content)) {
            $this->parseContent($doc->getElementsByTagName('body'));
            $this->scanLinks($doc);
        } else {
            $this->log('Die Seite "%s" konnte nicht geladen werden', $this->parentUrl->getUrl());
        }
    }

    /**
     * @param \DOMNodeList $body
     */
    private function parseContent(\DOMNodeList $body)
    {
        $this->content = base64_encode(gzdeflate($body->item(0)->textContent, 9));
    }

    /**
     * @param DOMDocument $doc
     */
    private function scanLinks(DOMDocument $doc)
    {
        $links = $doc->getElementsByTagName('a');

        $this->log('Die Seite "%s" hat %d Links', $this->parentUrl->getUrl(), $links->length);

        for ($i = 0; $i < $links->length; ++$i) {
            /** @var \DOMElement $link */
            $link = $links->item($i);
            if ($link->hasAttribute('href')) {
                $url = new Url($link->getAttribute('href'));
                if ($this->shouldCrawlLink($url)) {
                    $this->links[] = $url->getUrl();

                    if ($this->shouldInsertLink($url)) {
                        $this->insertLink($url);
                    }
                }
            }
        }
    }

    /**
     * @param Url $url
     *
     * @return int
     */
    private function countChildsOf(Url $url): int
    {
        return $this->collection->count(
            [
                'in' => ['$elemMatch' => ['$eq' => $url->getBaseUrl()]]
            ]
        );
    }

    /**
     * @param Url $url
     *
     * @return int
     */
    private function countRelation(Url $url): int
    {
        return $this->collection->count(
            [
                'url' => $url->getUrl(),
                'in'  => ['$elemMatch' => ['$eq' => $this->parentUrl->getBaseUrl()]]
            ]
        );
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    private function shouldCrawlLink(Url $url): bool
    {
        return $url->isValid() && $this->countChildsOf($url) === 0;
    }

    /**
     * @param Url $url
     *
     * @return bool
     */
    private function shouldInsertLink(Url $url): bool
    {
        return $url->isValid() && $this->countRelation($url) === 0;
    }

    /**
     * @param Url $url
     */
    private function insertLink(Url $url)
    {
        $entry = $this->collection->findOne(
            [
                'url' => $url->getUrl()
            ]
        );

        if ($entry !== null) {
            $result = $this->collection->updateOne(
                [
                    'url' => $url->getUrl()
                ],
                [
                    '$addToSet' => [
                        'in' => $this->parentUrl->getBaseUrl()
                    ]
                ]
            );
        } else {
            $result = $this->collection->insertOne(
                [
                    'url'     => $url->getUrl(),
                    'base'    => $url->getBaseUrl(),
                    'content' => $this->content,
                    'pr'      => 0,
                    'in'      => [
                        $this->parentUrl->getBaseUrl()
                    ]
                ]
            );
        }

        if ($result->isAcknowledged()) {
            $this->links[] = $url->getUrl();
        }
    }
}
