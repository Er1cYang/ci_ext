<?php
namespace ci_ext\db;
use ci_ext\db\DbException;
class DbCommandBuilder extends \ci_ext\core\Object {
	
	const PARAM_PREFIX=':cip';

	private $_connection;

	public function __get($key) {
		$CI =& get_instance();
		return $CI->$key;
	}
	
	public function __construct() {
		$this->load->database();
		$this->_connection = $this->db;
	}

	public function getDbConnection() {
		return $this->_connection;
	}

	public function getLastInsertID($table) {
		$sql = 'select LAST_INSERT_ID()';
		$result = $this->getDbConnection()->query($sql)->result('array');
		return $result[0]['LAST_INSERT_ID()'];
	}
	
	public function quoteTableName($name) {
		return "`$name`";
	}
	
	public function quoteColumnName($name) {
		return "`$name`";
	}
	
	public function getPrimaryKey($table) {
		return $table->primaryKey();
	}
	
	public function getTableRawName($table) {
		return $this->quoteTableName($table->tableName());
	}
	
	public function quoteValue($value) {
		return "'{$value}'";	
	}

	public function createFindCommand($table,$criteria,$alias='t') {
		$this->ensureTable($table);
		$select=is_array($criteria->select) ? implode(', ',$criteria->select) : $criteria->select;
		if($criteria->alias!='')
			$alias=$criteria->alias;
		$alias=$this->quoteTableName($alias);

		$tableRawName = $this->getTableRawName($table);
		$sql=($criteria->distinct ? 'SELECT DISTINCT':'SELECT')." {$select} FROM {$tableRawName} $alias";
		$sql=$this->applyJoin($sql,$criteria->join);
		$sql=$this->applyCondition($sql,$criteria->condition);
		$sql=$this->applyGroup($sql,$criteria->group);
		$sql=$this->applyHaving($sql,$criteria->having);
		$sql=$this->applyOrder($sql,$criteria->order);
		$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
		$sql=$this->bindValues($sql, $criteria->params);
		return $sql;
	}

	public function createCountCommand($table,$criteria,$alias='t') {
		$this->ensureTable($table);
		if($criteria->alias!='')
			$alias=$criteria->alias;
		$alias=$this->quoteTableName($alias);

		if(!empty($criteria->group) || !empty($criteria->having)) {
			$select=is_array($criteria->select) ? implode(', ',$criteria->select) : $criteria->select;
			if($criteria->alias!='')
				$alias=$criteria->alias;
			$tableRawName = $this->getTableRawName($table);
			$sql=($criteria->distinct ? 'SELECT DISTINCT':'SELECT')." {$select} FROM {$tableRawName} $alias";
			$sql=$this->applyJoin($sql,$criteria->join);
			$sql=$this->applyCondition($sql,$criteria->condition);
			$sql=$this->applyGroup($sql,$criteria->group);
			$sql=$this->applyHaving($sql,$criteria->having);
			$sql="SELECT COUNT(*) FROM ($sql) sq";
		} else {
			if(is_string($criteria->select) && stripos($criteria->select,'count')===0) {
				$sql="SELECT ".$criteria->select;
			} else if($criteria->distinct) {
				$primaryKey = $this->getPrimaryKey($table);
				if(is_array($primaryKey)) {
					$pk=array();
					foreach($primaryKey as $key)
						$pk[]=$alias.'.'.$key;
					$pk=implode(', ',$pk);
				} else {
					$pk=$alias.'.'.$primaryKey;
				}
				$sql="SELECT COUNT(DISTINCT $pk)";
			} else {
				$sql="SELECT COUNT(*)";
			}
			
			$tableRawName = $this->getTableRawName($table);
			$sql.=" FROM $tableRawName $alias";
			$sql=$this->applyJoin($sql,$criteria->join);
			$sql=$this->applyCondition($sql,$criteria->condition);
		}

		$sql = $this->bindValues($sql,$criteria->params);
		return $sql;
	}

	public function createDeleteCommand($table,$criteria) {
		$this->ensureTable($table);
		$tableRawName = $this->getTableRawName($table);
		$sql="DELETE FROM $tableRawName";
		$sql=$this->applyJoin($sql,$criteria->join);
		$sql=$this->applyCondition($sql,$criteria->condition);
		$sql=$this->applyGroup($sql,$criteria->group);
		$sql=$this->applyHaving($sql,$criteria->having);
		$sql=$this->applyOrder($sql,$criteria->order);
		$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
		$sql=$this->bindValues($sql,$criteria->params);
		return $sql;
	}

	public function createInsertCommand($table,$data) {
		$this->ensureTable($table);
		$fields=array();
		$values=array();
		$i=0;
		foreach($data as $name=>$value) {
			$fields[]=$this->quoteColumnName($name);
			$values[]=$this->quoteValue($value);
			$i++;
		}
		if($fields===array()) {
			$pks=$this->getPrimaryKey($table);
			$pks=is_array($pks) ? $pks : array($pks);
			foreach($pks as $pk) {
				$fields[]=$this->quoteColumnName($pk);
				$placeholders[]='NULL';
			}
		}
		$tableRawName = $this->getTableRawName($table);
		$sql="INSERT INTO {$tableRawName} (".implode(', ',$fields).') VALUES ('.implode(', ',$values).')';
		
		return $sql;
	}

