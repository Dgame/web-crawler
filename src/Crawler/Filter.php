<?php

namespace Doody\Crawler\Crawler;

use Doody\Crawler\Logger\FileLogger;
use Doody\Crawler\Url\Url;

/**
 * Class Filter
 * @package Doody\Crawler\Crawler
 */
final class Filter
{
    const FILTER_BODY_REGEX   = '#<body.*?>(.+?)<\/body>#isS';
    const FILTER_SCRIPT_REGEX = '#<script.*?>.*?<\/script>#isS';
    const FILTER_STYLE_REGEX  = '#<style.*?>.*?<\/style>#isS';
    const FILTER_TITLE_REGEX  = '#<h(\d+).*?>(.+?)<\/h$1>#isS';
    const FILTER_HREF_REGEX   = '#href="(.+?)"#iS';

    /**
     * @var Url|null
     */
    private $url = null;
    /**
     * @var string
     */
    private $content = '';
    /**
     * @var string
     */
    private $title = '';
    /**
     * @var array
     */
    private $hrefs = [];

    /**
     * Filter constructor.
     *
     * @param Url    $url
     * @param string $content
     */
    public function __construct(Url $url, string $content)
    {
        $this->url = $url;

        $this->extractContent($content);
    }

    /**
     * @return string
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return bool
     */
    public function hasTitle() : bool
    {
        return !empty($this->title);
    }

    /**
     * @return array
     */
    public function getHrefs(): array
    {
        return $this->hrefs;
    }

    /**
     * @param string $content
     */
    private function extractContent(string $content)
    {
        if (preg_match(self::FILTER_BODY_REGEX, $content, $matches)) {
            $body = $matches[1];

            $this->extractHrefs($body);
            $this->extractTitle($body);

            $this->content = preg_replace(self::FILTER_SCRIPT_REGEX, '', $body);
            $this->content = preg_replace(self::FILTER_STYLE_REGEX, '', $this->content);
            $this->content = strip_tags($this->content);
            $this->content = str_replace(["\r", "\n"], '', $this->content); // weird stuff
            $this->content = preg_replace("#[\v\t\s]+#", ' ', $this->content); // weird stuff
        } else {
            FileLogger::Instance()->log('Die Seite "%s" hat keinen body-Tag', $this->url->asString());
        }
    }

    /**
     * @param string $body
     */
    private function extractTitle(string $body)
    {
        if (preg_match(self::FILTER_TITLE_REGEX, $body, $matches)) {
            if (!empty($matches[0])) {
                $this->title = trim($matches[0][1]);
            } else {
                FileLogger::Instance()->log('Die Seite "%s" hat keinen Titel', $this->url->asString());
            }
        } else {
            FileLogger::Instance()->log('Die Seite "%s" hat keine H-Tags', $this->url->asString());
        }
    }

    /**
     * @param string $body
     */
    private function extractHrefs(string $body)
    {
        if (preg_match_all(self::FILTER_HREF_REGEX, $body, $matches)) {
            if (VERBOSE_LOG) {
                FileLogger::Instance()->log('Die Seite "%s" hat %d Links', $this->url->asString(), count($matches[1]));
            }

            $this->hrefs = $matches[1];
        } else {
            FileLogger::Instance()->log('Die Seite "%s" hat keine Links', $this->url->asString());
        }
    }
}