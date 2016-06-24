<?php

namespace Doody\Crawler;

/**
 * Class Url
 * @package Doody\Crawler
 */
final class Url
{
    /**
     * @var null|string
     */
    private $url = null;

    /**
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $url = trim($url);
        if (!preg_match('#^https?#', $url)) {
            $url = sprintf('http://%s', $url);
        }

        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getContent() : string
    {
        return @file_get_contents($this->url);
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid() : bool
    {
        return filter_var($this->url, FILTER_VALIDATE_URL) !== false && $this->match('#mailto#') === false;
    }

    /**
     * @param string $pattern
     *
     * @return bool
     */
    public function match(string $pattern) : bool
    {
        return preg_match($pattern, $this->url);
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }
}