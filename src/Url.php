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
     * @var null|bool
     */
    private $valid = null;
    /**
     * @var array
     */
    private $matches = [];

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
        if ($this->valid === null) {
            $this->valid = filter_var($this->url, FILTER_VALIDATE_URL) !== false && $this->match('#mailto#') === false;
        }

        return $this->valid;
    }

    /**
     * @param string $pattern
     *
     * @return bool
     */
    public function match(string $pattern) : bool
    {
        if (!array_key_exists($pattern, $this->matches)) {
            $this->matches[$pattern] = preg_match($pattern, $this->url) === 1;
        }

        return $this->matches[$pattern];
    }

    /**
     * @return string
     */
    public function getUrl() : string
    {
        return $this->url;
    }

    /**
     * Return the host part of an url
     * @return string
     */
    public function getBaseUrl()
    {
        $base = parse_url($this->url, PHP_URL_HOST);
        if (substr($base, 0, 4) !== 'www.') {
            $base = 'www.' . $base;
        }
        return $base;
    }
}
