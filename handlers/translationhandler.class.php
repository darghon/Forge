<?php
namespace Forge;


class TranslationHandler{

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
    protected $_i18nLocation = 'app/{{app}}/i18n';

    /**
     * @param array $settings
     */
    public function __construct($settings = array()){
        if(array_key_exists('enabled', $settings)) $this->_enabled = !!($settings['enabled']);
        if(array_key_exists('default', $settings)) $this->_defaultLanguage = $settings['default'];
        if(array_key_exists('available', $settings)) $this->_availableLanguages = $settings['available'];
    }

    /**
     * @param string $string
     * @return string
     */
    public function translate($string){
        if($this->_activeLanguage === null) $this->_detectActiveLanguage();
        if($this->_translations === null) $this->_loadTranslations();

        return (array_key_exists($string, $this->_translations)) ? $this->_translations[$string] : $string;
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
            $path = str_replace('{{app}}', Route::curr_app(), $this->_i18nLocation).$this->_activeLanguage.'.i18n.php';
            if(!file_exists($path)){
                @mkdir(str_replace('{{app}}', Route::curr_app(), $this->_i18nLocation), 0777, true);
                //create file
                file_put_contents($path, <<<eof
<?php

return array(

);
eof
                );

            }
            include $path;
        }
        catch(Exception $error){}
        return array();
    }

}
