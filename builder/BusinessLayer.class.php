<?php
namespace Forge\Builder;

use Forge\baseGenerator;
use Forge\Config;
use Forge\Generator;
use Forge\TableDefinition;
use Forge\Tools;

/**
 * Class BusinessLayer
 * -------------------
 * Builder class that creates the base and normal version of the businesslayer object.
 * If the normal version exists, this one is kept. the base version will always be overwritten
 *
 * @package Forge\Builder
 * @author  Gerry Van Bael
 */
class BusinessLayer extends baseGenerator
{
    /** @var TableDefinition */
    protected $_tableDefinition;
    /** @var string */
    protected $_location;
    /** @var string */
    protected $_objectTemplates;

    public function __construct($args = [])
    {
        list($this->_tableDefinition) = $args + [null];
        $this->_location = Config::path('objects') . '/business/';
        $this->_objectTemplates = $this->_getTemplatesByType('objects');
    }

    public function generate()
    {
        foreach ($this->_objectTemplates as $path => $targetPath) {
            $contents = file_get_contents(Config::path('forge') . '/templates/' . $path);
            $template = new \Forge\TemplateHandler($contents);
            $template->setTemplateVariables($this->_createTokenMap());

            $template->generateTemplate();
            $template->writeFile($this->_location.$targetPath, strpos($targetPath, 'base{object}') > -1 ? true : false);

        }
    }

    protected function _createTokenMap()
    {
        $columns = [];
        foreach($this->_tableDefinition->getColumns() as $column) {
            $columns[] = [
                'column_name' => Tools::strtocamelcase($column->getName(), true),
                'column_type' => $column->getType(),
                'raw_column_name' => Tools::camelcasetostr($column->getName()),
                'raw' => $column->getName()
            ];
        }
        $links = [];
        foreach($this->_tableDefinition->getLinks() as $link) {
            $links[] = [
                'link_name' => Tools::strtocamelcase($link->getLinkName(),true),
                'link_result' => $link->getToObject(),
                'link_object' => str_replace('[]','',$link->getToObject()),
                'raw_link_name' => Tools::camelcasetostr($link->getLinkName()),
                'raw' => $link->getLinkName()
            ];
        }
        $extends = $this->_tableDefinition->getExtends();
        $implements = $this->_tableDefinition->getImplements();
        return [
            'object' => $this->_tableDefinition->getTableName(),
            'columns' => $columns,
            'links' => $links,
            'extends' => $extends["Business"],
            'implements' => !empty($implements["Business"]) ? 'implements '.implode(', ',$implements['Business']) : ''
        ];
    }

