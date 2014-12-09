<?php
namespace Forge;

/**
 * A baseGenerator class contains the most important global methods that are needed to convert the specified
 * templates to the "to generate" documents
 */
abstract class baseGenerator implements IGenerator
{
    /**
     * Standard construct method that receives a single joined array parameter
     */
    public function __construct($args = [])
    {

    }

    public static function isGenerator()
    {
        return true;
    }

    /**
     * Standard destroy method that destroys each declared variable, and lastly, itself
     */
    public function __destroy()
    {
        foreach ($this as $key => $var) {
            unset($this->$key);
        }
        unset($this);
    }

    /**
     * Public function to replace tokens or placeholders from the passed strings with the effective values.
     * This function uses the getTokenValue to specify which value the token will be replaced with.
     *
     * @param String $string
     * @param Array  $token_map
     *
     * @return String $result
     */
    protected function _replaceTokens($string, $token_map = [])
    {
        $blocks = [];
        //detect blocks
        if(preg_match_all('|{{(.*)(:(.*))?}}|U', $string, $functions, PREG_OFFSET_CAPTURE)) {
            $start = [];
            foreach ($functions[0] as $index => $match) {
                if($functions[1][$index][0] == 'BLOCK') {
                    $start[] = ['tag' => trim($functions[3][$index][0]), 'start' => $functions[0][$index][1]];
                }
                if($functions[1][$index][0] == 'ENDBLOCK') {
                    $block = array_pop($start);
                    $block['end'] = $functions[1][$index][1] + strlen($match[0]);
                    $blocks[$block['tag']] = substr($string, $block['start'], $functions[0][$index][1] + strlen($match[0]) - $block['start']);
                }
            }
        }

        foreach($blocks as $tag => $block) {
            if(isset($token_map[$tag]) && is_array($token_map[$tag])) {
                $text = [];
                foreach($token_map[$tag] as $values) {
                    $t = $block;
                    foreach($values as $key => $value){
                        $t = str_replace('{'.$key.'}', $value, $t);
                    }
                    $text[] = $t;
                }
                $string = str_replace($block, implode('',$text), $string);
            }
        }

        preg_match_all('|{.*}|U', $string, $tokens);
        if (!is_array($tokens[0])) return $string;
        $tokens[0] = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
        foreach ($tokens[0] as $token) {
            $string = str_replace($token, $this->_getTokenValue(str_replace('/', '_', substr($token, 1, -1)), $token_map), $string);
        }
        echo $string;

        return $string;
    }

    /**
     * Public function to retrieve the value of a specified token. This function receives the token key, and a possible
     * token map The token map is used to assign a specific value that will otherwise remain unfound. A token
     * transformer can also be used by creating a method called transform, followed by a upper camel case token name
     * transformer example: transformAppName(), this function will be called when a token app_name is found.
     *
     * @param String $key       Token_Key
     * @param Array  $token_map Token Mapping
     *
     * @return String $result
     */
    protected function _getTokenValue($key, $token_map = [])
    {
        if (isset($token_map[$key])) return $token_map[$key];
        if (method_exists($this, 'transform' . Tools::strtocamelcase($key, true))) return $this->{'transform' . Tools::strtocamelcase($key, true)}();
        if (property_exists($this, $key)) return $this->$key;

        return '';
    }

    protected function _getDefault($field)
    {
        switch ($field['type']) {
            case ObjectGenerator::FIELD_TYPE_INTEGER:
            case ObjectGenerator::FIELD_TYPE_FLOAT:
                return $field['default'];
                break;
            case ObjectGenerator::FIELD_TYPE_BOOLEAN:
                return (string)(($field["default"] == "1" || $field["default"] == true || $field["default"] == "true") ? "true" : "false");
                break;
            default:
                return (string)(($field["default"] != "null") ? "'" . $field["default"] . "'" : $field["default"]);
                break;
        }
    }

    protected function _createDirectory($fullPath, $success = null, $fail = null, $exists = null)
    {
        if (is_array($fullPath)) {
            foreach ($fullPath as $path) $this->_createDirectory($path, $success, $fail);
        } else {
            try {
                if (!file_exists($fullPath)) {
                    mkdir($fullPath, 0777, true);
                    if ($success !== null) print($success);
                } else {
                    if ($exists !== null) print($exists);
                }
            } catch
            (Exception $error) {
                if ($fail !== null) print($fail);
            }
        }
        flush();
    }

    /**
     * @param null|string $type template prefix
     *
     * @return array $templateFiles
     */
    protected function _getTemplatesByType($type = null)
    {
        $templates = scandir(Config::path('forge') . '/templates');

        $resultList = [];

        foreach ($templates as $template) {
            $file = explode('_', $template);
            if ($type === null || $file[0] == $type) {
                if ($type !== null) array_splice($file, 0, 1);
                $resultList[$template] = implode('/', $file);
            }
        }

        return $resultList;
    }

}
