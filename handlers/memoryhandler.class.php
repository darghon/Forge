<?php
namespace Forge;

/**
 * MemoryHandler is a wrapper that can handle 3 different modes of object caching.
 * These 3 methods can be switched by setting the correct mode.
 */
class MemoryHandler
{
    /**
     * Mode that specifies that the systeem needs to keep a collection during load
     */
    const MODE_REGISTRY = 0;
    /**
     * Mode that specifies that memcached server should be used (if possible)
     */
    const MODE_MEMCACHE = 1;
    /**
     * Mode that specifies that alternative php cache should be used (if possible)
     */
    const MODE_APC = 2;
    /**
     * Lifetime set for object on the memcache server.
     *
     * @var Integer $timeout
     */
    protected $timeout = 30;
    /**
     * Global array that contains all business objects of the request
     * This array will only be used in MODE_REGISTRY and is not shared between sessions
     *
     * @var Array $objectcollection
     */
    protected $objectcollection = null;
    /**
     * Global array that contains all finder objects of the request
     * This array will only be used in MODE_REGISTRY and is not shared between sessions
     */
    protected $findercollection = null;
    /**
     * Memcached Connection object
     * This object will only be used in MODE_MEMCACHE and is shared between sessions
     *
     * @var Memcache
     */
    protected $memcache = null;
    /**
     * Mode of the MemoryHandler
     *
     * @var Integer
     */
    protected $mode = self::MODE_MEMCACHE;
    /**
     * Flag that specifies if a connection to a MEMCACHED server has been made or not.
     *
     * @var Boolean
     */
    protected $connected = false;

    /**
     * Construction of the MemoryHandler Object
     * You need to set a mode before you are able to start using it.
     */
    public function __construct()
    {
    }

    /**
     * Public function that registers the added connection data to the memcache object
     */
    public function connect($host, $port = 11211)
    {
        return $this->addServer($server, $port);
    }

    /**
     * Public function that registers the passed host and optional port to the memcached server.
     *
     * @param String  $host
     * @param Integer $port = 11211
     */
    public function addServer($host, $port = 11211)
    {
        if ($this->mode != self::MODE_MEMCACHE) return false;
        if ($this->memcache->addServer($host, $port) === true) {
            $this->connected = true;

            return true;
        } else {
            return false;
        }
    }

    /**
     * Public function that registered the added connection data to the memcache object
     */
    public function addServers(array $server_array)
    {
        foreach ($server_array as $server) {
            if (is_array($server)) {
                $this->addServer($server["host"], $server["port"]);
            } else {
                $this->addServer($server);
            }
        }
    }

    /**
     * Public function that retrieves an object of the specified class, and optional id.
     *
     * @param String  $class_name
     * @param Integer $id optional
     *
     * @return Object|null $class
     */
    public function & retrieve($class, $id = null)
    {
        if ($this->mode == self::MODE_MEMCACHE) return $this->retrieve_memory($class, $id);
        if ($this->mode == self::MODE_REGISTRY) return $this->retrieve_registry($class, $id);

        return null;
    }

    /**
     * Internal function that retrieves the requested object from the memcache server
     *
     * @param String  $class_name
     * @param Integer $id optional
     *
     * @return Object $class
     */
    private function & retrieve_memory($class, $id = null)
    {
        if ($class::is_a('Finder')) {
            $obj = $this->memcache->get($this->generateKey($class));
            if ($obj === false) {
                return $this->register(new $class);
            }

            return $obj;
        } else {
            $obj = $this->memcache->get($this->generateKey($class, $id));

            return $obj;
        }
    }

    /**
     * Internal function that returns a unique key for each object
     *
     * @param String  $class
     * @param Integer $id optional
     */
    private function generateKey($class, $id = null)
    {
        if ($id !== null) {
            return Tools::encrypt($class . $id, 'm3m');
        } else {
            return Tools::encrypt($class, 'm3m');
        }
    }

    /**
     * Public function that registers an object in the specified container (by mode)
     * This function will return a reference to the object rather than the object itself.
     *
     * @param Object $object
     *
     * @return Object|null $object_ref
     */
    public function & register($object)
    {
        if ($this->mode == self::MODE_MEMCACHE) return $this->register_memory($object);
        if ($this->mode == self::MODE_REGISTRY) return $this->register_registry($object);

        return null;
    }

    /**
     * Internal method to store an object in the registry_mode
     *
     * @param Object $object
     *
     * @return Object $object_ref
     */
    private function & register_memory($object)
    {
        $class = get_class($object);
        $id = is_a($object, 'Finder') ? null : $object->getID();
        $this->memcache->set($this->generateKey($class, $id), $object, 0, $this->timeout);
        $obj = &$this->memcache->get($this->generateKey($class, $id));

        return $obj;
    }