    private function writeBaseContent($file)
    {
        fwrite($file, "<?php " . PHP_EOL);
        fwrite($file, "abstract class base" . $this->name . " extends " . $this->extends . $this->implements . "{" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);

        foreach ($this->fields as $field) {
            $functionName = Tools::strtocamelcase($field["name"], true);
            switch ($field['type']) {
                case 'list':
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return array \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){" . PHP_EOL);
                    fwrite($file, "\t\t\$unserialize = unserialize(\$this->data->" . $field["name"] . ");" . PHP_EOL);
                    fwrite($file, "\t\treturn is_array(\$unserialize) ? \$unserialize : array();" . PHP_EOL);
                    fwrite($file, "\t}" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param array  \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(array \$val = array()){ \$this->data->" . $field["name"] . " = serialize(\$val); return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
                case 'timestamp':
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return integer \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){ return date(\"c\",\$this->data->" . $field["name"] . "); }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param array  \$" . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(\$val){ \$this->data->" . $field["name"] . " = date('Y-m-d H:i:s',\$val); return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
                default:
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public get function that retrieves the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return " . $field["type"] . ' $' . $field['name'] . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function get" . $functionName . "(){ return \$this->data->" . $field["name"] . "; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    fwrite($file, "\t/**" . PHP_EOL);
                    fwrite($file, "\t * Public set function that sets the " . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @param " . $field["type"] . ' $' . $field['name'] . PHP_EOL);
                    fwrite($file, "\t * @return \$this" . PHP_EOL);
                    fwrite($file, "\t */" . PHP_EOL);
                    fwrite($file, "\tpublic function set" . $functionName . "(\$val){ \$this->data->" . $field["name"] . " = \$val; return \$this; }" . PHP_EOL);
                    fwrite($file, PHP_EOL);
                    break;
            }
        }
        //generate links if needed
        if (is_array($this->links)) {
            foreach ($this->links as $link) {
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Lazy loading function that retrieves the selected " . $link["name"] . "" . PHP_EOL);
                fwrite($file, "\t * @return " . $link["target"] . " " . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function & get" . $link["name"] . "(){" . PHP_EOL);
                fwrite($file, "\t\treturn " . $link["target"] . "::Find(\$this->data->" . $link["local"] . ");" . PHP_EOL);
                fwrite($file, "\t}" . PHP_EOL);
                fwrite($file, "" . PHP_EOL);
            }
        }
        // Check if any translation fields are added to the configuration
        if ($this->multi_lang === true) {
            //Create the lazy loading function for translations
            fwrite($file, "\t/**" . PHP_EOL);
            fwrite($file, "\t * Lazy loading translation" . PHP_EOL);
            fwrite($file, "\t * @return " . $this->name . "_i18n " . PHP_EOL);
            fwrite($file, "\t */" . PHP_EOL);
            fwrite($file, "\tpublic function & getTranslation(\$lang = null){" . PHP_EOL);
            fwrite($file, "\t\tif(\$lang === null) \$lang = \\Forge\\Forge::Translate()->getActiveLanguage();" . PHP_EOL);
            fwrite($file, "\t\t\$lang = explode('_',\$lang);" . PHP_EOL);
            fwrite($file, "\t\treturn " . $this->name . "_i18n::Find()->getTranslationByID(\$this->data->ID,\$lang[0]);" . PHP_EOL);
            fwrite($file, "\t}" . PHP_EOL);
            fwrite($file, "" . PHP_EOL);
            //create a getter for each translated field
            foreach ($this->translate as $translated_field) {
                if (in_array($translated_field['name'], ['ID', $this->name . 'ID', 'Lang', '_recordVersion', '_deletedAt'])) continue; //skip these fields
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Retrieve translated value for " . $translated_field['name'] . PHP_EOL);
                fwrite($file, "\t * @return " . $translated_field['type'] . " \$" . $translated_field['name'] . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function get" . $translated_field['name'] . "(\$lang = null){" . PHP_EOL);
                fwrite($file, "\t\treturn \$this->getTranslation(\$lang)->get" . $translated_field['name'] . "();" . PHP_EOL);
                fwrite($file, "\t}" . PHP_EOL);
                fwrite($file, "" . PHP_EOL);
                fwrite($file, "\t/**" . PHP_EOL);
                fwrite($file, "\t * Set translated value for " . $translated_field['name'] . PHP_EOL);
                fwrite($file, "\t * @param string \$value" . PHP_EOL);
                fwrite($file, "\t * @param string \$lang" . PHP_EOL);
                fwrite($file, "\t * @return \$this" . PHP_EOL);
                fwrite($file, "\t */" . PHP_EOL);
                fwrite($file, "\tpublic function set" . $translated_field['name'] . "(\$value, \$lang = null){" . PHP_EOL);
                fwrite($file, "\t\t\$i18n = &\$this->getTranslation(\$lang);" . PHP_EOL);
                fwrite($file, "\t\t\$i18n->set" . $translated_field['name'] . "(\$value);" . PHP_EOL);
                fwrite($file, "\t\treturn \$this;" . PHP_EOL);
                fwrite($file, "\t}" . PHP_EOL);
                fwrite($file, "" . PHP_EOL);
            }
        }
        //create a magic __toString method for select list dumping
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Magic to string method" . PHP_EOL);
        fwrite($file, "\t * @return String " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function __toString(){" . PHP_EOL);
        $ok = false;
        foreach ($this->fields as $field) {
            if ($ok)
                continue;
            if (strtolower($field['name']) == 'name' || strtolower($field['name']) == 'description') {
                fwrite($file, "\t\treturn \$this->data->" . $field["name"] . ";" . PHP_EOL);
                $ok = true;
            }
        }
        if (!$ok) {
            if ($this->multi_lang === true) {
                fwrite($file, "\t\treturn (String)\$this->getTranslation();" . PHP_EOL);
            } else {
                fwrite($file, "\t\treturn (String)\$this->data->ID;" . PHP_EOL);
            }
        }
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Object validator, if this class returns true, the object can is valid and can be saved." . PHP_EOL);
        fwrite($file, "\t * @return Boolean " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function validate(){" . PHP_EOL);
        fwrite($file, "\t\treturn parent::validate();" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Final Static function that allows the retrieval of the correct Finder functions for this object" . PHP_EOL);
        fwrite($file, "\t * @return Finder\\" . $this->getName() . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tfinal public static function & Find(\$id = null){" . PHP_EOL);
        fwrite($file, "\t\treturn parent::Find(\$id);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Public Destructor, unset every used valiable" . PHP_EOL);
        fwrite($file, "\t * @return String " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tpublic function __destroy(){" . PHP_EOL);
        fwrite($file, "\t\tforeach(\$this as \$key => \$val) unset(\$this->\$key);" . PHP_EOL);
        fwrite($file, "\t\tunset(\$this);" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
        fwrite($file, "}" . PHP_EOL);
    }

    public function getName()
    {
        return $this->name;
    }

    private function writeClassContent($file)
    {
        fwrite($file, "<?php" . PHP_EOL);
        fwrite($file, "\t/**" . PHP_EOL);
        fwrite($file, "\t * Forge Business class" . PHP_EOL);
        fwrite($file, "\t * --------------------" . PHP_EOL);
        fwrite($file, "\t * This class is the user control for " . $this->name . "." . PHP_EOL);
        fwrite($file, "\t * Any custom actions to this database object need to be specified here." . PHP_EOL);
        fwrite($file, "\t * " . PHP_EOL);
        fwrite($file, "\t * @author Gerry Van Bael " . PHP_EOL);
        fwrite($file, "\t */" . PHP_EOL);
        fwrite($file, "\tclass " . $this->name . " extends base" . $this->name . "{" . PHP_EOL);
        fwrite($file, "" . PHP_EOL);
        fwrite($file, "\t}" . PHP_EOL);
    }

    private function linkThis()
    {
        return [
            [
                "name"   => $this->name,
                "target" => $this->name,
                "local"  => $this->name . "ID"
            ]
        ];
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function __destroy()
    {
        unset($this->name, $this->fields, $this->location);
    }

}