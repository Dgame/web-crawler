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

    /**
     * @var Crawler
     */
    private static $instance = null;
    /**
     * @var string
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
     * Crawler constructor.
     */
    private function __construct()
    {
        $this->collection = (new Client())->selectCollection(self::DB_NAME, self::DB_COLLECTION);
    }

    /**
     * @return array
     */
    public function getScannedLinks() : array
    {
        return $this->links;
    }

    /**
     * @return Crawler
     */
    public static function Instance() : Crawler
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $url
     */
    public function crawl(string $url)
    {
        $this->links     = [];
        $this->parentUrl = $url;

        if ($this->verifyLink($url)) {
            $content = @file_get_contents($url);
            if (!empty($content)) {
                $this->parseDom($content);
            }
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
        for ($i = 0; $i < $links->length; $i++) {
            /** @var \DOMElement $link */
            $link = $links->item($i);
            if ($link->hasAttribute('href')) {
                $url = trim($link->getAttribute('href'));
                if ($this->verifyLink($url)) {
                    $this->insertLink($url);
                }
            }
        }
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    private function verifyLink(string $url) : bool
    {
        if (filter_var($url, FILTER_VALIDATE_URL) !== false && !preg_match('#mailto#i', $url)) {
            return $this->collection->count(['url' => $url]) === 0;
        }

        return false;
    }

    /**
     * @param string $url
     */
    private function insertLink(string $url)
    {
        $result = $this->collection->insertOne(
            [
                'url'    => $url,
                'parent' => $this->parentUrl,
                'content' => $this->content,
                'pr' => 0,
            ]
        );

        if ($result->getInsertedCount() !== 0) {
            $this->links[] = $url;
        }
    }
}
