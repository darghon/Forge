<?php
namespace Forge\Builder;
use Forge\DatabaseSchema;
use Forge\Config;

/**
 * Generator that builds the folder structure of a new application.
 * When this object is called to generate, it will check if the
 * root folder of the application already exists, if so, the application is
 * considered "generated". if not, it will attempt to create all
 * neccesary folders and default files
 *
 * @author Darghon
 */
class Database extends \Forge\ObjectGenerator
{

    protected $environment = null;
    protected $overwrite = false;

    public function __construct($args = [])
    {
        list($this->environment, $this->overwrite) = $args + [null, false];
        if ($this->environment !== null) \Forge\Forge::setEnvironment($this->environment);
        if (strtolower($this->overwrite) === 'true') {
            $this->overwrite = true;
        }
        if (strtolower($this->overwrite) === 'false') {
            $this->overwrite = false;
        }
    }

    /**
     * Public generate action. This method performs all actions required to build the wanted files
     * Todo: Change to use Schema object instead
     * @return boolean $result;
     */
    public function generate()
    {
        $schema = &$this->getDatabaseSchema();
        //Schema is now an array with all the tables loaded from the config/database folder
        foreach ($schema as $table_name => &$table) {
            echo "Building " . $table_name;
            flush();

            list($fields, $links, $translation) = $this->processTable($table_name, $table);
            \Forge\Generator::getInstance()->build('databasetable',[\Forge\Tools::camelcasetostr($table_name), $fields, $links, $translation, $this->overwrite]);
            echo " DONE!" . PHP_EOL;
            flush();
        }
        echo "Creating indexes ";
        flush();
        \Forge\Database::getDB()->processQueue('.');
        echo " DONE!" . PHP_EOL;
        flush();
    }
}