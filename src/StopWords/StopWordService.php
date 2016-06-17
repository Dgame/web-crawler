<?php

namespace Doody\Crawler\StopWords;

/**
 * Class StopWords
 * @package Doody\Crawler\StopWords
 */
final class StopWordService
{
    const LANGUAGE_PATTERN = [Language::DE => '#(?:^de\.|\.de$)#'];

    /**
     * @var StopWordService
     */
    private static $instance = null;
    /**
     * @var array
     */
    private $languages = [];

    /**
     * Lexer constructor.
     */
    private function __construct()
    {
    }

    /**
     * @return StopWordService
     */
    public static function Instance() : StopWordService
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * @param string $url
     *
     * @return Language
     * @throws \Exception
     */
    public function detectLanguageFromURL(string $url) : Language
    {
        foreach (self::LANGUAGE_PATTERN as $language => $pattern) {
            if (preg_match($pattern, $url)) {
                return Language::Load($language);
            }
        }

        return Language::EN();
    }

    /**
     * @param Language $language
     *
     * @return StopWords
     */
    public function loadLanguage(Language $language) : StopWords
    {
        $lang = $language->getLanguageShortcut();
        if (!array_key_exists($lang, $this->languages)) {
            $this->languages[$lang] = new StopWords($language);
        }

        return $this->languages[$lang];
    }

    /**
     * @param string $url
     *
     * @return StopWords
     */
    public function loadLanguageByURL(string $url) : StopWords
    {
        $language = $this->detectLanguageFromURL($url);

        return $this->loadLanguage($language);
    }
}