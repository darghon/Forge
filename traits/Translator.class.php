<?php
namespace Forge;

trait Translator
{
    /**
     * @param string $string
     * @return string $translatedString
     */
    public function __($string)
    {
        return Forge::Translate()->translate($string);
    }
}