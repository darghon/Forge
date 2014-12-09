<?php
namespace Forge;

/**
 * Tools are the internal framework toolset.
 * This file can be editor, but it's adviced not to, updates of the framework
 * will possibly overwrite any changes
 */
class Tools
{

    /**
     * This function will return a camel cased string from what was entered
     * Splitting the words with spaces or _
     * Ex. string_to camel_case => StringToCamelCase
     */
    public static function strtocamelcase($string, $upper = false)
    {
        $string = str_replace("_", " ", $string);
        $tmp = str_replace(" ", "", ucwords($string));

        return ($upper) ? $tmp : strtolower(substr($tmp, 0, 1)) . (substr($tmp, 1));
    }

    /**
     * @param        $string
     * @param string $delimiter
     *
     * @return string
     */
    public static function camelcasetostr($string, $delimiter = '_')
    {
        return trim(strtolower(preg_replace('/([a-z])([A-Z])/', '$1'.$delimiter.'$2', $string)));
    }

    /**
     * This function displays a print of the given object
     *
     * @param Object  $object
     * @param Boolean $detail false
     */
    public static function dump($object, $detail = false)
    {
        echo "<pre>";
        ($detail) ? var_dump($object) : print_r($object);
        echo "</pre>";
    }

    /**
     * This function returnes as cleaned array without null entries
     *
     * @param String $split
     * @param String $string
     *
     * @return Array
     */
    public static function cleanExplode($split, $string)
    {
        $result = [];
        $explode = explode($split, $string);
        foreach ($explode as $entry) {
            if (trim($entry) != '') {
                $result[] = $entry;
            }
        }

        return $result;
    }

    /**
     * This function allows the user to encrypt a given string by an optional salt.
     * This function used a sha1 encryption which is one way
     *
     * @param String $string
     * @param String $salt
     *
     * @return String Encrypted
     */
    public static function encrypt($string, $salt = "sv4l6j6kc9uq")
    {
        return sha1($salt . $string . $salt);
    }

    /**
     * This function reverses a date
     *
     * @param String $date
     *
     * @return String
     */
    public static function reverseDate($date)
    {
        return implode("-", array_reverse(preg_split("/[-:\/]+/", $date)));
    }

    /**
     * This function checks if the passed argument is a valid date
     *
     * @param String $date
     *
     * @return Boolean
     */
    public static function is_date($date)
    {
        $stamp = strtotime($date);
        if (!is_numeric($stamp))
            return false;
        $month = date('m', $stamp);
        $day = date('d', $stamp);
        $year = date('Y', $stamp);
        if (checkdate($month, $day, $year))
            return true;

        return false;
    }

    /**
     * This function returns the current date/time stamp
     *
     * @return String timestamp
     */
    public static function now()
    {
        return date('Y-m-d H:i:s');
    }

    public static function decode($param)
    {
        $result = [];
        $pos = -1;

        $explode = explode(';', $param);
        foreach ($explode as $entry) {
            $pos = strpos($entry, '{');
            $key = substr($entry, 0, $pos);
            $value = substr($entry, $pos + 1, -1);
            $result[$key] = $value;
        }

        return $result;
    }

    public static function encode($param)
    {
        $string = [];
        foreach ($param as $key => $entry) {
            if ($entry != '' && !is_numeric($key))
                $string[] = $key . "{" . $entry . "}";
        }

        return implode(';', $string);
    }

    public static function transformDate($string)
    {
        return substr($string, 0, 4) . '-' . substr($string, 4, 2) . '-' . substr($string, 6, 2) . ' ' . substr($string, 8, 2) . ':' . substr($string, 10, 2) . ':' . substr($string, 12);
    }

    /**
     * Public function that adds support for get_called_class function to PHP < 5.3
     *
     * @return String $get_called_class
     */
    public static function getCaller()
    {
        $bt = debug_backtrace();
        $l = 0;
        do {
            $l++;
            $lines = file($bt[$l]['file']);
            $callerLine = $lines[$bt[$l]['line'] - 1];
            preg_match('/([a-zA-Z0-9\_]+)::' . $bt[$l]['function'] . '/', $callerLine, $matches);

            if ($matches[1] == 'self') {
                $line = $bt[$l]['line'] - 1;
                while ($line > 0 && strpos($lines[$line], 'class') === false) {
                    $line--;
                }
                preg_match('/class[\s]+(.+?)[\s]+/si', $lines[$line], $matches);
            }
        } while ($matches[1] == 'parent' && $matches[1]);

        return $matches[1];
    }

    public static function slugify($text)
    {
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);
        $text = trim($text, '-');
        if (function_exists('iconv')) {
            $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        }
        $text = strtolower($text);
        $text = preg_replace('~[^-\w]+~', '', $text);
        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    public static function shrink($string, $size = 20)
    {
        return substr($string, 0, strlen($string) > $size + 3 ? $size : $size + 3) . (strlen($string) > $size + 3 ? '...' : '');
    }

    public static function translateDay($index)
    {
        switch ($index) {
            case 0:
                return 'Zondag';
                break;
            case 1:
                return 'Maandag';
                break;
            case 2:
                return 'Dinsdag';
                break;
            case 3:
                return 'Woensdag';
                break;
            case 4:
                return 'Donderdag';
                break;
            case 5:
                return 'Vrijdag';
                break;
            case 6:
                return 'Zaterdag';
                break;
            default:
                return null;
        }
    }

    public static function explode_r($glue, $string)
    {
        //TODO: create recursive explode
    }

    public static function implode_r($glue, array $array)
    {
        //TODO: create recursive implode
    }

    public static function generateLevelUpStats(
        array $statDivision = ['Strength' => 17, 'Constitution' => 17, 'Dexterity' => 17, 'Intelligence' => 17, 'Wisdom' => 16, 'Charisma' => 16],
        array $_stats = ['Strength' => 0, 'Constitution' => 0, 'Dexterity' => 0, 'Intelligence' => 0, 'Wisdom' => 0, 'Charisma' => 0]
    )
    {
        for ($x = 1; $x < 7; $x++) {
            $rnd = mt_rand(1, 100);
            foreach ($statDivision as $stat => $amount) {
                $rnd -= $amount;
                if ($rnd <= 0) {
                    $_stats[$stat]++;
                    break;
                }
            }
        }

        return $_stats;
    }

    /**
     * Merge arrays, sum their children with identical keys
     *
     * @param array $array_1
     * @param array $array_2
     * @param array $_ (optional)
     *
     * @return array $merged_result
     */
    public static function array_sum_recursive()
    {
        $arguments = func_get_args();
        $result = [];
        foreach ($arguments as $arrays) {
            foreach ($arrays as $key => $value) {
                if (!isset($result[$key])) {
                    $result[$key] = $value;
                } else {
                    if (is_array($value)) {
                        if (is_array($result[$key])) $result[$key] = self::array_sum_recursive($result[$key], $value);
                        else {
                            $result[$key] = self::array_sum_recursive([$result[$key]], $value);
                        }
                    } else {
                        $result[$key] += $value;
                    }
                }
            }
        }

        return $result;
    }

    /**
     * Retrieve the stat comparison of 2 stats based on the first one. The result is multiplied by the base
     * Ex. 5 vs 10 = (5/(5+10))*100 =>
     *
     * @param integer $_stat1
     * @param integer $_stat2
     * @param integer $base
     *
     * @return integer
     */
    public static function getStatComparison($_stat1 = 0, $_stat2 = 0, $base = 100)
    {
        return (int)(($_stat1 / ($_stat1 + $_stat2)) * $base);
    }

}