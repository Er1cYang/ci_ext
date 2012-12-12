<?php
namespace ci_ext\db;

use ci_ext\db\CommandBuilder;
use ci_ext\events\ModelEvent;
use ci_ext\core\Exception;
use ci_ext\core\DbException;

/**
 * TableRecord
 * <pre>
 * Table Getway + Row Getway
 * </pre>
 * ==============================================
 * File encoding: UTF-8 
 * ----------------------------------------------
 * ActiveRow.php
 * ==============================================
 * @author YangDongqi <yangdongqi@gmail.com>
 * @copyright Copyright &copy; 2006-2012 Hayzone IT LTD.
 * @version $id$
 */
abstract class Table extends \ci_ext\events\EventDispatcher {
	
	const HAS_MANY = '';					// 一对多
	const HAS_ONE = '';						// 一对一
	const MANY_MANY = '';					// 多对多
	const BELONGS_TO = '';					// 属于
	
	const SCENARIO_INSERT = 'insert';		// 插入
	const SCENARIO_UPDATE = 'update';		// 更新

	const CASCADE_ALL = 7;					// 全部级联
	const CASCADE_DELETE = 4;				// 级联删除
	const CASCADE_VALIDATE = 2;				// 级联验证
	const CASCADE_SAVE = 1;					// 级联保存
	
	private $_alias = 't';					// 查询时表别名
	private $_scenario;					// 当前场景，用于rule和CUD操作
	private $_dbConnection;				// CodeIgniter数据库链接实例
	private $_new;							// 当前数据是否是新数据
	private $_related = array();			// 关联数据缓存
	private $_pk;							// 当前记录主键
	private $_attributes = array();		// 当前记录的所有属性，包括表字段
	
	/**
	 * 根据给定场景实例化一个对象
	 * @param string $scenario
	 */
	public function __construct($scenario=self::SCENARIO_INSERT) {
		$this->setScenario($scenario);
		$this->setIsNewRecord(true);
		$this->loadDefaultAttributes();
	}
	
	/**
	 * 初始化工作
	 * @return void
	 */
	public function init() {
	}
	
	/**
	 * 加载数据库字段到属性中
	 * @return void
	 */
	protected function loadDefaultAttributes() {
		$fields = $this->getDbConnection()->list_fields($this->tableName());
		foreach($fields as $f) {
			$this->_attributes[$f] = '';
		}
	}
	
	/**
	 * 是否是新数据
	 * @return boolean
	 */
	public function getIsNewRecord() {
		return $this->_new;
	}
	
	/**
	 * 设置当前记录是否是新数据
	 * @param boolean $isNew
	 * @return void
	 */
	public function setIsNewRecord($isNew) {
		$this->_new = $isNew;
	}
	
	/**
	 * 设置当前场景
	 * @param string $scenario
	 * @return void
	 */
	public function setScenario($scenario) {
		$this->_scenario = $scenario;
	}
	
	/**
	 * 获取当前场景
	 * @return string
	 */
	public function getScenario() {
		return $this->_scenario;
	}
	
	/**
	 * 获取数据库连接
	 * 如果不存在，就使用$this->load->database()加载数据库
	 * @return CI_DB_driver
	 */
	public function getDbConnection() {
		if(!$this->_dbConnection) {
			$this->load->database();
			$this->setDbConnection($this->db);
		}
		return $this->_dbConnection;
	}
	
	/**
	 * 设置数据库链接
	 * @param CI_DB_driver $connection
	 * @return void
	 */
	public function setDbConnection($connection) {
		$this->_dbConnection = $connection;
	}
	
	/**
	 * 设置当前记录的属性
	 * @param array $values
	 * @return void
	 */
	public function setAttributes(array $values) {
		foreach($values as $k=>$v) {
			if(property_exists($this, $k) || isset($this->_attributes[$k])) {
				$this->$k = $v;
			}
		}
	}
	
	/**
	 * 获取当前记录属性集合
	 * @param array $attributes
	 * @return array
	 */
	public function getAttributes($attributes=null) {
		return $this->_attributes;
	}
	
	/**
	 * 获取表的主键名称
	 * @return mixed
	 */
	public function primaryKey() {
		return $this->getDbConnection()->primary($this->tableName());
	}
	
	/**
	 * 验证规则
	 * @return array
	 */
	public function rules() {
		return array();
	}
	
	/**
	 * 模型关系
	 * @return array
	 */
	public function relations() {
		return array();
	}
	
	/**
	 * 模型静态属性名
	 * @return array
	 */
	public function attributeNames() {
		return array();
	}
	
