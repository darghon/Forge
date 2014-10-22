<?php
namespace Forge\Builder;

use Forge\Config;
use Forge\DatabaseSchema;
use Forge\Generator;
use Forge\ObjectGenerator;

/**
 * Generator that builds the folder structure of a new application.
 * When this object is called to generate, it will check if the
 * root folder of the application already exists, if so, the application is
 * considered "generated". if not, it will attempt to create all
 * necessary folders and default files
 *
 * @author Darghon
 */
class Objects extends ObjectGenerator
{

    /**
     * Public generate action. This method performs all actions required to build the wanted files
     * @return boolean $result;
     */
    public function generate()
    {
//        $dbschema = new DatabaseSchema(Config::path('config').'/database/');
//        $dbschema->loadSchema();
//

        $schema = &$this->getDatabaseSchema();
        //Schema is now an array with all the tables loaded from the config/database folder
        foreach ($schema as $table_name => &$table) {

            list($fields, $links, $translation, $extends, $implements) = $this->processTable($table_name, $table);
            Generator::getInstance()->build('businesslayer', array($table_name, $fields, $links, $translation, $extends['Business'], $implements['Business']));
            Generator::getInstance()->build('datalayer', array($table_name, $fields, $translation, $extends['Data'], $implements['Data']));
            Generator::getInstance()->build('finder', array($table_name, $fields, $translation, $extends['Finder'], $implements['Finder']));

            echo " DONE!" . PHP_EOL;
            flush();
        }
    }

}