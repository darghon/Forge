<?php
namespace Forge;

class Query{
	
	const COMPARE_LIKE = 'like "%$1%"';
	const COMPARE_EQUALS = '= "$1"';
	const COMPARE_STARTLIKE = '= "$1%"';
	const COMPARE_ENDLIKE = '= "%$1"';
	
	const JOIN_INNER = 'inner';
	const JOIN_LEFT = 'left';
	const JOIN_RIGHT = 'right';
	const JOIN_UNION = 'union';
	const JOIN_NONE = null;
	
	private $type = 'select';
	private $fields = array();
	private $table = array();
	private $join = array();
	private $criteria = array();
	private $criteria_link = null;
	private $order = array();
	private $error = array();

    /**
     * @return Query
     */
    public static function build(){
        $query = new Query();
        return $query;
    }

	public function __construct($select = null,$from = null,$where = null){
		if($select !== null) $this->select($select);
		if($from !== null) $this->from($from);
		if($where !== null) $this->where($where);
		return $this;
	}
	
	public function select($select = null){
		$this->type = 'select';
		if($select === null){ 
			$this->fields[] = '*';
			return $this;
		}
		if(!is_array($select)){
			$this->fields[] = $select;
		}
		else{
			foreach($select as $alias => $value){
				$this->fields[] = $value.(!is_numeric($alias) ? ' as '.$alias : '');
			}
		}
		return $this;
	}
	
	public function from($table){
		if(!is_array($table)){
			$ex = explode(' ',$table);
			$this->table[] = new QueryTable($ex[0],isset($ex[1]) ? $ex[1] : null);
		}
		else{
			foreach($table as $value){
				$ex = explode(' ',$value);
				$this->table[] = new QueryTable($ex[0],isset($ex[1]) ? $ex[1] : null);
			}
		}
		return $this;
	}
	
	public function join($table, $on, $method = Query::JOIN_INNER){
		$ex = explode(' ',$table);
		$this->join[] = new QueryTable($ex[0],isset($ex[1]) ? $ex[1] : null, $on, $method);
		return $this;
	}
	
	public function innerJoin($table,$on){ return $this->join($table,$on,self::JOIN_INNER); }
	public function leftJoin($table,$on){ return $this->join($table,$on,self::JOIN_LEFT); }
	public function rightJoin($table,$on){ return $this->join($table,$on,self::JOIN_RIGHT); }
	public function union($table,$on){ return $this->join($table,$on,self::JOIN_UNION); }
	
	public function where($field, $value = '/**empty**/', $compare = self::COMPARE_EQUALS){
        if(is_array($field)){
            //intercept a array to do multiple compare statements (always and)
            foreach($field as $key => $def){
                if(is_array($def)){ //definition in a subarray
                    list($subField, $subValue, $subCompare) = $def + array(null,'/**empty**/',self::COMPARE_EQUALS);
                    $this->where($subField, $subValue, $subCompare);
                }
                else{
                    $this->where($key, $def, self::COMPARE_EQUALS);
                }
            }
            return $this;
        }
		if($value === '/**empty**/'){
			$this->criteria[] = '('.$field.')';
		}
		else{
			switch(getType($value)){
				case 'boolean':
					$this->criteria[] = $field.' = "'.($value ? 1 : 0).'"';
					break;
                case 'array':
                    if($this->criteria_link == null || $this->criteria_link == '_and_') $this->andWhereIn($field,$value);
                    else $this->orWhereIn($field,$value);
                    break;
				default:
					if($value === null){
						$this->criteria[] = $field.' is null';
					}
					else{
						$this->criteria[] = $field.str_replace('$1',(string)$value,$compare);
					}
					break;
			}
		}
		return $this;
	}

	public function whereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS){
		if($value === '/**empty**/'){
			$this->criteria[] = 'not ('.$field.')';
		}
		else{
			switch((string)$value){
				case 'null':
					$this->criteria[] = $field.' is not null';
					break;
				case 'true':
					$this->criteria[] = 'not '.$field.' = "1"';
					break;
				case 'false':
					$this->criteria[] = 'not '.$field.' = "0"';
					break;
				default:
					$this->criteria[] = 'not '.$field.str_replace('$1',(string)$value,$compare);
					break;
			}
		}
		return $this;
	}

	public function andWhere($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS){
		if($this->criteria_link !== null && $this->criteria_link !== ' and '){
			$this->error[] = 'Can not combine AND and OR criteria like this.';
			return $this;
		}
		$this->criteria_link = ' and ';
		return $this->where($field, $value, $compare);
	}
	public function andWhereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS){
		if($this->criteria_link !== null && $this->criteria_link !== ' and '){
			$this->error[] = 'Can not combine AND and OR criteria like this.';
			return $this;
		}
		$this->criteria_link = ' and ';
		return $this->whereNot($field, $value, $compare);
	}
	public function andWhereIn($field, $value = array()){
        foreach($value as $val){
            $conditions[] = sprintf('`%s` = "%s"', $field, $val);
        }
        return $this->where('('.implode(' or ',$conditions).')');
	}
	public function andWhereNotIn($field, $value = array()){
        if($this->criteria_link !== null && $this->criteria_link !== ' and '){
            $this->error[] = 'Can not combine AND and OR criteria like this.';
            return $this;
        }
        $this->criteria_link = ' and ';
        return $this->whereNot($field, $value, $compare);
	}
	public function orWhere($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS){
		if($this->criteria_link !== null && $this->criteria_link !== ' or '){
			$this->error[] = 'Can not combine AND and OR criteria like this.';
			return $this;
		}
		$this->criteria_link = ' or ';
		return $this->where($field, $value, $compare);
	}
	public function orWhereNot($field, $value = '/**empty**/', $compare = Query::COMPARE_EQUALS){
		if($this->criteria_link !== null && $this->criteria_link !== ' or '){
			$this->error[] = 'Can not combine AND and OR criteria like this.';
			return $this;
		}
		$this->criteria_link = ' or ';
		return $this->whereNot($field, $value, $compare);
	
	}
	public function orWhereIn(){
	
	}
	public function orWhereNotIn(){
	
	}
	
	public function orderBy($order){
		if(!is_array($order)){
			$this->order[] = $order;
		}
		else{
			foreach($order as $value){
				$this->order[] = $value;
			}
		}
		return $this;
	}
	
	public function assign($param,&$variable){
	
	}
	
	public function __toString(){
		return $this->type.' '.implode(', ',$this->fields).' from '.implode(', ',$this->table).(count($this->join) > 0 ? ' '.implode(' ',$this->join) : '').(count($this->criteria) > 0 ? ' where '.implode($this->criteria_link,$this->criteria) : '').(count($this->order) > 0 ? ' order by '.implode(', ',$this->order) : '').';';
	}
	
	public function clear(){
		$this->fields = array();
		$this->table = array();
		$this->join = array();
		$this->criteria = array();
		$this->criteria_link = null;
		$this->order = array();
	}
	
	public function __call($method, $args){
		return $this;
	}
	
}