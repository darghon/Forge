<?php
namespace Core;

/**
 * This class handles slots which can be defined in templates.
 * A slot is a simple placeholder
 */
class Slot{

  /**
   * A pre-defined string that outlines a slot holder in the layout template
   * @var String
   */
  private $placeholder = null;
  /**
   * This variable contains the content value, when the assigned slot is executed
   * @var String
   */
  private $content = null;

  /**
   * Create Slot
   * @param String $name
   */
  public function __construct($name){
    $this->placeholder = "[SLOT HOLDER[".$name."]-----[SLOT]";
    $this->content = "";
  }

  /**
   * Set Assigned slot value
   * @param String $content
   */
  public function setContent($content){
    $this->content = $content;
  }

  /**
   * Get Placeholder text
   * @return String
   */
  public function getPlaceholder(){
    return $this->placeholder;
  }


  /**
   * Get assigned slot value
   * @return String
   */
  public function getContent(){
    return $this->content;
  }

}