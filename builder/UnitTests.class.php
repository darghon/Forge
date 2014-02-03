<?php
namespace Forge\Builder;

/**
 * Generator that builds the folder structure of a new application.
 * When this object is called to generate, it will check if the
 * root folder of the application already exists, if so, the application is
 * considered "generated". if not, it will attempt to create all
 * neccesary folders and default files
 *
 * @author Darghon
 */
class UnitTests extends \Forge\ObjectGenerator {

	/**
	 * Public generate action. This method performs all actions required to build the wanted files
	 * @return boolean $result;
	 */
	public function generate() {
		$schema = &$this->getDatabaseSchema();
		//Schema is now an array with all the tables loaded from the config/database folder
		foreach($schema as $table_name => &$table) {
			echo "Building ".$table_name; flush();

			list($fields, $links, $translation, $extends, $implements) = $this->processTable($table_name, $table);
            unset($links);
            \Forge\Generator::getInstance()->build('datalayertest',array($table_name,$fields,$translation, $extends['Data'], $implements['Data']));
            \Forge\Generator::getInstance()->build('businesslayertest',array($table_name,$fields,$translation, $extends['Business'], $implements['Business']));

			echo " DONE!".PHP_EOL; flush();
		}
	}

}