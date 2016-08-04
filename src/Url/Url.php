<?php

namespace Doody\Crawler\Url;

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
     * Url constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $url = trim($url);
        if (!preg_match('#^https?#', $url)) {
            $url = sprintf('http://%s', ltrim($url, '/'));
        }

        $this->url = $url;
    }

    /**
     * @param string $url
     *
     * @return bool
     */
    public function isValid() : bool
    {
        if ($this->valid === null) {
            $this->valid = filter_var($this->url, FILTER_VALIDATE_URL) !== false && !preg_match('#mailto#iS', $this->url);
        }

        return $this->valid;
    }

    /**
     * @return string
     */
    public function asString() : string
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