	/**
	 * 保存模型
	 * 如果模型是newRecord，则调用insert否则调用update
	 * @param array $attributes
	 * @return boolean
	 */
	public function save($attributes=array()) {
		if($this->getIsNewRecord()) {
			return $this->insert($attributes);
		} else {
			return $this->update($attributes);
		}
	}
	
	/**
	 * 插入记录
	 * @param array $attributes
	 * @throws DbException
	 * @return boolean
	 */
	public function insert($attributes=null) {
		if(!$this->getIsNewRecord()) {
			throw new DbException('The active record cannot be inserted to database because it is not new.');
		}
		if($this->beforeSave()) {
			$builder=$this->getCommandBuilder();
			$table=$this->tableName();
			$sql=$builder->createInsertCommand($table, $this->getAttributes($attributes));
			if($this->getDbConnection()->query($sql)) {
				$pk = $this->primaryKey();
				$this->$pk = $builder->getLastInsertID($table);
				$this->_pk=$this->getPrimaryKey();
				$this->afterSave();
				$this->setIsNewRecord(false);
				$this->setScenario('update');
				return true;
			}
		}
		return false;
	}
	
	/**
	 * 更新记录
	 * @param array $attributes
	 * @throws DbException
	 * @return boolean
	 */
	protected function update($attributes=null) {
		if($this->getIsNewRecord()) {
			throw new DbException('The active record cannot be updated because it is new.');
		}
		if($this->beforeSave()) {
			if($this->_pk===null) {
				$this->_pk=$this->getPrimaryKey();
			}
			$this->updateByPk($this->getOldPrimaryKey(), $this->getAttributes($attributes));
			$this->_pk=$this->getPrimaryKey();
			$this->afterSave();
			return true;
		}
		return false;
	}
	
	/**
	 * 按primary key更新记录
	 * 返回受影响的行数
	 * @param mixed $pk
	 * @param array $attributes
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function updateByPk($pk,$attributes,$condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$table=$this->tableName();
		$criteria=$builder->createPkCriteria($table,$pk,$condition,$params);
		$sql=$builder->createUpdateCommand($table,$attributes,$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 按给定条件更新记录
	 * 返回受影响的行数
	 * @param array $attributes
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function updateAll($attributes,$condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$sql=$builder->createUpdateCommand($this->tableName(),$attributes,$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 更新一个或多个计数器
	 * 返回受影响的行数
	 * <code>
	 * $model->updateCounters(array(
	 * 	'read' => 1,
	 * 	'visit' => 1
	 * ));
	 * </code>
	 * 执行以上的代码，visit和read字段会自动递增1
	 * @param array $counters
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function updateCounters($counters,$condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$sql=$builder->createUpdateCounterCommand($this->tableName(),$counters,$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 删除当前记录
	 * <pre>
	 * 删除之前会调用beforeDelete，如果返回值是false，不执行真正的删除逻辑
	 * 删除成功后，会调用afterDelete，并且返回一个boolean值，表示该次操作
	 * 在数据库中执行是否成功
	 * </pre>
	 * @throws DbException
	 * @return boolean
	 */
	public function delete() {
		if(!$this->getIsNewRecord()) {
			if($this->beforeDelete()) {
				$result=$this->deleteByPk($this->getPrimaryKey())>0;
				$this->afterDelete();
				return $result;
			} else {
				return false;
			}
		} else {
			throw new DbException('The active record cannot be deleted because it is new.');
		}
	}
	
	/**
	 * 按照primary key删除一条数据
	 * <pre>
	 * 返回受影响的行数
	 * </pre>
	 * @param mixed $pk
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function deleteByPk($pk,$condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createPkCriteria($this->tableName(),$pk,$condition,$params);
		$sql=$builder->createDeleteCommand($this->tableName(),$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 按照条件删除多条数据
	 * <pre>
	 * 返回受影响的行数
	 * </pre>
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function deleteAll($condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$sql=$builder->createDeleteCommand($this->tableName(),$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 按照属性删除多条数据
	 * <pre>
	 * 返回受影响的行数
	 * </pre>
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function deleteAllByAttributes($attributes,$condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createColumnCriteria($this->tableName(),$attributes,$condition,$params);
		$sql=$builder->createDeleteCommand($this->tableName(),$criteria);
		$this->getDbConnection()->simple_query($sql);
		return $this->getDbConnection->affected_rows();
	}
	
	/**
	 * 查询单条记录
	 * <pre>
	 * $condition 可以为一个字符串,例如：name = 'zhangsan'
	 * 也可以是一个DbCriteria实例
	 * $params 参数最终会绑定到sql中，比如使用name=:name
	 * </pre>
	 * @param mixed $condition
	 * @param arary $params
	 * @return Object
	 */
	public function find($condition='',$params=array()) {
		$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
		return $this->query($criteria);
	}
	
