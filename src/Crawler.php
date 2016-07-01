<?php

namespace Doody\Crawler;

use DOMDocument;
use Doody\Crawler\StopWords\StopWordService;
use MongoDB\Client;

/**
 * Class Crawler
 * @package Doody\Crawler
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
        return $this->links;
    }

    /**
     * @param string $input
     * @param array  ...$args
     */
    public function log(string $input, ...$args)
    {
        if (self::LOG) {
            if (!empty($args)) {
                $input = vprintf($input, $args);
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

        $this->log('Scanne die Seite "%s"', $url->getUrl());

        if ($this->shouldCrawlLink($url)) {
            $content = $url->getContent();
            if (!empty($content)) {
                $this->parseDom($content);
            } else {
                $this->log('Die Seite "%s" hat keinen Content', $url->getUrl());
            }
        } else {
            $this->log('Die Seite "%s" (childs: %d) wurde nicht verifiziert', $url->getUrl(), $this->countChildsOf($url));
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
        $content = strip_tags($body->item(0)->textContent);
        $words   = preg_split('#\s+#', $content);
        $words   = array_filter($words, function (string $word) {
            return preg_match('#[a-z]#i', $word);
        });
        $words   = array_map(function (string $word) {
            return preg_replace('#[^a-z\d]#i', '', $word);
        }, $words);

        $this->content = StopWordService::Instance()->loadLanguageByURL($this->parentUrl)->removeStopwords($words);
    }

    /**
     * @param DOMDocument $doc
     */
    private function scanLinks(DOMDocument $doc)
    {
        $links = $doc->getElementsByTagName('a');

        $this->log('Die Seite "%s" hat %d Links', $this->parentUrl->getUrl(), $links->length);

        for ($i = 0; $i < $links->length; $i++) {
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
        if (filter_var($url, FILTER_VALIDATE_URL) !== false && !preg_match('#mailto#i', $url)) {
            return $this->collection->count(
                [
                    'parent' => $url->getUrl(),
                ]
            );
        }

        return 0;
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
                'url'    => $url->getUrl(),
                'parent' => $this->parentUrl->getUrl(),
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
        $result = $this->collection->insertOne(
            [
                'url'     => $url->getUrl(),
                'parent'  => $this->parentUrl->getUrl(),
                'content' => $this->content,
                'pr'      => 0,
            ]
        );

        if ($result->getInsertedCount() !== 0) {
            $this->links[] = $url->getUrl();
        }
    }
}
