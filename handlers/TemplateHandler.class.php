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
        echo $this->_replaceTokens();

    }

    protected function _parseTemplate()
    {
        $lines = explode(PHP_EOL, $this->_templateContent);
        $blockBuffer = [];
        $blockIdentifiers = [0 => 'global'];
        $currentLevel = 0;
        $ifBuffer = [];
        $ifConditions = [];
        $currentIfLevel = -1;
        foreach ($lines as $line) {
            if (!isset($blockBuffer[$currentLevel])) $blockBuffer[$currentLevel] = '';
            if (preg_match_all('|{{([^:\}]*)(:([^\}]*))?}}|', $line, $function)) {
                foreach ($function[0] as $index => $f) {
                    switch ($function[1][$index]) {
                        case 'BLOCK': /* New Block */
                            $blockBuffer[$currentLevel] .= substr($line, 0, max(0, strpos($line, $function[0][$index]) - 1));
                            $currentLevel++;
                            $blockBuffer[$currentLevel] = substr($line, strpos($line, $function[0][$index]) + strlen($function[0][$index])) . PHP_EOL;
                            $blockIdentifiers[$currentLevel] = trim($function[3][$index]);
                            break;
                        case 'ENDBLOCK': /* End Current Block */
                            $identifier = $blockIdentifiers[$currentLevel];
                            $blockBuffer[$currentLevel] .= substr($line, 0, strpos($line, $function[0][$index]) - 1);
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
        foreach ($blockIdentifiers as $key => $value) {
            $this->_templateBlocks[$value] = $blockBuffer[$key];
        }
    }

    /**
     * @return mixed|string
     */
    protected function _replaceTokens()
    {
        $result = '';
        $templateBlocks = array_reverse($this->_templateBlocks);
        foreach($templateBlocks as $key => $block) {
            if($key !== 'global') {
                preg_match_all('|{(.*)}|U', $block, $tokens);
                if (!is_array($tokens[0])) return $block;
                $tokens[0] = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
                $content = '';
                foreach($this->_templateVariables[$key] as $tokenMap) {
                    $_block = $block;
                    foreach ($tokenMap as $token) {
                        $_block = str_replace($token, $this->_templateVariables[$key][substr($token,1,-1)], $_block);
                    }
                    $content .= $_block;
                }
                echo "Added key '".'BLOCK_'.$key."'";
                $this->_templateVariables['BLOCK_'.$key] = $content;
            }
            else{
                preg_match_all('|{(.*)}|U', $block, $tokens);
                if (!is_array($tokens[0])) return $block;
                $tokens[0] = array_unique($tokens[0]); //replace each type of token just once, no need to repeat the process
                foreach ($tokens[0] as $token) {
                    $block = str_replace($token, $this->_templateVariables[substr($token,1,-1)], $block);
                }
                $result = $block;
            }
        }
        return $result;

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