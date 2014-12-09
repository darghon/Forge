<?php
namespace Forge;

/**
 * This class interacts between the finders and the Forge
 * Allowing the user to get a object
 */
class Database
{

    public static function Persist(\Forge\DataLayer $object)
    {
        return Forge::getFinder(str_replace('Data\\', 'Finder\\', get_class($object)))->persist($object);
    }

    public static function Delete(\Forge\DataLayer $object)
    {
        return Forge::getFinder(str_replace('Data\\', 'Finder\\', get_class($object)))->delete($object);
    }

    public static function escape($value)
    {
        if (is_array($value)) $value = serialize($value); //escape arrays to serialized strings
        return is_string($value) ? mysqli_real_escape_string(Forge::Connection()->getConnection(), $value) : $value;
    }

    /**
     * This function returns a reference to the DB object within the Forge
     *
     * @return DatabaseHandler
     */
    public static function & getDB()
    {
        return Forge::Database();
    }

    /**
     * This function exports all the tables or all the specified tables to the data directory
     * Each table will create it's own xml file contains it's records
     *
     * @param array $tablenames optional
     */
    public static function exportToXML($tablenames = [])
    {
        if (count($tablenames) == 0) {
            $tablenames = Database::getTables();
        }
        foreach ($tablenames as $table) {
            if (class_exists('Finder\\' . $table)) {
                $xml = new XMLDocument();
                $xml->create($table);
                $holder = new XMLNode("records");
                $records = Database::Find($table)->all();
                foreach ($records as $record) {
                    $holder->appendChild($record->toXML('record'));
                }
                unset($records);
                $xml->root->appendChild($holder);
                $xml->save(Config::path('root') . '/data/' . $table . '.xml');
            } else {
                Debug::Error($table, 'Unable to export this table');
            }
        }
    }

    public static function getTables()
    {
        return Forge::Database()->getTables();
    }

    /**
     * This function uses the Forge to fetch a finder of a specific data type
     * It returns a reference to the object
     *
     * @return Finder
     */
    public static function & Find($objectname)
    {
        return Forge::getFinder($objectname);
    }

    public static function importFromXML($filenames = [])
    {
        $all = count($filenames) > 0 ? false : true;
        if (false !== ($handle = opendir(Config::path("data")))) {
            while (false !== ($file = readdir($handle)) || ($all == true && count($filenames) > 0)) {
                if (substr($file, 0, 1) != "." && !is_dir(Config::path("data") . "/" . $file)) {
                    if ($all == true) {
                        self::XML2Object(Config::path("data") . "/" . $file, ucwords(substr($file, 0, strrpos($file, "."))));
                    } else {
                        foreach ($filenames as $entry) {
                            if ($file == $entry . '.xml') {
                                self::XML2Object(Config::path("data") . "/" . $file, ucwords($entry));
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
    }

    private static function XML2Object($path, $type)
    {
        $xml = new XMLDocument($path);
        $records = $xml->getElementsByTagName('record');
        foreach ($records as $record) {
            $obj = new $type;
            $obj->fromXML($record);
            $obj->persist();
        }
    }

}