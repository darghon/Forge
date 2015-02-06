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
     *
     * @return boolean $result;
     */
    public function generate()
    {
        $dbschema = new DatabaseSchema(Config::path('config') . '/database/');
        $schema = $dbschema->loadSchema()->getTableDefinitions();

        foreach ($schema as $table_name => &$table) {
            echo "Building: ".$table->getTableName().' ';

            Generator::getInstance()->build('businesslayer', [$table]);
            Generator::getInstance()->build('finder', [$table]);
            Generator::getInstance()->build('datalayer', [$table]);

            echo " DONE!" . PHP_EOL;
            flush();
        }
    }

}