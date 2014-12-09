<?php
namespace Forge;

class Query
{

    const COMPARE_LIKE = ' like "%$1%"';
    const COMPARE_EQUALS = ' = "$1"';
    const COMPARE_STARTLIKE = ' = "$1%"';
    const COMPARE_ENDLIKE = ' = "%$1"';
    const COMPARE_GREATER_THAN = ' > "$1"';
    const COMPARE_GREATER_THAN_EQUAL = ' >= "$1"';
    const COMPARE_LESS_THAN = ' < "$1"';
    const COMPARE_LESS_THAN_EQUAL = ' <= "$1"';

    const JOIN_INNER = 'inner';
    const JOIN_LEFT = 'left';
    const JOIN_RIGHT = 'right';
    const JOIN_UNION = 'union';
    const JOIN_NONE = null;

    private $type = 'select';
    private $fields = [];
    private $table = [];
    private $join = [];
    private $criteria = [];
    private $criteria_link = ' and ';
    private $order = [];
    private $limit = null;
    private $error = [];

    /**
     * @param null $select
     * @param null $from
     * @param null $where
     */
    public function __construct($select = null, $from = null, $where = null)
    {
        if ($select !== null) $this->select($select);
        if ($from !== null) $this->from($from);
        if ($where !== null) $this->where($where);

        return $this;
    }

    /**
     * @param null $select
     *
     * @return $this
     */
    public function select($select = null)
    {
        $this->type = 'select';
        if ($select === null) {
            $this->fields[] = '*';

            return $this;
        }
        if (!is_array($select)) {
            $this->fields[] = $select;
        } else {
            foreach ($select as $alias => $value) {
                $this->fields[] = $value . (!is_numeric($alias) ? ' as ' . $alias : '');
            }
        }

        return $this;
    }

