<?php
namespace Forge;

class LinkDefinition
{
    use Translator;

    /** @var string */
    protected $_linkName;
    /** @var string */
    protected $_fromObject;
    /** @var string */
    protected $_toObject;
    /** @var string */
    protected $_linkTable;
    /** @var string */
    protected $_localForeignKey;

    /** @var string */
    protected $_linkTableLocalForeignKey;
    /** @var string */
    protected $_targetForeignKey;
    /** @var string */
    protected $_linkTableTargetForeignKey;

    /**
     * @param null|string $linkName
     * @param null        $fromObject
     * @param array       $definitions
     *
     * @throws \Exception
     */
    public function __construct($linkName = null, $fromObject = null, $definitions = [])
    {
        if (!is_null($linkName)) $this->_linkName = $linkName;
        if (!is_null($fromObject)) $this->_fromObject = $fromObject;
        if (!empty($definitions)) $this->buildFromDefinitions($definitions);
    }

    /**
     * @param array $definitions
     *
     * @throws \Exception
     */
    public function buildFromDefinitions($definitions = [])
    {
        foreach ($definitions as $type => $definition) {
            switch(strtolower($type)) {
                case 'local':
                    $this->_localForeignKey = $definition;
                    break;
                case 'target':
                    if(preg_match('/^([^\.]*)(\.(.*))?$/', $definition, $matches)){
                        if(isset($matches[1])) $this->_toObject = $matches[1];
                        $this->_targetForeignKey = isset($matches[3]) ? $matches[3] : 'id';
                    }
                    else{
                        throw new \Exception(sprintf($this->__('Unable to parse link target: %s'),$definition));
                    }
                    unset($matches);
                    break;
                case 'link':
                    if(preg_match('/^([^\[]*)(\[([^,]*),([^\]]*)\])?$/', $definition, $matches)){
                        if(isset($matches[1])) $this->_linkTable = $matches[1];
                        $this->_linkTableLocalForeignKey = isset($matches[3]) ? $matches[3] : Tools::camelcasetostr($this->_fromObject).'_id';
                        $this->_linkTableTargetForeignKey = isset($matches[4]) ? $matches[4] : Tools::camelcasetostr($this->_toObject).'_id';
                    }
                    else{
                        throw new \Exception(sprintf($this->__('Unable to parse link table: %s'),$definition));
                    }
                    unset($matches);
                    break;
            }
        }
    }

    /**
     * @return string
     */
    public function getFromObject()
    {
        return $this->_fromObject;
    }

    /**
     * @param string $fromObject
     *
     * @return $this
     */
    public function setFromObject($fromObject)
    {
        $this->_fromObject = $fromObject;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkName()
    {
        return $this->_linkName;
    }

    /**
     * @param string $linkName
     *
     * @return $this
     */
    public function setLinkName($linkName)
    {
        $this->_linkName = $linkName;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkTable()
    {
        return $this->_linkTable;
    }

    /**
     * @param string $linkTable
     *
     * @return $this
     */
    public function setLinkTable($linkTable)
    {
        $this->_linkTable = $linkTable;
        return $this;
    }

    /**
     * @return string
     */
    public function getLocalForeignKey()
    {
        return $this->_localForeignKey;
    }

    /**
     * @param string $localForeignKey
     *
     * @return $this
     */
    public function setLocalForeignKey($localForeignKey)
    {
        $this->_localForeignKey = $localForeignKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getTargetForeignKey()
    {
        return $this->_targetForeignKey;
    }

    /**
     * @param string $targetForeignKey
     *
     * @return $this
     */
    public function setTargetForeignKey($targetForeignKey)
    {
        $this->_targetForeignKey = $targetForeignKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getToObject()
    {
        return $this->_toObject;
    }

    /**
     * @param string $toObject
     *
     * @return $this
     */
    public function setToObject($toObject)
    {
        $this->_toObject = $toObject;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkTableLocalForeignKey()
    {
        return $this->_linkTableLocalForeignKey;
    }

    /**
     * @param string $linkTableLocalForeignKey
     *
     * @return $this
     */
    public function setLinkTableLocalForeignKey($linkTableLocalForeignKey)
    {
        $this->_linkTableLocalForeignKey = $linkTableLocalForeignKey;
        return $this;
    }

    /**
     * @return string
     */
    public function getLinkTableTargetForeignKey()
    {
        return $this->_linkTableTargetForeignKey;
    }

    /**
     * @param string $linkTableTargetForeignKey
     *
     * @return $this
     */
    public function setLinkTableTargetForeignKey($linkTableTargetForeignKey)
    {
        $this->_linkTableTargetForeignKey = $linkTableTargetForeignKey;
        return $this;
    }


}