	/**
	 * 查询多条记录
	 * <pre>
	 * 参数参考 @see find
	 * </pre>
	 * @param mixed $condition
	 * @param array $params
	 * @return array
	 */
	public function findAll($condition='',$params=array()) {
		$criteria=$this->getCommandBuilder()->createCriteria($condition,$params);
		return $this->query($criteria,true);
	}
	
	/**
	 * 按照primary key查询一条数据
	 * @param mixed $pk
	 * @param mixed $condition
	 * @param array $params
	 * @return Object
	 */
	public function findByPk($pk,$condition='',$params=array()) {
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createPkCriteria($this->tableName(),$pk,$condition,$params,$prefix);
		return $this->query($criteria);
	}
	
	/**
	 * 按照primary key查询多条数据
	 * @param mixed $pk
	 * @param mixed $condition
	 * @param array $params
	 * @return array
	 */
	public function findAllByPk($pk,$condition='',$params=array()) {
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createPkCriteria($this->tableName(),$pk,$condition,$params,$prefix);
		return $this->query($criteria,true);
	}
	
	/**
	 * 按照属性查询一条数据
	 * @param array $attributes
	 * @param mixed $condition
	 * @param array $params
	 * @return Object
	 */
	public function findByAttributes($attributes,$condition='',$params=array()) {
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createColumnCriteria($this->tableName(),$attributes,$condition,$params,$prefix);
		return $this->query($criteria);
	}
	
	/**
	 * 按照属性查询多条数据
	 * @param array $attributes
	 * @param mixed $condition
	 * @param array $params
	 * @return array
	 */
	public function findAllByAttributes($attributes,$condition='',$params=array()) {
		$prefix=$this->getTableAlias(true).'.';
		$criteria=$this->getCommandBuilder()->createColumnCriteria($this->tableName(),$attributes,$condition,$params,$prefix);
		return $this->query($criteria,true);
	}
	
	/**
	 * 按照SQL查询一条数据
	 * @param string $sql
	 * @param array $params
	 * @return Object
	 */
	public function findBySql($sql,$params=array()) {
		$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return $this->populateRecord($result?$result[0]:false);
	}
	
	/**
	 * 按照SQL查询多条数据
	 * @param string $sql
	 * @param array $params
	 * @return array
	 */
	public function findAllBySql($sql,$params=array()) {
		$command=$this->getCommandBuilder()->createSqlCommand($sql,$params);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return $this->populateRecords($result);
	}
	
	/**
	 * 统计符合条件的数据
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function count($condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$sql = $builder->createCountCommand($this->tableName(),$criteria);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return reset($result[0]);
	}
	
	/**
	 * 按属性统计符合条件的数据
	 * @param array $attributes
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function countByAttributes($attributes,$condition='',$params=array()) {
		$prefix=$this->getTableAlias(true).'.';
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createColumnCriteria($this->tableName(),$attributes,$condition,$params,$prefix);
		$sql = $builder->createCountCommand($this->tableName(),$criteria);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return reset($result[0]);
	}
	
	/**
	 * 按SQL统计符合条件的数据
	 * @param mixed $condition
	 * @param array $params
	 * @return integer
	 */
	public function countBySql($sql,$params=array()) {
		$sql = $this->getCommandBuilder()->createSqlCommand($sql,$params);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return reset($result[0]);
	}
	
	/**
	 * 按条件查询该数据是否存在
	 * @param mixed $condition
	 * @param array $params
	 * @return boolean
	 */
	public function exists($condition='',$params=array()) {
		$builder=$this->getCommandBuilder();
		$criteria=$builder->createCriteria($condition,$params);
		$criteria->select='1';
		$criteria->limit=1;
		$sql = $builder->createFindCommand($this->tableName(),$criteria);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return !empty($result);
	}
	
	/**
	 * 查询实现
	 * @param DbCriteria $criteria
	 * @param boolean $all
	 * @return mixed
	 */
	protected function query($criteria,$all=false) {
		$this->beforeFind();
		if(!$all) {
			$criteria->limit=1;
		}
		$sql = $this->getCommandBuilder()->createFindCommand($this->tableName(),$criteria);
		$result = $this->getDbConnection()->query($sql)->result('array');
		return $all ? $this->populateRecords($result,true,$criteria->index) : $this->populateRecord($result?$result[0]:false);
	}
	
