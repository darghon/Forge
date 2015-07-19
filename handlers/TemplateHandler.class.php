<?php
namespace Forge;

class TemplateHandler
{
    use Translator;

    /** @var string */
    protected $_templateContent;
    /** @var array */
    protected $_templateVariables;
    /** @var array */
    protected $_templateBlocks;
    /** @var string */
    protected $_generatedContent;

    /**
     * @param null|string $content
     */
    public function __construct($content = null)
    {
        if (!is_null($content)) $this->_templateContent = $content;
    }

    public function generateTemplate()
    {
        //split blocks from content
        $this->_parseTemplate();
        //replace tokens in content
        $this->_generatedContent = $this->_replaceTokens();
    }

    protected function _parseTemplate()
    {
        $lines = explode(PHP_EOL, $this->_templateContent);
        $blockBuffer = [];
        $blockIdentifiers = [0 => 'global'];
        $currentLevel = 0;
        foreach ($lines as $line) {
            if (!isset($blockBuffer[$currentLevel])) $blockBuffer[$currentLevel] = '';
            if (preg_match_all('|{{([^:\}]*)(:([^\}]*))?}}|', $line, $function)) {
                foreach ($function[0] as $index => $f) {
                    switch ($function[1][$index]) {
                        case 'BLOCK': /* New Block */
                            $blockBuffer[$currentLevel] .= substr($line, 0, max(0, strpos($line, $function[0][$index]) - 1));
                            $currentLevel++;
                            $after = substr($line, strpos($line, $function[0][$index]) + strlen($function[0][$index]));
                            $blockBuffer[$currentLevel] = $after != '' ? $after . PHP_EOL : '';
                            $blockIdentifiers[$currentLevel] = trim($function[3][$index]);
                            break;
                        case 'ENDBLOCK': /* End Current Block */
                            $identifier = $blockIdentifiers[$currentLevel];
                            $before = substr($line, 0, max(0, strpos($line, $function[0][$index]) - 1));
                            if($before != '') $blockBuffer[$currentLevel] .= $before;
                            $this->_templateBlocks[$identifier] = $blockBuffer[$currentLevel];
                            $currentLevel--;
                            $blockBuffer[$currentLevel] .= '{BLOCK_' . $identifier . '}' . substr($line, strpos($line, $function[0][$index]) + strlen($function[0][$index])) . PHP_EOL;
                            break;
// Todo: Implement actual if statement...
//                        case 'IF':
//                            $currentIfLevel ++;
//                            $ifConditions[$currentIfLevel] = $function[3][$index];
//                            $blockBuffer[$currentLevel] .= substr($line, 0, max(0, strpos($line, $function[0][$index]) - 1));
//
//                            break;
//                        case 'ELSEIF':
//
//                            break;
//                        case 'ELSE':
//
//                            break;
//                        case 'ENDIF':
//
//                            break;
                        default:
                            $blockBuffer[$currentLevel] .= $line . PHP_EOL;
                            break;
                    }
                }
            } else {
                $blockBuffer[$currentLevel] .= $line . PHP_EOL;
            }
        }
        $this->_templateBlocks['global'] = $blockBuffer[0];
    }

    /**
     * @return mixed|string
     */
    protected function _replaceTokens()
    {
        $result = '';
        foreach ($this->_templateBlocks as $key => $block) {
            if ($key !== 'global') {
                preg_match_all('|{.*}|U', $block, $tokens);
                if (!is_array($tokens[0])) return $block;
                $tokens = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
                $content = '';
                foreach ($this->_templateVariables[$key] as $tokenMap) {
                    $_block = $block;
                    foreach ($tokens as $token) {
                        $_block = str_replace($token, $tokenMap[substr($token, 1, -1)], $_block);
                    }
                    $content .= $_block;
                }
                $this->_templateVariables['BLOCK_' . $key] = $content;
            } else {
                preg_match_all('|{.*}|U', $block, $tokens);
                if (!is_array($tokens[0])) return $block;
                $tokens = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
                foreach ($tokens as $token) {
                    $block = str_replace($token, $this->_templateVariables[substr($token, 1, -1)], $block);
                }
                $result = $block;
            }
        }
        return $result;

    }

    /**
     * @param string $filename
     * @param bool   $overwrite
     *
     * @return bool|int
     * @throws \Exception
     */
    public function writeFile($filename, $overwrite = false)
    {
        if(!file_exists(dirname($filename))) mkdir(dirname($filename), 0775, true);
        if (is_null($this->_generatedContent)) throw new \Exception($this->__('Unable to generate file, no generated content found.'));
        if (preg_match_all('|{.*}|U', $filename, $tokens)) {
            $tokens = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
            foreach ($tokens as $token) {
                $filename = str_replace($token, $this->_templateVariables[substr($token, 1, -1)], $filename);
            }
        }
        if ($overwrite || !file_exists($filename)) {
            echo '.';
            return file_put_contents($filename,$this->_generatedContent);
        }
        return true;
    }

    /**
     * @return array
     */
    public function getTemplateBlocks()
    {
        return $this->_templateBlocks;
    }

    /**
     * @param array $templateBlocks
     *
     * @return $this
     */
    public function setTemplateBlocks(array $templateBlocks)
    {
        $this->_templateBlocks = $templateBlocks;
        return $this;
    }

    /**
     * @return string
     */
    public function getTemplateContent()
    {
        return $this->_templateContent;
    }

    /**
     * @param string $templateContent
     *
     * @return $this
     */
    public function setTemplateContent($templateContent)
    {
        $this->_templateContent = $templateContent;
        return $this;
    }

    /**
     * @return array
     */
    public function getTemplateVariables()
    {
        return $this->_templateVariables;
    }

    /**
     * @param array $templateVariables
     *
     * @return $this
     */
    public function setTemplateVariables(array $templateVariables)
    {
        $this->_templateVariables = $templateVariables;
        return $this;
    }

    public function __destruct()
    {
        foreach ($this as $key => $value) unset($this->$key);
        unset($this);
    }

    /**
     * @param $condition
     *
     * @throws \Exception
     * @return bool
     */
    protected function _evaluateCondition($condition)
    {
        $workingString = $condition;
        $conditions = [];
        while (strpos($workingString, '(') > -1) {
            if (preg_match_all('|(\([^\(\)]*\))|', $workingString, $groups)) {
                foreach ($groups[0] as $group_index => $group) {
                    $key = '$' . (count($conditions) + 1);
                    $conditions[$key] = $group;
                    $workingString = str_replace($group, $key, $workingString);
                }
            } else {
                throw new \Exception(sprintf($this->__('Unable to complete template, contains invalid If condition: %s', $condition)));
            }
        }
        return true;
    }

}