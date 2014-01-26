<?php
namespace Forge;

class Timer{
  private $start_time = null;
  private $stop_time = null;
  private $name = null;

  public function __construct($name,$start = true){
    $this->name = $name;
    if($start){
      $this->start_time = microtime(true);
    }
  }

  public function start(){
    $this->start_time = microtime(true);
  }

  public function stop(){
    $this->stop_time = microtime(true);
  }

  public function getDuration($precision = 3){
    return round($this->stop_time - $this->start_time,$precision);
  }

  public function getName(){
    return $this->name;
  }

  public function setName($name){
    $this->name = $name;
  }
}
