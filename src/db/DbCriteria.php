<?php
namespace ci_ext\db;
class DbCriteria extends \ci_ext\core\Object {
	
	const PARAM_PREFIX = ':cip';
	public static $paramCount = 0;
	public $select = '*';
	public $distinct = false;
	public $condition = '';
	public $params = array ();
	public $limit = - 1;
	public $offset = - 1;
	public $order = '';
	public $group = '';
	public $join = '';
	public $having = '';
	public $with;
	public $alias;
	public $together;
	public $index;
	public $scopes;
	
	public function __construct($data = array()) {
		foreach ( $data as $name => $value )
			$this->$name = $value;
	}
	
	public function __wakeup() {
		$map = array ();
		$params = array ();
		foreach ( $this->params as $name => $value ) {
			$newName = self::PARAM_PREFIX . self::$paramCount ++;
			$map [$name] = $newName;
			$params [$newName] = $value;
		}
		$this->condition = strtr ( $this->condition, $map );
		$this->params = $params;
	}
	
	public function addCondition($condition, $operator = 'AND') {
		if (is_array ( $condition )) {
			if ($condition === array ())
				return $this;
			$condition = '(' . implode ( ') ' . $operator . ' (', $condition ) . ')';
		}
		if ($this->condition === '')
			$this->condition = $condition;
		else
			$this->condition = '(' . $this->condition . ') ' . $operator . ' (' . $condition . ')';
		return $this;
	}
	
	public function addSearchCondition($column, $keyword, $escape = true, $operator = 'AND', $like = 'LIKE') {
		if ($keyword == '')
			return $this;
		if ($escape)
			$keyword = '%' . strtr ( $keyword, array ('%' => '\%', '_' => '\_', '\\' => '\\\\' ) ) . '%';
		$condition = $column . " $like " . self::PARAM_PREFIX . self::$paramCount;
		$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $keyword;
		return $this->addCondition ( $condition, $operator );
	}
	
	public function addInCondition($column, $values, $operator = 'AND') {
		if (($n = count ( $values )) < 1)
			return $this->addCondition ( '0=1', $operator );
		if ($n === 1) {
			$value = reset ( $values );
			if ($value === null)
				return $this->addCondition ( $column . ' IS NULL' );
			$condition = $column . '=' . self::PARAM_PREFIX . self::$paramCount;
			$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
		} else {
			$params = array ();
			foreach ( $values as $value ) {
				$params [] = self::PARAM_PREFIX . self::$paramCount;
				$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
			}
			$condition = $column . ' IN (' . implode ( ', ', $params ) . ')';
		}
		return $this->addCondition ( $condition, $operator );
	}
	
	public function addNotInCondition($column, $values, $operator = 'AND') {
		if (($n = count ( $values )) < 1)
			return $this;
		if ($n === 1) {
			$value = reset ( $values );
			if ($value === null)
				return $this->addCondition ( $column . ' IS NOT NULL' );
			$condition = $column . '!=' . self::PARAM_PREFIX . self::$paramCount;
			$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
		} else {
			$params = array ();
			foreach ( $values as $value ) {
				$params [] = self::PARAM_PREFIX . self::$paramCount;
				$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
			}
			$condition = $column . ' NOT IN (' . implode ( ', ', $params ) . ')';
		}
		return $this->addCondition ( $condition, $operator );
	}
	
	public function addColumnCondition($columns, $columnOperator = 'AND', $operator = 'AND') {
		$params = array ();
		foreach ( $columns as $name => $value ) {
			if ($value === null)
				$params [] = $name . ' IS NULL';
			else {
				$params [] = $name . '=' . self::PARAM_PREFIX . self::$paramCount;
				$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
			}
		}
		return $this->addCondition ( implode ( " $columnOperator ", $params ), $operator );
	}
	
	public function compare($column, $value, $partialMatch = false, $operator = 'AND', $escape = true) {
		if (is_array ( $value )) {
			if ($value === array ())
				return $this;
			return $this->addInCondition ( $column, $value, $operator );
		} else
			$value = "$value";
		
		if (preg_match ( '/^(?:\s*(<>|<=|>=|<|>|=))?(.*)$/', $value, $matches )) {
			$value = $matches [2];
			$op = $matches [1];
		} else
			$op = '';
		
		if ($value === '')
			return $this;
		
		if ($partialMatch) {
			if ($op === '')
				return $this->addSearchCondition ( $column, $value, $escape, $operator );
			if ($op === '<>')
				return $this->addSearchCondition ( $column, $value, $escape, $operator, 'NOT LIKE' );
		} else if ($op === '')
			$op = '=';
		
		$this->addCondition ( $column . $op . self::PARAM_PREFIX . self::$paramCount, $operator );
		$this->params [self::PARAM_PREFIX . self::$paramCount ++] = $value;
		
		return $this;
	}
	
	public function addBetweenCondition($column, $valueStart, $valueEnd, $operator = 'AND') {
		if ($valueStart === '' || $valueEnd === '')
			return $this;
		
		$paramStart = self::PARAM_PREFIX . self::$paramCount ++;
		$paramEnd = self::PARAM_PREFIX . self::$paramCount ++;
		$this->params [$paramStart] = $valueStart;
		$this->params [$paramEnd] = $valueEnd;
		$condition = "$column BETWEEN $paramStart AND $paramEnd";
		
		if ($this->condition === '')
			$this->condition = $condition;
		else
			$this->condition = '(' . $this->condition . ') ' . $operator . ' (' . $condition . ')';
		return $this;
	}
	