	public function createUpdateCommand($table,$data,$criteria) {
		$this->ensureTable($table);
		$fields=array();
		$values=array();
		$bindByPosition=isset($criteria->params[0]);
		$i=0;
		foreach($data as $name=>$value) {
			$fields[]=$this->quoteColumnName($name).'='.$this->quoteValue($value);
		}
		if($fields===array()) {
			throw new DbException('No columns are being updated for table "'.$table.'"');
		}
		$tableRawName = $this->getTableRawName($table);
		$sql="UPDATE {$tableRawName} SET ".implode(', ',$fields);
		$sql=$this->applyJoin($sql,$criteria->join);
		$sql=$this->applyCondition($sql,$criteria->condition);
		$sql=$this->applyOrder($sql,$criteria->order);
		$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
		$sql=$this->bindValues($sql,$criteria->params);
		return $sql;
	}

	public function createUpdateCounterCommand($table,$counters,$criteria) {
		$this->ensureTable($table);
		$fields=array();
		foreach($counters as $name=>$value) {
			$value=(int)$value;
			$columnRawName = $this->quoteColumnName($name);
			if($value<0)
				$fields[]="{$columnRawName}={$columnRawName}-".(-$value);
			else
				$fields[]="{$columnRawName}={$columnRawName}+".$value;
		}
		if($fields!==array()) {
			$tableRawName = $this->getTableRawName($table);
			$sql="UPDATE $tableRawName SET ".implode(', ',$fields);
			$sql=$this->applyJoin($sql,$criteria->join);
			$sql=$this->applyCondition($sql,$criteria->condition);
			$sql=$this->applyOrder($sql,$criteria->order);
			$sql=$this->applyLimit($sql,$criteria->limit,$criteria->offset);
			$sql=$this->bindValues($sql,$criteria->params);
			return $sql;
		} else {
			throw new DbException('No counter columns are being updated for table "'.$table.'".');
		}
	}

	public function createSqlCommand($sql,$params=array()) {
		$this->bindValues($sql,$params);
		return $sql;
	}

	public function applyJoin($sql,$join) {
		if($join!='')
			return $sql.' '.$join;
		else
			return $sql;
	}

	public function applyCondition($sql,$condition) {
		if($condition!='')
			return $sql.' WHERE '.$condition;
		else
			return $sql;
	}

	public function applyOrder($sql,$orderBy) {
		if($orderBy!='')
			return $sql.' ORDER BY '.$orderBy;
		else
			return $sql;
	}

	public function applyLimit($sql,$limit,$offset) {
		if($limit>=0)
			$sql.=' LIMIT '.(int)$limit;
		if($offset>0)
			$sql.=' OFFSET '.(int)$offset;
		return $sql;
	}

	public function applyGroup($sql,$group) {
		if($group!='')
			return $sql.' GROUP BY '.$group;
		else
			return $sql;
	}

	public function applyHaving($sql,$having) {
		if($having!='')
			return $sql.' HAVING '.$having;
		else
			return $sql;
	}

	public function bindValues($sql, $values) {
		foreach($values as $k=>$v) {
			$sql = str_replace($k, $this->quoteValue($v), $sql);
		}
		return $sql;
	}

	public function createCriteria($condition='',$params=array()) {
		if(is_array($condition)) {
			$criteria=new DbCriteria($condition);
		} else if($condition instanceof DbCriteria) {
			$criteria=clone $condition;
		} else {
			$criteria=new DbCriteria;
			$criteria->condition=$condition;
			$criteria->params=$params;
		}
		return $criteria;
	}
	public function createPkCriteria($table,$pk,$condition='',$params=array(),$prefix=null) {
		
		$this->ensureTable($table);
		$criteria=$this->createCriteria($condition,$params);
		if($criteria->alias!='') {
			$prefix=$this->quoteTableName($criteria->alias).'.';
		}
		if(!is_array($pk)) {
			$pk=array($pk);
		}
		if(is_array($this->getPrimaryKey($table)) && !isset($pk[0]) && $pk!==array()) {
			$pk=array($pk);
		}
		$condition=$this->createInCondition($table,$this->getPrimaryKey($table),$pk,$prefix);
		if($criteria->condition!='') {
			$criteria->condition=$condition.' AND ('.$criteria->condition.')';
		} else {
			$criteria->condition=$condition;
		}
		
		return $criteria;
	}

	public function createPkCondition($table,$values,$prefix=null) {
		$this->ensureTable($table);
		return $this->createInCondition($table,$this->getPrimaryKey($table),$values,$prefix);
	}