    /**
     * Internal method to store an object in the memcached server.
     *
     * @param Object $object
     *
     * @return Object $object_ref
     */
    private function & register_registry($object)
    {
        $class = get_class($object);
        if (is_a($object, 'Finder') === true) {
            if (!isset($this->findercollection[$class]) || !array_key_exists($class, $this->findercollection)) {
                $this->findercollection[$class] = $object;
            }

            return $this->findercollection[$class];
        } else {
            if (isset($this->objectcollection[$class]) || array_key_exists($class, $this->objectcollection)) {
                if (!isset($this->objectcollection[$class][$object->getID()]) || !array_key_exists($object->getID(), $this->objectcollection[$class])) {
                    $this->objectcollection[$class][$object->getID()] = $object;
                }
            } else {
                //If no previous object of this type is stored, the collection is created, and the object is added
                $this->objectcollection[$class] = [$object->getID() => $object];
            }

            return $this->objectcollection[$class][$object->getID()];
        }
    }

    /**
     * Internal function that retrieves a requested object from the registry_mode
     *
     * @param String  $class_name
     * @param Integer $id optional
     *
     * @return Object $class
     */
    private function & retrieve_registry($class, $id = null)
    {
        if ($class::is_a('Forge\Finder')) {
            if (!isset($this->findercollection[$class]) || !array_key_exists($class, $this->findercollection)) {
                $finder = new $class;
                $this->findercollection[$class] =& $finder;
            }

            return $this->findercollection[$class];
        } else {
            //Set a default variable to be returned if needed (object expected in referenced return)
            $objdef = false;
            //If Business object type is already set in the objectCollection Array
            if (isset($this->objectcollection[$class]) || array_key_exists($class, $this->objectcollection)) {
                //Check if the collection has the object with the requested id
                if (isset($this->objectcollection[$class][$id]) || array_key_exists($id, $this->objectcollection[$class])) {
                    return $this->objectcollection[$class][$id];
                }

                return $objdef;
            } else {
                return $objdef;
            }
        }
    }

    /**
     * Public function that updates the passed object, if a old_id is passed, the old entry of this object will be
     * removed from the collection This function will return a reference of the passed object
     *
     * @param Object  $object
     * @param Integer $old_id
     *
     * @return Object|null $reference
     */
    public function & update($object, $old_id = null)
    {
        if ($this->mode == self::MODE_MEMCACHE) return $this->update_memory($object, $old_id);
        if ($this->mode == self::MODE_REGISTRY) return $this->update_registry($object, $old_id);

        return null;
    }

    private function & update_memory($object, $old_id = null)
    {
        $class = get_class($object);
        $id = is_a($object, 'Finder') ? null : $object->getID();
        if ($old_id !== null) {
            $this->memcache->delete($this->generateKey($class, $old_id));
        }
        $this->memcache->replace($this->generateKey($class, $id));

        return $this->memcache->get($this->generateKey($class, $id));
    }

    private function & update_registry($object, $old_id = null)
    {
        $class = get_class($object);
        if (is_a($object, 'Finder') === true) {
            $this->findercollection[$class] = $object;

            return $this->findercollection[$class];
        } else {
            if ($old_id !== null && isset($this->objectcollection[$class]) && isset($this->objectcollection[$class][$old_id])) unset($this->objectcollection[$class][$old_id]);
            if (isset($this->objectcollection[$class]) || array_key_exists($class, $this->objectcollection)) {
                $this->objectcollection[$class][$object->getID()] = $object;
            } else {
                //If no previous object of this type is stored, the collection is created, and the object is added
                $this->objectcollection[$class] = [$object->getID() => $object];
            }

            return $this->objectcollection[$class][$object->getID()];
        }
    }

    /**
     * Statistics function that returns the stats of the memcache object
     */
    public function getStats()
    {
        if ($this->memcache !== null) {
            $return = $this->memcache->getStats();
            $this->memcache->flush();

            return $return;
        } else {
            return 'memcache not loaded';
        }
    }

    /**
     * Public function that retrieves the mode of the memory handler
     *
     * @return Integer $mode;
     */
    public function getMode()
    {
        return $this->mode;
    }

    /**
     * Public function that sets the mode of the memory handler
     *
     * @param Integer $mode
     */
    public function setMode($mode = self::MODE_MEMCACHE)
    {
        $this->mode = $mode;
        if ($this->mode == self::MODE_MEMCACHE) {
            $this->memcache = new Memcache;
        } else {
            $this->memcache = null;
            $this->objectcollection = [];
            $this->findercollection = [];
        }
    }

    /**
     * Generic destroy function
     */
    public function __destroy()
    {
        foreach ($this as $key => $var) {
            unset($this->$key);
        }
        unset($this);
    }

}
