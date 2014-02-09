<?php
namespace Forge;

trait Localize {
    protected $_initializedLocale = false;
    protected $_userLanguage = null;
    protected $_dateFormat = null;
    protected $_timeZone = null;
    protected $_numberFormat = null;

    public function getUserLanguage(){
        if($this->_initializedLocale == false) $this->_initUserLocale();
    }

    /**
     * @param null $dateFormat
     */
    public function setDateFormat($dateFormat)
    {
        $this->_dateFormat = $dateFormat;
        return $this;
    }

    /**
     * @return null
     */
    public function getDateFormat()
    {
        if($this->_initializedLocale == false) $this->_initUserLocale();
        return $this->_dateFormat;
    }

    /**
     * @param null $numberFormat
     */
    public function setNumberFormat($numberFormat)
    {
        $this->_numberFormat = $numberFormat;
        return $this;
    }

    /**
     * @return null
     */
    public function getNumberFormat()
    {
        if($this->_initializedLocale == false) $this->_initUserLocale();
        return $this->_numberFormat;
    }

    /**
     * @param null $timeZone
     */
    public function setTimeZone($timeZone)
    {
        $this->_timeZone = $timeZone;
        return $this;
    }

    /**
     * @return null
     */
    public function getTimeZone()
    {
        if($this->_initializedLocale == false) $this->_initUserLocale();
        return $this->_timeZone;
    }

    protected function _initUserLocale(){
        $this->_userLanguage = Forge::Translate()->getActiveLanguage();
        $this->_dateFormat = Forge::Translate()->getDateFormat();
        $this->_timeZone = Forge::Translate()->getTimeZone();
        $this->_numberFormat = Forge::Translate()->getNumberFormat();
        $this->_initializedLocale = true;
    }
}