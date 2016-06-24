<?php

namespace Doody\Crawler\StopWords;

/**
 * Class Language
 * @package Doody\Crawler\StopWords
 */
final class Language
{
    const DE = 'de';
    const EN = 'en';

    /**
     * @var string
     */
    private $language = null;

    /**
     * Language constructor.
     *
     * @param string $language
     */
    private function __construct(string $language)
    {
        $this->language = $language;
    }

    /**
     * @param string $language
     *
     * @return Language
     * @throws \Exception
     */
    public static function Load(string $language) : Language
    {
        static $Languages = [];
        if (empty($Languages)) {
            $ref       = new \ReflectionClass(__CLASS__);
            $Languages = array_flip($ref->getConstants());
        }

        if (array_key_exists($language, $Languages)) {
            return new self($language);
        }

        throw new \Exception('Unsupported language: ' . $language);
    }

    /**
     * @return string
     */
    public function getLanguageShortcut() : string
    {
        return $this->language;
    }

    /**
     * @return Language
     */
    public static function DE() : Language
    {
        return new self(self::DE);
    }

    /**
     * @return Language
     */
    public static function EN() : Language
    {
        return new self(self::EN);
    }
}