	public function createColumnCriteria($table,$columns,$condition='',$params=array(),$prefix=null) {
		$this->ensureTable($table);
		$criteria=$this->createCriteria($condition,$params);
		if($criteria->alias!='')
			$prefix=$this->quoteTableName($criteria->alias).'.';
		$bindByPosition=isset($criteria->params[0]);
		$conditions=array();
		$values=array();
		$i=0;
		if($prefix===null) {
			$prefix=$table->rawName.'.';
		}
		foreach($columns as $name=>$value) {
			$columnRawName = $this->quoteColumnName($name);
			if(is_array($value)) {
				$conditions[]=$this->createInCondition($table,$name,$value,$prefix);
			} else if($value!==null) {
				if($bindByPosition) {
					$conditions[]=$prefix.$columnRawName.'=?';
					$values[]=$value;
				} else {
					$conditions[]=$prefix.$columnRawName.'='.self::PARAM_PREFIX.$i;
					$values[self::PARAM_PREFIX.$i]=$value;
					$i++;
				}
			} else {
				$conditions[]=$prefix.$columnRawName.' IS NULL';
			}
		}
		$criteria->params=array_merge($values,$criteria->params);
		if(isset($conditions[0]))
		{
			if($criteria->condition!='')
				$criteria->condition=implode(' AND ',$conditions).' AND ('.$criteria->condition.')';
			else
				$criteria->condition=implode(' AND ',$conditions);
		}
		return $criteria;
	}
	
	public function createSearchCondition($table,$columns,$keywords,$prefix=null,$caseSensitive=true) {
		$this->ensureTable($table);
		$tableRawName = $this->getTableRawName($table);
		if(!is_array($keywords))
			$keywords=preg_split('/\s+/u',$keywords,-1,PREG_SPLIT_NO_EMPTY);
		if(empty($keywords))
			return '';
		if($prefix===null)
			$prefix=$tableRawName.'.';
		$conditions=array();
		foreach($columns as $name) {
			$columnRawName = $this->quoteColumnName($name);
			$condition=array();
			foreach($keywords as $keyword) {
				$keyword='%'.strtr($keyword,array('%'=>'\%', '_'=>'\_')).'%';
				if($caseSensitive)
					$condition[]=$prefix.$columns.' LIKE '.$this->quoteValue('%'.$keyword.'%');
				else
					$condition[]='LOWER('.$prefix.$columnRawName.') LIKE LOWER('.$this->quoteValue('%'.$keyword.'%').')';
			}
			$conditions[]=implode(' AND ',$condition);
		}
		return '('.implode(' OR ',$conditions).')';
	}

	public function createInCondition($table,$columnName,$values,$prefix=null) {
		if(($n=count($values))<1) {
			return '0=1';
		}

		$this->ensureTable($table);
		$tableRawName = $this->getTableRawName($table);
		
		if($prefix===null) {
			$prefix=$tableRawName.'.';
		}

		if(is_array($columnName) && count($columnName)===1) {
			$columnName=reset($columnName);
		}

		if(is_string($columnName)) {
			$columnRawName = $this->quoteColumnName($columnName);
			foreach($values as &$value) {
				if(is_string($value)) {
					$value=$this->quoteValue($value);
				}
			}
			if($n===1) {
				return $prefix.$columnRawName.($values[0]===null?' IS NULL':'='.$values[0]);
			} else {
				return $prefix.$columnRawName.' IN ('.implode(', ',$values).')';
			}
		}
		else if(is_array($columnName)) {
			foreach($columnName as $name) {
				$columnRawName = $this->quoteColumnName($name);
				for($i=0;$i<$n;++$i) {
					if(isset($values[$i][$name])) {
						if(is_string($value)) {
							$values[$i][$name]=$this->quoteValue($value);
						} else {
							$values[$i][$name]=$value;
						}
					}
				}
			}
			if(count($values)===1) {
				$entries=array();
				foreach($values[0] as $name=>$value) {
					$columnRawName = $this->quoteColumnName($name);
					$entries[]=$prefix.$columnRawName.($value===null?' IS NULL':'='.$value);
				}
				return implode(' AND ',$entries);
			}

			return $this->createCompositeInCondition($table,$values,$prefix);
		} else {
			throw new DbException('Column name must be either a string or an array.');
		}
	}

	protected function createCompositeInCondition($table,$values,$prefix)
	{
		$keyNames=array();
		foreach(array_keys($values[0]) as $name)
			$keyNames[]=$prefix.$table->columns[$name]->rawName;
		$vs=array();
		foreach($values as $value)
			$vs[]='('.implode(', ',$value).')';
		return '('.implode(', ',$keyNames).') IN ('.implode(', ',$vs).')';
	}
	
	protected function ensureTable($table) {
		//$tables = $this->getDbConnection()->list_tables();
		/*if(!in_array($table, $tables)) {
			throw new DbException('yii','Table "'.$table.'" does not exist.');
		}*/
	}
	
}

?>