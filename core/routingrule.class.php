<?php
namespace Forge;

/**
 * Definition of a routing rule.
 * This class handles the registration, and matching of routing rules and urls.
 */
class RoutingRule
{
    /**
     * List of raw URLS that have been defined in the routing config yml
     * The key of each url is it's language code, or __ for default language
     *
     * @var Array
     */
    protected $raw_url = [];
    /**
     * List of raw URL patterns.
     * This list is internaly populated for each raw url, and is a regex match string to check likelyness of matchable
     * urls
     *
     * @var Array
     */
    //protected $url_pattern = array();
    /**
     * List of attributes that have been defined in the routing rule
     *
     * @var Array
     */
    protected $attributes = [];

    protected $defaults = [];
    /**
     * Internal counter of parameters that have been specified in the routing line
     *
     * @var Integer
     */
    protected $attr_in_url = 0;

    /**
     * Construct a Routing rule by it's rule definition
     *
     * @param Array $url_rule
     */
    public function __construct($url_rule = [])
    {
        if (!empty($url_rule)) {
            if (is_array($url_rule['url'])) {
                foreach ($url_rule['url'] as $iso2 => $rule) $this->parseUrl($rule, $iso2);
            } else $this->parseUrl($url_rule['url']);
            if (is_array($url_rule['param'])) {
                foreach ($url_rule['param'] as $key => $value) $this->setAttribute($key, $value);
            }
        }
    }

    /**
     * Register URL into routing rule
     * default iso (even if there is none) is __, which represents the default language specified in the settings yaml
     *
     * @param type $url
     * @param type $iso2
     */
    private function parseUrl($url, $iso2 = '__')
    {
        $this->raw_url[$iso2] = $url;
        if (false != ($this->attr_in_url = preg_match_all("/\/{{([^\}]*)}}/", $url, $variables))) {
            foreach ($variables[1] as $variable) {
                list($key, $default) = explode(':', $variable) + [null, null];
                $this->setDefault($key, $default); //add key + value to attribute holder
            }
        }
        //$url = str_replace(array('*','/'),array('.*','\\/'),$url);
        //$this->url_pattern[$iso2] = "/^".preg_replace("/\{\{[^\}]*\}\}/","[^\/]*",$url)."/";
    }

    public function setDefault($key, $default)
    {
        $this->defaults[$key] = $default;
    }

    /**
     * Public function to add a attribute to the routing rule
     *
     * @param String $key
     * @param String $value
     */
    public function setAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    /**
     * Get all specified attributes in routing rule
     *
     * @return Array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Public function to add multiple attributes to the routing rule
     *
     * @param Array $attributes
     */
    public function setAttributes($attributes = [])
    {
        foreach ($attributes as $key => $value) {
            $this->setAttribute($key, $value);
        }
    }

    public function getAttribute($key, $default = null)
    {
        return isset($this->attributes[$key]) || array_key_exists($key, $this->attributes) ? $this->attributes[$key] : (isset($this->defaults[$key]) || array_key_exists($key, $this->defaults) ? $this->defaults[$key] : $default);
    }

    public function getRawUrl($iso2 = '__')
    {
        return $this->raw_url[$iso2];
    }

    /**
     * Function returns the amount of attributes in the url
     *
     * @return Integer
     */
    public function getAttrInUrl()
    {
        return $this->attr_in_url;
    }

    /**
     * Compare rule to a passed url
     *
     * @param String $url
     * @param String $iso2 (optional)
     *
     * @return Integer $score
     */
    public function matchToUrl($url, $iso2 = '__')
    {

        if ($url == $this->raw_url[$iso2]) return 9; //if passed url matches this one, return 9
        $url_parts = Tools::cleanExplode('/', $this->raw_url[$iso2]);
        $score = 0;
        $done = false;
        $url_explode = Tools::cleanExplode('/', $url);
        foreach ($url_explode as $key => $match_part) {
            if ($done) continue; //no need to continue matching when a * is met
            if (isset($url_parts[$key])) {
                if ($url_parts[$key] == '*') $done = true;
                if ($done) continue;
                if (preg_match("/\{\{[^\}]*\}\}/", $url_parts[$key]) == 0) {
                    if ($url_parts[$key] == $match_part) {
                        $score++;
                    } else {
                        return -2; //If a static part doesn't match, then this is not a good url
                    }
                }
            } else {
                return -3; //if url is longer then match target, and match target doesn't end with a *, then this is not a good url
            }
        }

        return $score;
    }

    /**
     * Build URL with passed attributes and optional language code
     *
     * @param Array  $attributes
     * @param String $iso2 (optional)
     *
     * @return String $url
     */
    public function buildUrl($attributes = [], $iso2 = '__')
    {
        if ($iso2 != '__' && (!isset($this->raw_url[$iso2]) || !array_key_exists($iso2, $this->raw_url))) $iso2 = '__';

        //remove default params if they where set
        $attributes = array_diff($attributes, $this->attributes);

        $url = $this->raw_url[$iso2];
        //replace params in url
        if (preg_match_all("/\/({{([^\}:]*):?[^\}]*}})/", $this->raw_url[$iso2], $variables) > 0) {
            foreach ($variables[1] as $key => $variable) {
                if (
                    isset($attributes[$variables[2][$key]]) ||
                    isset($this->attributes[$variables[2][$key]]) ||
                    array_key_exists($variables[2][$key], $attributes) ||
                    array_key_exists($variables[2][$key], $this->attributes)
                ) {

                    $attr = isset($attributes[$variables[2][$key]]) || array_key_exists($variables[2][$key], $attributes) ? $attributes[$variables[2][$key]] : $this->attributes[$variables[2][$key]];

                    $url = str_replace($variable, $attr, $url);
                    unset($attributes[$variables[2][$key]]);
                }
            }
        }
        if (substr($url, -2) == '/*') {
            $url = substr($url, 0, -2);
            foreach ($attributes as $attribute => $value) {
                if ($value !== null) {
                    $url .= sprintf('/%s/%s', $attribute, $value);
                }
            }
        }

        return substr($url, 0, 1) == '/' ? substr($url, 1) : $url;
    }
}