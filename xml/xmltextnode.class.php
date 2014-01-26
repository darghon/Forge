<?php
namespace Forge;
/**
 * XMLTextNode is a representation of a basic string
 *
 * @author Gerry Van Bael
 */
class XMLTextNode {
    /**
     * Text of the text node
     * @var String
     */
    private $text = null;
	/**
     * Reference to parent object
     * @var Pointer
     */
    protected $parent = null;

    /**
     * Constructor
     * @param String $text optional
     */
    public function __construct($text = null){
      $this->text = $text;
    }

    /**
     * Magic method that returns the text of the TextNode
     * @return String
     */
    public function __toString(){
      return (string)$this->text;
    }

    /**
     * Sets the text of the text node
     * @param String $text
     */
    public function setText($text = null){
      $this->text = $text;
    }

    /**
     * Returns the text of the TextNode
     * @return String
     */
    public function getText(){
      return $this->text;
    }

	/**
     * Get the pointer of the parent object
     * @return Pointer
     */
    public function & getParent(){
      return $this->parent;
    }

    /**
     * Set the pointer or reference of the parent object
     * @param Pointer $parent
     */
    public function setParent(&$parent){
      $this->parent = &$parent;
    }
	
    /**
     * Destructor
     */
    public function  __destruct(){
      unset($this->text);
    }
}
?>
