<?php

namespace Doody\Crawler\StopWords;

/**
 * Class StopWords
 * @package Doody\Crawler\StopWords
 */
final class StopWords
{
    const PATH = '%s/files/%s.txt';

    /**
     * @var array
     */
    private $stopwords = [];

    /**
     * StopWords constructor.
     *
     * @param Language $language
     */
    public function __construct(Language $language)
    {
        $filename = sprintf(self::PATH, dirname(__FILE__), $language->getLanguageShortcut());
        $words    = file($filename);
        foreach ($words as $word) {
            $word = trim($word);

            $this->stopwords[$word] = $word;
        }
    }

    /**
     * @param array $words
     *
     * @return array
     */
    public function removeStopwords(array $words) : array
    {
        return array_diff(array_map('strtolower', $words), $this->stopwords);
    }

    /**
     * @param string $word
     *
     * @return bool
     */
    public function isStopWord(string $word) : bool
    {
        return isset($this->stopwords[$word]);
    }
}