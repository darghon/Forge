<?php
namespace Forge\Event;

use Forge\IEvent;

class MissingTranslationEvent implements IEvent
{

    /**
     * @var \Forge\TranslationHandler
     */
    protected $_context = null;

    /**
     * Initiate the event class with a context (object that triggered the event)
     *
     * @param null|\Forge\TranslationHandler $context
     */
    public function __construct($context = null)
    {
        if (is_null($context)) throw new \InvalidArgumentException('Expected context to be instance of TranslationHandler');
        $this->_context = $context;

    }

    /**
     * Run the event in question
     *
     * @return bool $success
     */
    public function raiseEvent()
    {
        $path = $this->_context->getTranslationPath();
        @mkdir($path, 0777, true);
        //create file
        file_put_contents($path . $this->_context->getActiveLanguage() . '.i18n.php', sprintf(<<<eof
<?php

return array(
%s
);
eof
                , $this->_getTranslations())
        );
    }

    /**
     * @return string
     */
    protected function _getTranslations()
    {
        $translations = [];
        $allTranslations = $this->_context->getTranslations();
        ksort($allTranslations);
        foreach ($allTranslations as $key => $value) $translations[] = sprintf("\t'%s' => '%s'", addslashes($key), addslashes($value));

        return implode(',' . PHP_EOL, $translations);
    }

}