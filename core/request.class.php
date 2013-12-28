<?php
namespace Core;

class Request{

  public static function init(){
    Forge::Request();
  }

  public static function Method(){
    return Forge::Request()->getMethod();
  }

  public static function getGetParameter($key,$default = null){
    return Forge::Request()->getGetParameter($key, $default);
  }

  public static function getPostParameter($key,$default = null){
    return Forge::Request()->getPostParameter($key, $default);
  }

  public static function getGetParameters(){
    return Forge::Request()->getGetParameters();
  }

  public static function getPostParameters(){
    return Forge::Request()->getPostParameters();
  }

  public static function getFiles(){
    return Forge::Request()->getFiles();
  }

  public static function getParameter($key,$default = null){
    return Forge::Request()->getParameter($key,$default);
  }

  public static function getParameters(){
    return Forge::Request()->getParameters();
  }

  public static function setParameter($key, $value = null){
  	return Forge::Request()->setParameter($key,$value);
  }

  public static function isAjaxCall(){
  	return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false;
  }

  public static function getSessionID(){
	  return Forge::Request()->getSessionID();
  }

}