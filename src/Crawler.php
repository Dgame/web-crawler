<?php

namespace Doody\Crawler;

use DOMDocument;
use MongoDB\Client;

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
    private $parent = null;
    /**
     * @var array
     */
    private $links = [];

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
        $this->parent = $url;
        $this->links  = [];

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
            $this->scanLinks($doc);
        }
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
        if ($result->getInsertedCount()) {
            $this->links[] = $url;
        }
    }
}
