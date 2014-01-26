<?php
namespace Forge;
/**
 * XMLNode is a representation of an xml element with possible attributes and/or children
 *
 * @author Gerry Van Bael
 */
class XMLNode extends XMLTextNode{
    /**
     * Collection of Childnodes
     * @var XMLNode[]
     */
    public $children = array();
    /**
     * Collection of attributes
     * @var array attributes
     */
    public $attributes = array();
    /**
     * Name of the element (basicly what is between the < and > markers)
     * @var String
     */
    protected $name = null;

    /**
     * Constructor
     * @param String $name Optional
     */
    public function __construct($name = null, $content = null){
      $this->name = $name;
      if($content != null){
      	$this->appendChild($content);
      }
    }

    /**
     * Get name of the xmlnode
     * @return String
     */
    public function getName(){
      return $this->name;
    }

    /**
     * Set the name of the xmlnode
     * @param String $name
     */
    public function setName($name = null){
      $this->name = $name;
    }

    /**
     * Add a child to the XMLNode
     * @param XMLNode $node
     */
    public function appendChild($node){
      $node->setParent($this);
      $this->children[] = $node;
    }

    /**
     * Add children to the XMLNode
     * @param array $nodeCollection
     */
    public function appendChildren(array $nodeCollection){
      foreach($nodeCollection as $node){
      	//if(is_array($node)) $this->appendChildren($node);
      	$this->appendChild($node);
      }
    }

    /**
     * Remove a child from XMLNode
     * @param Integer $index
     */
    public function removeChild($index){
      if(isset($this->children[$index]))
        unset($this->children[$index]);
    }

    /**
     * Remove children from XMLNode
     * @param array $indexCollection
     */
    public function removeChildren(array $indexCollection){
      foreach($indexCollection as $index) $this->removeChild($index);
    }

    /**
     * Returns the first child element if it exists, else it returns null
     * @return XMLNode
     */
    public function & firstChild(){
	  $def = null;
      if(isset($this->children[0]))
		return $this->children[0];
	  else
		return $def;
    }

    /**
     * Returns the last child element if it exists, else it returns null
     * @return XMLNode
     */
    public function & lastChild(){
	  $def = null;
      if(count($this->children) > 0)
	    return $this->children[count($this->children)-1];
	  else
	    return $def;
    }

    /**
     * Returns the specified child element if it exists, else it returns null
     * @param Integer $index
     * @return XMLNode
     */
    public function & child($index){
	  $def = null;
	  if(isset($this->children[$index]))
        return $this->children[$index];
	  else
	    return $def;
    }
	
	/**
     * Returns true or false if the node has children
     * @return Boolean
     */
    public function hasChildren(){
	  return (count($this->children) > 0)?true:false;
	}

    /**
     * Adds an attribute to the current element
     * @param String $key
     * @param String $value
     */
    public function setAttribute($key, $value){
      $this->attributes[$key] = $value;
    }

    /**
     * Adds multible attributes to the current element
     * @param array $attributeCollection
     */
    public function setAttributes(array $attributeCollection){
      foreach($attributeCollection as $key => $value) $this->setAttribute($key,$value);
    }

    /**
     * Returns the requested attribute if it exists, if not it returns null
     * @param String $key
     */
    public function getAttribute($key){
      return (isset($this->attributes[$key]))?$this->attributes[$key]:null;
    }
	
	/**
     * Returns a reference collection of elements with the specified name
	 * @param String $name
     * @return \Forge\XMLNode|\Forge\XMLNode[]
     */
	public function & getElementsByTagName($name){
		$result = array();
		//check self
		if($this->name == $name) $result[] = &$this;
		foreach($this->children as &$child){
			if(!$child instanceOf XMLNode) continue;
			$coll = &$child->getElementsByTagName($name);
			foreach($coll as &$entry){
				$result[] = &$entry;
			}
		}
		return $result;
	}

    /**
     * @param string $childName
     * @return XMLNode[]|XMLNode|null
     */
    public function __get($childName){
        $matches = array();
        foreach($this->children as &$child){
            if($child->getName() == $childName) $matches[] = &$child;
        }
        if(count($matches) > 1) return $matches;
        return !empty($matches) ? $matches[0] : null;
    }

    /**
     * Handles the given array to parse the item name and attributes if needed
     * @param array $list
     */
    public function parse(array $list){
      foreach($list as $entry){
        if($entry == "") continue;
        if(strpos($entry,"=") > -1){ //attribute
          $tmp = explode("=",$entry);
          $this->setAttribute($tmp[0], $tmp[1]);
        }
        else{ //element name
          $this->name = $entry;
        }
      }
    }

    /**
     * Magic method that returns a string representation of the object
     * @return String
     */
    public function writeXml(){
      $string = "<".$this->name;
      foreach($this->attributes as $key => $value){
        $string .= " ".$key."=\"".$value."\"";
      }
      if(count($this->children) > 0){
        $string .= ">";
        foreach($this->children as $child){
          $string .= $child; //invoke __toString method
        }
        $string .= "</".$this->name.">";
      }
      else{
        $string .= " />";
      }
      return str_replace("><",">\n<",$string); //format with breaklines for nicer source code
    }

    public function __toString(){
        return $this->getText();
    }


    public function getText(){
        $string = '';
        foreach($this->children as &$child) $string .= $child->getText();
        return $string;
    }

    /**
     * Destructor
     */
    public function __destruct(){
      unset($this->name,$this->parent,$this->children,$this->attributes);
      parent::__destruct();
    }
}
?>