	/**
	 * 实例化，此处用于多态，一般情况下无需覆盖
	 * @param array $attributes
	 * @return Object
	 */
	protected function instantiate($attributes) {
		$class=get_class($this);
		$model=new $class(null);
		return $model;
	}
	
	/**
	 * 将数组转换为一个对象
	 * @param array $attributes
	 * @param boolean $callAfterFind
	 * @return Object
	 */
	public function populateRecord($attributes,$callAfterFind=true) {
		if($attributes!==false) {
			$record=$this->instantiate($attributes);
			$record->setScenario('update');
			$record->_new = false;
			$record->init();
			foreach($attributes as $name=>$value) {
				if(property_exists($record,$name)) {
					$record->$name=$value;
				} else if(isset($this->_attributes[$name])) {
					$record->_attributes[$name]=$value;
				}
			}
			$record->_pk=$record->getPrimaryKey();
			// $record->attachBehaviors($record->behaviors());
			if($callAfterFind) {
				$record->afterFind();
			}
			return $record;
		} else {
			return null;
		}
	}
	
	/**
	 * 将数组数据集合转换为一个对象集合
	 * @param array $data
	 * @param boolean $callAfterFind
	 * @param string $index
	 * @return array
	 */
	public function populateRecords($data,$callAfterFind=true,$index=null) {
		$records=array();
		foreach($data as $attributes) {
			if(($record=$this->populateRecord($attributes,$callAfterFind))!==null) {
				if($index===null)
					$records[]=$record;
				else
					$records[$record->$index]=$record;
			}
		}
		return $records;
	}

	/**
	 * 获取表的别名
	 * @param boolean $quote
	 * @return string
	 */
	public function getTableAlias($quote=false) {
		return $quote ? $this->getCommandBuilder()->quoteTableName($this->_alias) : $this->_alias;
	}
	
	/**
	 * 设置表的别名
	 * @param string $alias
	 * @return void
	 */
	public function setTableAlias($alias) {
		$this->_alias = $alias;
	}
	
	/**
	 * 获取SQL构造器
	 * @return CommandBuilder
	 */
	public function getCommandBuilder() {
		return new CommandBuilder();
	}
	
	/**
	 * 可以用该方法获取到旧的primary key
	 * @return mixed
	 */
	public function getOldPrimaryKey() {
		return $this->_pk;
	}
	
	/**
	 * 获取表名
	 * @return string
	 */
	public function tableName() {
		return get_class($this);
	}
	
	/**
	 * 获取主键的值
	 * @return mixed
	 */
	public function getPrimaryKey() {
		return $this->id;
	}
	
	/**
	 * 设置主键的值
	 * @param mixed $value
	 */
	public function setPrimaryKey($value) {
		$this->_pk = $value;
		return $this->id = $value;
	}
	
	/**
	 * 重写__get规则 支持了$this->_attributes
	 * @see ci_ext\core.Object::__get()
	 */
	public function __get($key) {
		$CI =& get_instance();
		if(property_exists($CI, $key)) {
			return $CI->$key;
		} else if(isset($this->_attributes[$key])) {
			return $this->_attributes[$key];
		} else {
			return parent::__get($key);
		}
	}
	
	/**
	 * 重写__set规则 支持了$this->_attributes
	 * @see ci_ext\core.Object::__set()
	 */
	public function __set($key, $value) {
		if(parent::__isset($key)) {
			parent::__set($key, $value);
		} else if(isset($this->_attributes[$key])) {
			$this->_attributes[$key] = $value;
		} else {
			parent::__set($key, $value);
		}
		
	}
	
	/**
	 * 触发事件beforeSave
	 * @return boolean
	 */
	protected function beforeSave() {
		return true;
	}
	
	/**
	 * 触发事件afterSave
	 * @return void
	 */
	protected function afterSave() {
	}
	
	/**
	 * 触发事件beforeValidate
	 * @return boolean
	 */
	protected function beforeValidate() {
		return true;
	}
	
	/**
	 * 触发事件afterValidate
	 * @return void
	 */
	protected function afterValidate() {
	}
	
	/**
	 * 触发事件beforeDelete
	 * @return boolean
	 */
	protected function beforeDelete() {
		return true;
	}
	
	/**
	 * 触发事件afterDelete
	 * @return void
	 */
	protected function afterDelete() {
	}
	
	/**
	 * 触发事件beforeFind
	 * @return void
	 */
	protected function beforeFind() {
	}
	
	/**
	 * 触发事件afterFind
	 * @return void
	 */
	protected function afterFind() {
	}
	
	
}

?>