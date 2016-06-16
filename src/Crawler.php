<?php

namespace Doody\Crawler;

use DOMDocument;
use PDO;

/**
 * Class Crawler
 * @package Doody\Crawler
 */
final class Crawler
{
    const DSN      = 'mysql:dbname=test;host=127.0.0.1';
    const USER     = 'root';
    const PASSWORD = '';

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

    /**
     * Crawler constructor.
     */
    private function __construct()
    {
        $this->dbh = new PDO(self::DSN, self::USER, self::PASSWORD, [PDO::ATTR_PERSISTENT => true]);
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
            $stmt = $this->dbh->prepare('SELECT COUNT(id) as count FROM crawler WHERE url = ?');
            if ($stmt->execute([$url])) {
                return $stmt->fetch(PDO::FETCH_ASSOC)['count'] == 0;
            }
        }

        return false;
    }

    /**
     * @param string $url
     */
    private function insertLink(string $url)
    {
        $stmt = $this->dbh->prepare('INSERT INTO crawler (url, parent) VALUES (?, ?)');
        if ($stmt->execute([$url, $this->parent])) {
            $this->links[] = $url;
        }
    }
}