    /**
     * @param $table
     *
     * @return $this
     */
    public function from($table)
    {
        if (!is_array($table)) {
            $ex = explode(' ', $table);
            $this->table[] = new QueryTable($ex[0], isset($ex[1]) ? $ex[1] : null);
        } else {
            foreach ($table as $value) {
                $ex = explode(' ', $value);
                $this->table[] = new QueryTable($ex[0], isset($ex[1]) ? $ex[1] : null);
            }
        }

        return $this;
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this
     */
    public function where($field, $value = '/**empty**/', $compare = self::COMPARE_EQUALS)
    {
        if (is_array($field)) {
            //intercept a array to do multiple compare statements (always and)
            foreach ($field as $key => $def) {
                if (is_array($def)) { //definition in a subarray
                    list($subField, $subValue, $subCompare) = $def + [null, '/**empty**/', self::COMPARE_EQUALS];
                    $this->where($subField, $subValue, $subCompare);
                } else {
                    $this->where($key, $def, self::COMPARE_EQUALS);
                }
            }

            return $this;
        }
        if ($value === '/**empty**/') {
            $this->criteria[] = '(' . $field . ')';
        } else {
            switch (getType($value)) {
                case 'boolean':
                    $this->criteria[] = $field . ' = "' . ($value ? 1 : 0) . '"';
                    break;
                case 'array':
                    if ($this->criteria_link == null || $this->criteria_link == '_and_') $this->andWhereIn($field, $value);
                    else $this->orWhereIn($field, $value);
                    break;
                default:
                    if ($value === null) {
                        $this->criteria[] = $field . ' is null';
                    } else {
                        $this->criteria[] = $field . str_replace('$1', (string)$value, $compare);
                    }
                    break;
            }
        }

        return $this;
    }

    /**
     * @param       $field
     * @param array $value
     *
     * @return Query
     */
    public function andWhereIn($field, $value = [])
    {
        foreach ($value as $val) {
            $conditions[] = sprintf('`%s` = "%s"', $field, $val);
        }

        return $this->where('(' . implode(' or ', $conditions) . ')');
    }

    public function orWhereIn()
    {

    }

    /**
     * @return Query
     */
    public static function build()
    {
        $query = new Query();

        return $query;
    }

    /**
     * @param $table
     * @param $on
     *
     * @return Query
     */
    public function innerJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_INNER);
    }

    /**
     * @param        $table
     * @param        $on
     * @param string $method
     *
     * @return $this
     */
    public function join($table, $on, $method = Query::JOIN_INNER)
    {
        $ex = explode(' ', $table);
        $this->join[] = new QueryTable($ex[0], isset($ex[1]) ? $ex[1] : null, $on, $method);

        return $this;
    }

    /**
     * @param $table
     * @param $on
     *
     * @return Query
     */
    public function leftJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_LEFT);
    }

    /**
     * @param $table
     * @param $on
     *
     * @return Query
     */
    public function rightJoin($table, $on)
    {
        return $this->join($table, $on, self::JOIN_RIGHT);
    }

    /**
     * @param $table
     * @param $on
     *
     * @return Query
     */
    public function union($table, $on)
    {
        return $this->join($table, $on, self::JOIN_UNION);
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this|Query
     */
    public function andWhere($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS)
    {
        if ($this->criteria_link !== null && $this->criteria_link !== ' and ') {
            $this->error[] = 'Can not combine AND and OR criteria like this.';

            return $this;
        }
        $this->criteria_link = ' and ';

        return $this->where($field, $value, $compare);
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this|Query
     */
    public function andWhereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS)
    {
        if ($this->criteria_link !== null && $this->criteria_link !== ' and ') {
            $this->error[] = 'Can not combine AND and OR criteria like this.';

            return $this;
        }
        $this->criteria_link = ' and ';

        return $this->whereNot($field, $value, $compare);
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this
     */
    public function whereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS)
    {
        if ($value === '/**empty**/') {
            $this->criteria[] = 'not (' . $field . ')';
        } else {
            switch ((string)$value) {
                case 'null':
                    $this->criteria[] = $field . ' is not null';
                    break;
                case 'true':
                    $this->criteria[] = 'not ' . $field . ' = "1"';
                    break;
                case 'false':
                    $this->criteria[] = 'not ' . $field . ' = "0"';
                    break;
                default:
                    $this->criteria[] = 'not ' . $field . str_replace('$1', (string)$value, $compare);
                    break;
            }
        }

        return $this;
    }

    /**
     * @param       $field
     * @param array $value
     *
     * @return $this|Query
     */
    public function andWhereNotIn($field, $value = [])
    {
        if ($this->criteria_link !== null && $this->criteria_link !== ' and ') {
            $this->error[] = 'Can not combine AND and OR criteria like this.';

            return $this;
        }
        $this->criteria_link = ' and ';

        return $this->whereNot($field, $value, Query::COMPARE_EQUALS);
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this|Query
     */
    public function orWhere($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS)
    {
        if ($this->criteria_link !== null && $this->criteria_link !== ' or ') {
            $this->error[] = 'Can not combine AND and OR criteria like this.';

            return $this;
        }
        $this->criteria_link = ' or ';

        return $this->where($field, $value, $compare);
    }

    /**
     * @param        $field
     * @param string $value
     * @param string $compare
     *
     * @return $this|Query
     */
    public function orWhereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS)
    {
        if ($this->criteria_link !== null && $this->criteria_link !== ' or ') {
            $this->error[] = 'Can not combine AND and OR criteria like this.';

            return $this;
        }
        $this->criteria_link = ' or ';

        return $this->whereNot($field, $value, $compare);

    }

    public function orWhereNotIn()
    {

    }

    /**
     * @param $order
     *
     * @return $this
     */
    public function orderBy($order)
    {
        if (!is_array($order)) {
            $this->order[] = $order;
        } else {
            foreach ($order as $value) {
                $this->order[] = $value;
            }
        }

        return $this;
    }

    /**
     * @param int $page
     * @param int $size
     *
     * @return $this
     */
    public function limit($page = 1, $size = 20)
    {
        $this->limit = ' limit ' . (($page - 1) * $size) . ', ' . $size;

        return $this;
    }

    public function assign($param, &$variable)
    {

    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->type . ' ' . implode(', ', $this->fields) . ' from ' . implode(', ', $this->table) . (count($this->join) > 0 ? ' ' . implode(' ', $this->join) : '') . (count($this->criteria) > 0 ? ' where ' . implode($this->criteria_link, $this->criteria) : '') . (count($this->order) > 0 ? ' order by ' . implode(', ', $this->order) : '') . ($this->limit !== null ? $this->limit : '') . ';';
    }

    public function clear()
    {
        $this->fields = [];
        $this->table = [];
        $this->join = [];
        $this->criteria = [];
        $this->criteria_link = null;
        $this->order = [];
    }

}