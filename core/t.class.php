<?php
namespace Forge;

/**
 * Class T is a shorthand for Translate.
 * This class contains static functions that represent translation of any and
 * all strings.
 * This class is used even if translation support is turned off (By the framework core)
 */
class T
{

    /**
     * Static protected variable that contains the active used language.
     * By default will use english.
     *
     * @var type
     */
    protected static $language = 'en_EN';

    /**
     * Static protected variable that contains all loaded translation files.
     *
     * @var Array
     */
    protected static $lang_buffer = [];

    /**
     * function that translates a string into the active language if possible.
     * you can pass parameters into the text by adding {} around them, you can then fill them in by passing that value
     * as a parameter You can also automatically use plural forms of words if wanted by adding all forms into {}
     * devided by a | The system will consider the 1st entry as the single form, 2nd as other forms (plural) You need
     * to pass a un-indexed number in the params array to trigger this process, otherwise "0" is assumed Ex.
     * __("examples","I have {n} {book|books}.",array(2)); => I have 2 books.
     *
     * @param String $category
     * @param String $text   to be translated
     * @param Array  $params optional passed parameters
     */
    public static function __($cat, $text, $params = [])
    {
        return Forge::Translate()->translate($cat, $text, $params);
    }

    /**
     * Function that formats the passed date to the correct language format
     * Expected format of date is: yyyy-mm-dd
     *
     * @param String $date
     *
     * @return String $format_date
     */
    public static function formatDate($date)
    {
        return Forge::Translate()->formatDate($date);
    }

    /**
     * Function that normalizes the date to the mysql standard (yyyy-mm-dd)
     * The expected format is the format that was defined for the active language
     *
     * @param String $date
     *
     * @return String $normalized_date
     */
    public static function normDate($date)
    {
        return Forge::Translate()->normalizeDate($date);
    }

    public static function createJavascriptTranslator()
    {
        $xml = new XMLDocument(Config::path('i18n') . '/' . self::$language . '/javascript.xml');
    }

}