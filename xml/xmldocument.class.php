<?php
namespace Forge;

/**
 * XMLDocument is an xml parser that creates an element tree of a passed xml document
 *
 * @author Gerry Van Bael
 */
class XMLDocument
{

    /**
     * root of xml document tree
     *
     * @var XMLNode
     */
    public $root = null;
    /**
     * String that contains the base xml code.
     *
     * @var String
     */
    protected $xml = null;
    /**
     * String that contains the version of the xml
     *
     * @var String
     */
    protected $version = "1.0";
    /**
     * String that contains the encoding of the xml
     *
     * @var String
     */
    protected $encoding = "ISO-8859-1";
    /**
     * String that contains the document type of the xml document
     *
     * @var String
     */
    protected $doctype = null;

    /**
     * Constructor
     *
     * @param String $path Optional
     */
    public function __construct($path = null)
    {
        if ($path !== null) {
            $this->load($path);
        }
    }

    public function load($path)
    {
        if (file_exists($path)) {
            $this->xml = $this->getContent($path);
            $this->parse();
        }
    }

    /**
     * Private function that retrieves the content of an xml file
     *
     * @param String $file Path of the xml file
     *
     * @return String $file_content
     */
    private function getContent($file_path)
    {
        $file = fopen($file_path, "r");
        $result = fread($file, filesize($file_path));
        fclose($file);

        return $result;
    }

    private function parse()
    {
        $xml = $this->xml;
        $ignores = [];
        //first strip all parser ignores and replace with token holders
        preg_match_all('|(<!\[CDATA\[[^\]]*\]\]>)|U', $xml, $result);
        foreach ($result[1] as $key => $entry) {
            $ignores[$key] = new XMLTextNode($entry);
            $xml = str_replace($entry, "{SPL:" . $key . ":EPL}", $xml);
        }
        $result = null;
        $current = $this->root;
        //devide xml is file is to large... preserve memory and CPU strength, cause preg_match_all is a server killer
        $total = strlen($xml);
        $block_size = 262144; //256kb
        while (0 < ($curr_size = strlen($xml))) {
            //self::show_status(($total - $curr_size), $total);
            //get block limit
            $end = strpos($xml, '<', min($curr_size, $block_size));
            //create the working_xml file
            $working_xml = $curr_size > $block_size ? substr($xml, 0, $end) : $xml;

            preg_match_all('|([^<]*)(<[^>]*>)([^<]*)|', $working_xml, $result);
            $xml = $end > -1 ? substr($xml, $end) : '';
            array_splice($result, 0, 1);
            $process = 0;
            foreach ($result[1] as $key => $tag) {
                preg_match('|<[\/]*([^\s\/>]*)|', $tag, $type); //just need 1 (only 1 possible)
                switch (substr($type[1], 0, 1)) {
                    case '!': //doctype declaration
                        $this->setDoctype($tag);
                        break;
                    case '?':
                        if (strpos($tag, ' ') > -1) {
                            preg_match_all('|\s([a-zA-Z]*)=["\']([^"]*)["\'][\s\/>?]|', $tag, $attributes);
                            foreach ($attributes[1] as $index => $attribute) {
                                switch (strtolower($attribute)) {
                                    case 'version':
                                        $this->setVersion($attributes[2][$index]);
                                        break;
                                    case 'encoding':
                                        $this->setEncoding($attributes[2][$index]);
                                        break;
                                }
                            }
                        }
                        break;
                    default:
                        //is there a text node before this tag?
                        if (trim($result[0][$key]) != "") {
                            $text = $result[0][$key];
                            if (substr($text, 0, 5) == "{SPL:" && substr($text, -5) == ":EPL}") {
                                $text = $ignores[str_replace(["{SPL:", ":EPL}"], "", $text)];
                            }
                            $textnode = new XMLTextNode($text);
                            if ($current !== null) {
                                $current->appendChild($textnode);
                            }
                        }
                        //is it a closing tag?
                        if (substr($tag, 1, 1) != "/") {
                            $node = new XMLNode($type[1]);
                            if (strpos($tag, ' ') > -1) {
                                preg_match_all('|\s([a-zA-Z]*)=["\']([^"]*)["\'][\s\/>?]|', $tag, $attributes);
                                foreach ($attributes[1] as $index => $attribute)
                                    $node->setAttribute($attribute, $attributes[2][$index]);
                            }
                            if ($this->root === null) {
                                $this->root = $node;
                                $current = &$this->root;
                            } else {
                                //add the tag
                                $current->appendChild($node);
                                if (substr($tag, -2, 1) != "/") { //NOT a self closing tag
                                    $current = &$current->lastChild(); //set current to last added child
                                }
                            }
                            unset($node);
                        } else {
                            if ($current instanceOf XMLNode) {
                                $current = &$current->getParent();
                            }
                        }
                        //is there a text node behind this tag?
                        if (trim($result[2][$key]) != "") {
                            $text = trim($result[2][$key]);
                            if (substr($text, 0, 5) == "{SPL:" && substr($text, -5) == ":EPL}") {
                                $text = $ignores[(int)(str_replace(["{SPL:", ":EPL}"], "", $text))];
                            }
                            $textnode = new XMLTextNode($text);
                            if ($current !== null) {
                                $current->appendChild($textnode);
                            }
                        }
                        break;
                }
                unset($result[0][$key], $result[1][$key], $result[2][$key], $type, $attributes);
            }
            unset($result);
        }
    }

    public function create($root_element = "root", $version = "1.0", $encoding = "ISO-8859-1")
    {
        $this->root = new XMLNode($root_element);
        $this->version = $version;
        $this->encoding = $encoding;
    }

    public function loadXML($string)
    {
        $this->xml = $string;
        $this->parse();
    }

    public function getVersion()
    {
        return $this->version;
    }

    public function setVersion($version = "1.0")
    {
        $this->version = $version;
    }

    public function getEncoding()
    {
        return $this->encoding;
    }

    public function setEncoding($enc = "ISO-8859-1")
    {
        $this->encoding = $enc;
    }

    public function getDoctype()
    {
        return $this->doctype;
    }

    public function setDoctype($doctype)
    {
        $this->doctype = $doctype;
    }

    public function save($path)
    {
        $file = fopen($path, "w");
        fwrite($file, $this->toString());
        fclose($file);
    }

    public function toString()
    {
        $string = "<?xml version=\"" . $this->version . "\" encoding=\"" . $this->encoding . "\" ?>\n";
        $string .= $this->root;

        return $string;
    }

    public function saveXML()
    {
        $string = "<?xml version=\"" . $this->version . "\" encoding=\"" . $this->encoding . "\" ?>\n";
        $string .= $this->root->writeXml();

        return $string;
    }

    public function __toString()
    {
        return $this->toString();
    }

    /**
     * @param $name
     *
     * @return \Forge\XMLNode[]
     */
    public function & getElementsByTagName($name)
    {
        return $this->root->getElementsByTagName($name);
    }

}

?>