<?php
namespace Forge\Builder;

/**
 * Generator that builds the schematics of an existing database.
 *
 * @author Darghon
 */
class Schematic extends \Forge\baseGenerator {

    protected $_tables;
    protected $_db;

	public function __construct($args = array()){
        try{
            //connect to database
            $this->_db = &\Forge\Database::getDB();
            $this->_tables = $this->_db->getTables();
            if(empty($this->_tables)) {
                throw new Exception('No tables found in database');
            }
        }
        catch(Exception $error) {
            echo $error->getMessage();
        }
	}
	
	/**
	 * Public generate action. This method performs all actions required to build the wanted files
	 * @return boolean $result;
	 */
	public function generate() {
        $dbSchematic = array();

        foreach ($this->_tables as $tableName) {
            $this->_db->setQuery(sprintf('DESCRIBE %s%s;',$this->_db->getPrefix(), $tableName))->execute();
            while ($row = $this->_db->getRecord()) {
                $dbSchematic[$tableName][] = $this->_getFieldDetails($row);
            }
        }

        $this->_writeSchematic($dbSchematic);

		echo " DONE!".PHP_EOL; flush();
	}

    protected function _getFieldDetails(array $fieldConfig = array()){
        if(isset($fieldConfig['Type'])) {
            preg_match('|^([a-zA-Z]+)(\((\d+)\))?$|',$fieldConfig['Type'], $matches);
            $type = $this->_getConfigType(isset($matches[1]) ? $matches[1] : 'varchar');
            $length = isset($matches[3]) ? $matches[3] : 0;
        }
        return array(
            'Name' =>   isset($fieldConfig['Field']) ? $fieldConfig['Field'] : null,
            'Type' =>   $type,
            'Length' =>   $length,
            'Null' =>   isset($fieldConfig['Null']) ? $fieldConfig['Null'] == 'YES' : false,
            'Default' =>isset($fieldConfig['Null']) ? $fieldConfig['Default'] : null,
            'Key' => isset($fieldConfig['Key']) ? $fieldConfig['Key'] : null,
        );
    }

    protected function _writeSchematic($dbSchematics) {
        $output = '';
        foreach ($dbSchematics as $tableName => $details) {
            $output .= $tableName.':'.PHP_EOL;
            $output .= '  Columns:'.PHP_EOL;
            foreach($details as $column) {
                $output .= '    '.$column['Name'].':'.PHP_EOL;
                $output .= '      Type: '.$column['Type'].PHP_EOL;
                $output .= '      Length: '.$column['Length'].PHP_EOL;
                $output .= '      Null: '.($column['Null'] ? 'True' : 'False').PHP_EOL;
                $output .= '      Default: '.($column['Default'] ? $column['Default'] : 'null').PHP_EOL;
            }
            $output .= PHP_EOL;
        }

        file_put_contents(\Forge\Config::path('config').'/database/tmp.yml',$output);
    }

    /**
     * @param $db_type
     * @return string
     */
    protected function _getConfigType($db_type){
        switch($db_type){
            case 'varchar':
            case 'text':
                return 'String';
            case 'int':
            case 'bigint':
            case 'tinyint':
                return 'Integer';
            case 'datetime':
            case 'timestamp':
                return 'Datetime';
            case 'date':
                return 'Date';
            case 'time':
                return 'Time';
            case 'double':
            case 'float':
            case 'decimal':
                return 'Decimal';
            default:
                die('Unknown datatype '.$db_type);
        }
    }
}