<?php
namespace Forge;

class YAML
{

    protected $key = null;
    protected $children = [];
    protected $parent = null;

    public function __construct($key)
    {
        $this->key = $key;
    }

    /**
     * Static load function of YAML will try to create a cache file for faster reloading.
     *
     * @param string  $path
     * @param boolean $cache
     *
     * @return type
     */
    public static function load($path, $cache = false)
    {

        $hash = md5(filemtime($path) . $path);
        if ($cache && file_exists(Config::path('cache') . '/' . $hash . '.php')) {
            include(Config::path('cache') . '/' . $hash . '.php');
        } else {
            $yml = self::parse(file_get_contents($path));
            Cache::saveOutput($hash . '.php', self::createCache($yml));
        }

        return $yml;
    }

    public static function parse($yml)
    {
        $list = explode(PHP_EOL, $yml);
        $result = new YAML('root');
        self::parseList($list, $result);

        return $result->toArray();
    }

    protected static function parseList(&$list, &$root)
    {
        $current = &$root;
        $current_level = 0;
        foreach ($list as &$line) {
            if (trim($line) == '') continue;
            if (preg_match("/^(\s*)([^#:]+):\s*\"?([^#\"]*)\"?/", $line, $m)) {
                $m[1] = strlen($m[1]) / 2;
                if ($current_level < $m[1]) {
                    $current_level++;
                    $current = &$current->lastChild();
                } elseif ($current_level > $m[1]) {
                    //can go up several levels at once
                    while ($current_level > $m[1]) {
                        $current_level--;
                        $current = &$current->getParent();
                    }
                }
                $current->addChild(new YAML(trim($m[2])));
                if (trim($m[3]) != '') {
                    if (preg_match("/^[\[{]([^\]}]*)?[\]}]$/", trim($m[3]), $m2) != false) {
                        $list2 = explode(',', $m2[1]);
                        foreach ($list2 as &$item) $item = trim($item);
                        self::parseList($list2, $current->lastChild());
                        unset($list2);
                    } else {
                        $current->lastChild()->addChild(trim($m[3]));
                    }
                }
            } else {
                //check if it's a list entry
                if (preg_match("/^(\s*)-\"?([^#\"]*)\"?/", $line, $m)) {
                    $m[1] = strlen($m[1]) / 2;
                    if ($current_level < $m[1]) {
                        $current_level++;
                        $current = &$current->lastChild();
                    } elseif ($current_level > $m[1]) {
                        $current_level--;
                        $current = &$current->getParent();
                    }
                    $current->addChild(trim($m[2]));
                } else {
                    //last attempt to match it to a list between brackets
                    if (preg_match("/^\s*\"?([^#\"]+)\"?/", trim($line), $m)) {
                        $current->addChild(trim($m[1]));
                    }
                }
            }
        }
    }

    public function toArray()
    {
        $children = [];
        if (count($this->children) == 1) {
            $child = $this->lastChild();
            if ($child instanceOf YAML) {
                $children[$child->getKey()] = $child->toArray();
            } else {
                $children = $child;
            }
        } else {
            foreach ($this->children as &$child) {
                if ($child instanceOf YAML) {
                    $children[$child->getKey()] = $child->toArray();
                } else {
                    $children[] = $child;
                }
            }
        }
        if (count($this->children) == 0) return '';

        return $children;
    }

    public function & lastChild()
    {
        return $this->children[count($this->children) - 1];
    }

    public function getKey()
    {
        return $this->key;
    }

    protected static function createCache(array $array)
    {
        $result = '<?php' . PHP_EOL;
        $result .= '$yml = array(' . PHP_EOL;
        $result .= self::writeArrayContent($array);
        $result .= ');';

        return $result;
    }

    protected static function writeArrayContent($array)
    {
        $result = '';
        foreach ($array as $key => $value) {
            $result .= "'$key'=>";
            if (is_array($value)) {
                $result .= " array(" . PHP_EOL . self::writeArrayContent($value) . '),' . PHP_EOL;
            } elseif ($value === true) {
                $result .= " true," . PHP_EOL;
            } elseif ($value === false) {
                $result .= " false," . PHP_EOL;
            } elseif ($value === null) {
                $result .= " null," . PHP_EOL;
            } else {
                $result .= " '$value'," . PHP_EOL;
            }
        }

        return $result;
    }

    public function addChild($child)
    {
        if ($child instanceOf YAML) {
            $child->setParent($this);
        }
        $this->children[] = $child == 'true' ? true : ($child == 'false' ? false : $child);
    }

    public function & getParent()
    {
        return $this->parent;
    }

    public function setParent(&$object)
    {
        $this->parent = &$object;
    }

}