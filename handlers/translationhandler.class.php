<?php
namespace Forge;


class TranslationHandler{

    use EventListener;
    /**
     * @var string
     */
    protected $_defaultLanguage = 'en_GB';

    /**
     * @var string
     */
    protected $_activeLanguage = null;

    /**
     * @var array
     */
    protected $_availableLanguages = array();

    /**
     * @var bool
     */
    protected $_enabled = true;

    /**
     * @var null
     */
    protected $_numberFormat = null;

    /**
     * @var null
     */
    protected $_dateFormat = null;

    /**
     * @var null
     */
    protected $_timeZone = null;

    /**
     * @var array
     */
    protected $_translations = null;

    /**
     * @var string
     */
    protected $_i18nLocation = '{{app}}/i18n/';

    /**
     * @param array $settings
     */
    public function __construct($settings = array()){
        if(array_key_exists('enabled', $settings)) $this->_enabled = !!($settings['enabled']);
        if(array_key_exists('default', $settings)) $this->_defaultLanguage = $settings['default'];
        if(array_key_exists('available', $settings)) $this->_availableLanguages = $settings['available'];
    }

    public function getActiveLanguage(){
        return $this->_activeLanguage;
    }

    /**
     * @return array
     */
    public function getTranslations()
    {
        return $this->_translations;
    }

    /**
     * @param string $string
     * @return string
     */
    public function translate($string){
        if($this->_activeLanguage === null) $this->_detectActiveLanguage();
        if($this->_translations === null) $this->_loadTranslations();

        if(array_key_exists($string, $this->_translations) && $this->_translations[$string] !== ''){
            return $this->_translations[$string];
        }
        else{
            $this->_translations[$string] = ''; //add empty entry
            $this->raiseEvent(new \Forge\Event\MissingTranslationEvent($this));
        }

        return $string;
    }

    public function getTranslationPath(){
        return Config::path('app').'/'.str_replace('{{app}}', Route::curr_app(), $this->_i18nLocation);
    }

    protected function _detectActiveLanguage(){
        //fallback
        $this->_activeLanguage = $this->_defaultLanguage;
    }

    protected function _loadTranslations(){
        $this->_translations = $this->_readFile($this->_activeLanguage);
    }

    /**
     * @param $language
     * @return array $translations
     */
    protected function _readFile($language){
        try{
            $path = $this->getTranslationPath().$this->_activeLanguage.'.i18n.php';
            if(file_exists($path)){
                return include $path;
            }
        }
        catch(Exception $error){}
        return array();
    }

}