	public function mergeWith($criteria, $useAnd = true) {
		$and = $useAnd ? 'AND' : 'OR';
		if (is_array ( $criteria ))
			$criteria = new self ( $criteria );
		if ($this->select !== $criteria->select) {
			if ($this->select === '*')
				$this->select = $criteria->select;
			else if ($criteria->select !== '*') {
				$select1 = is_string ( $this->select ) ? preg_split ( '/\s*,\s*/', trim ( $this->select ), - 1, PREG_SPLIT_NO_EMPTY ) : $this->select;
				$select2 = is_string ( $criteria->select ) ? preg_split ( '/\s*,\s*/', trim ( $criteria->select ), - 1, PREG_SPLIT_NO_EMPTY ) : $criteria->select;
				$this->select = array_merge ( $select1, array_diff ( $select2, $select1 ) );
			}
		}
		
		if ($this->condition !== $criteria->condition) {
			if ($this->condition === '')
				$this->condition = $criteria->condition;
			else if ($criteria->condition !== '')
				$this->condition = "({$this->condition}) $and ({$criteria->condition})";
		}
		
		if ($this->params !== $criteria->params)
			$this->params = array_merge ( $this->params, $criteria->params );
		
		if ($criteria->limit > 0)
			$this->limit = $criteria->limit;
		
		if ($criteria->offset >= 0)
			$this->offset = $criteria->offset;
		
		if ($criteria->alias !== null)
			$this->alias = $criteria->alias;
		
		if ($this->order !== $criteria->order) {
			if ($this->order === '')
				$this->order = $criteria->order;
			else if ($criteria->order !== '')
				$this->order = $criteria->order . ', ' . $this->order;
		}
		
		if ($this->group !== $criteria->group) {
			if ($this->group === '')
				$this->group = $criteria->group;
			else if ($criteria->group !== '')
				$this->group .= ', ' . $criteria->group;
		}
		
		if ($this->join !== $criteria->join) {
			if ($this->join === '')
				$this->join = $criteria->join;
			else if ($criteria->join !== '')
				$this->join .= ' ' . $criteria->join;
		}
		
		if ($this->having !== $criteria->having) {
			if ($this->having === '')
				$this->having = $criteria->having;
			else if ($criteria->having !== '')
				$this->having = "({$this->having}) $and ({$criteria->having})";
		}
		
		if ($criteria->distinct > 0)
			$this->distinct = $criteria->distinct;
		
		if ($criteria->together !== null)
			$this->together = $criteria->together;
		
		if ($criteria->index !== null)
			$this->index = $criteria->index;
		
		if (empty ( $this->scopes ))
			$this->scopes = $criteria->scopes;
		else if (! empty ( $criteria->scopes )) {
			$scopes1 = ( array ) $this->scopes;
			$scopes2 = ( array ) $criteria->scopes;
			foreach ( $scopes1 as $k => $v ) {
				if (is_integer ( $k ))
					$scopes [] = $v;
				else if (isset ( $scopes2 [$k] ))
					$scopes [] = array ($k => $v );
				else
					$scopes [$k] = $v;
			}
			foreach ( $scopes2 as $k => $v ) {
				if (is_integer ( $k ))
					$scopes [] = $v;
				else if (isset ( $scopes1 [$k] ))
					$scopes [] = array ($k => $v );
				else
					$scopes [$k] = $v;
			}
			$this->scopes = $scopes;
		}
		
		if (empty ( $this->with ))
			$this->with = $criteria->with;
		else if (! empty ( $criteria->with )) {
			$this->with = ( array ) $this->with;
			foreach ( ( array ) $criteria->with as $k => $v ) {
				if (is_integer ( $k ))
					$this->with [] = $v;
				else if (isset ( $this->with [$k] )) {
					$excludes = array ();
					foreach ( array ('joinType', 'on' ) as $opt ) {
						if (isset ( $this->with [$k] [$opt] ))
							$excludes [$opt] = $this->with [$k] [$opt];
						if (isset ( $v [$opt] ))
							$excludes [$opt] = ($opt === 'on' && isset ( $excludes [$opt] ) && $v [$opt] !== $excludes [$opt]) ? "($excludes[$opt]) AND $v[$opt]" : $v [$opt];
						unset ( $this->with [$k] [$opt] );
						unset ( $v [$opt] );
					}
					$this->with [$k] = new self ( $this->with [$k] );
					$this->with [$k]->mergeWith ( $v, $useAnd );
					$this->with [$k] = $this->with [$k]->toArray ();
					if (count ( $excludes ) !== 0)
						$this->with [$k] = \ci_ext\utils\HashMap::mergeArray ( $this->with [$k], $excludes );
				} else
					$this->with [$k] = $v;
			}
		}
	}
	
	public function toArray() {
		$result = array ();
		foreach ( array ('select', 'condition', 'params', 'limit', 'offset', 'order', 'group', 'join', 'having', 'distinct', 'scopes', 'with', 'alias', 'index', 'together' ) as $name )
			$result [$name] = $this->$name;
		return $result;
	}

}

?>