<?php

namespace Doody\Crawler;

use DOMDocument;
use MongoDB\Client;
use Doody\Crawler\StopWords\StopWordService;

/**
 * Class Crawler
 * @package Doody\Crawler
 */
final class Crawler
{
    /**
     * @var Crawler
     */
    private static $instance = null;
    /**
     * @var PDO
     */
    private $dbh = null;
    /**
     * @var string
     */
    private $parentURL = null;
    /**
     * @var array
     */
    private $links = [];
    /**
     * @var array
     */
    private $content = [];

    private $collection = null;

    private function __construct()
    {
        $client = new Client();
        $this->collection = $client->selectCollection('mongodb', 'pages');
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
        $this->parentURL = $url;
        $this->links     = [];

        $content = @file_get_contents($url);
        if (!empty($content)) {
            $this->parseDom($content);
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
        $words   = array_filter($words, function(string $word) {
            return preg_match('#[a-z]#i', $word);
        });
        $words = array_map(function(string $word) {
            return preg_replace('#[^a-z\d]#i', '', $word);
        }, $words);

        $this->content = StopWordService::Instance()->loadLanguageByURL($this->parentURL)->removeStopwords($words);
    }

    /**
     * @param DOMDocument $doc
     */
    private function scanLinks(DOMDocument $doc)
    {
        $links = $doc->getElementsByTagName('a');
        for ($i = 0; $i < $links->length; $i++) {
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
            return $this->collection->count(['url' => $url]) == 0;
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
                'url' => $url,
                'parent' => $this->parent,
            ]
        );
        if ($result->getInsertedCount() !== 0) {
            $this->links[] = $url;
        }
    }
}
