<?php

namespace Doody\Crawler;

use DOMDocument;
use Doody\Crawler\StopWords\StopWordService;
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
    private $parentURL = null;
    /**
     * @var array
     */
    private $links = [];
    /**
     * @var array
     */
    private $content = [];

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
        if ($stmt->execute([$url, $this->parentURL])) {
            $this->links[] = $url;
        }
    }
}