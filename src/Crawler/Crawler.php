<?php

namespace Doody\Crawler\Crawler;

use Dgame\HttpClient\HttpClient;
use Doody\Crawler\Language\Language;
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
     * @var null|string
     */
    private $content = null;

    /**
     * @var null|string
     */
    private $title = null;

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
            if (preg_match('#<body.*?>(.+?)<\/body>#isS', $response->getBody(), $matches)) {
                $this->content = $matches[1];
                if (preg_match_all('#<h(\d+).*?>(.*?)<\/h\1>#isS', $this->content, $title_matches)) {
                    if (array_key_exists(0, $title_matches[2])) {
                        $this->title = trim(strip_tags($title_matches[2][0]));
                    } else {
                        FileLogger::Instance()->log('Die Seite "%s" hat keinen Titel', $this->url->asString());
                    }
                }
                if (preg_match_all('#href="(.+?)"#iS', $matches[1], $matches)) {
                    if (VERBOSE_LOG) {
                        FileLogger::Instance()->log('Die Seite "%s" hat %d Links', $this->url->asString(), count($matches[1]));
                    }

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
                    $title = $this->title ?: $relation->getChild()->getBaseUrl();
                    if (DB_INSERT) {
                        Mongo::Instance()->insert(
                            $relation,
                            $this->content,
                            $title,
                            Language::Instance()->detectLanguage($this->content)
                        );
                    }
                }
            }
        }
    }
}
