<?php


/**
 * @author ideadawn@gmail.com
 * @date 2014.3.4
 */

class F_Model_Pdo
{
    /**
     * PDO instances.
     *
     * @var array
     */
	static private $_pdos = array();
	/**
	 * PDO object.
	 *
	 * @var PDO
	 */
	protected $_pdo;
	
	/**
	 * This model's table name.
	 *
	 * @var string
	 */
	protected $_table = '';
	/**
	 * This table's primary key.
	 *
	 * @var string|array
	 */
	protected $_primary;
	
	/**
	 * The relationship between tables.
	 * 
	 * @var array
	 */
	protected $_relation;
	
	/**
	 * Model instances.
	 * 
	 * @var array
	 */
	static protected $_instance;
	
	/**
	 * Debug Mode.
	 * 
	 * @var bool
	 */
	protected $_debug;
	
	/**
	 * Last executed sql.
	 * 
	 * @var string
	 */
	protected $_sql;
	
	/**
	 * Connect database.
	 *
	 * @param string $dbname
	 * @param array $dbcnf
	 * @return PDO
	 */
	static public function connect(&$dbname, &$dbcnf)
	{
		if( isset(self::$_pdos[$dbname]) ) {
			return self::$_pdos[$dbname];
		}
		$options = array(PDO::MYSQL_ATTR_INIT_COMMAND => "set names {$dbcnf['charset']}");
		self::$_pdos[$dbname] = new PDO($dbcnf['dsn'], $dbcnf['username'], $dbcnf['passwd'], $options);
		return self::$_pdos[$dbname];
	}
	
	/**
	 * Init model's pdo driver.
	 *
	 * @param string $dbname
	 */
	public function __construct($dbname = null, $debug = false)
	{
		$db = Yaf_Application::app()->getConfig();
		$db = $db['db'];
		if( $dbname && isset($db[$dbname]) ) {
			$dbcnf = $db[$dbname];
		} else {
			$dbname = $db['default'];
			$dbcnf = $db[$dbname];
		}
		$this->_pdo = self::connect($dbname, $dbcnf);
		
		$this->_debug = $debug;
	}
	
	/**
	 * Get unique model.
	 * 
	 * @param string $model
	 * @return F_Model_Pdo
	 */
	static public function getInstance($model)
	{
	    $class = $model.'Model';
	    if( empty(self::$_instance[$model]) ) {
	        self::$_instance[$model] = new $class();
	    }
	    return self::$_instance[$model];
	}
	
	/**
	 * @return PDO
	 */
	public function getPdo()
	{
		return $this->_pdo;
	}
	
	/**
	 * Get the last executed sql.
	 * 
	 * @return string
	 */
	public function getLastSql()
	{
	    return $this->_sql;
	}
	
	/**
	 * 获取数据表名
	 * 
	 * @return string
	 */
	public function getTable()
	{
		return $this->_table;
	}
	/**
	 * 设置表名
	 * 
	 * @param string $table
	 */
	public function setTable($table)
	{
		$this->_table = $table;
	}
	/**
	 * 获取数据表主键
	 *
	 * @return string
	 */
	public function getPrimary()
	{
		return $this->_primary;
	}
	/**
	 * 设置主键
	 * 
	 * @param string $primary
	 */
	public function setPrimary($primary)
	{
		$this->_primary = $primary;
	}
	
	/**
	 * 获取数据表描述
	 * 
	 * @return string
	 */
	public function getTableLabel()
	{
		return '';
	}
	
	/**
	 * 获取数据表字段的描述信息
	 * 
	 * @return array
	 */
	public function getFieldsLabel()
	{
		return array(
		//	'field' => 'label',
		//	'field' => function(&$row){},
		);
	}
	
	/**
	 * 获取用于搜索的字段
	 * 
	 * @return array
	 */
	public function getFieldsSearch()
	{
	    return array(
    	//	'table_field' => array('name', 'html_dom_type', 'data_array | arguments', 'default_value', 'attr_array', 'css_array'),
		//	'id' => array('ID', 'input', null, '', array('required', 'placeholder'=>'序号'), array('width'=>'80px')),
		//	'status' => array('状态', 'select', array(0=>'关闭',1=>'开启'), 0),
		);
	}
	
	/**
	 * 获取列表字段的额外处理
	 * 
	 * @return array
	 */
	public function getFieldsPadding()
	{
	    return array();
	}
	
	/**
	 * 获取字段的回调函数，用于列表页面
	 * 操作列可用的字段名为：op_view, op_edit, op_delete
	 * 
	 * @return mixed
	 */
	public function getListCallback()
	{
	    return array(
	        //'field' => function(&$row){return '';},
	    );
	}
	
	/**
	 * 获取字段的回调函数，用于添加与编辑页面
	 *
	 * @return mixed
	 */
	public function getAddEditCallback()
	{
	    return array(
	        //'field' => function(&$row){return '';},
	    );
	}
	
	/**
	 * Get database error info.
	 *
	 * @return string
	 */
	public function error()
	{
		$err = $this->_pdo->errorInfo();
		return $err[2];
	}
	
	/**
	 * Insert a record.
	 *
	 * @param array $data
	 * @param bool $insert_id
	 * @return mixed
	 */
	public function insert($data, $insert_id = true)
	{
		if( empty($data) ) {
			return false;
		}
		$holder = implode(',', array_fill(0, count($data), '?'));
		$fields = implode(',', array_keys($data));
		$values = array_values($data);
		$sql = "INSERT INTO {$this->_table}({$fields}) VALUES({$holder})";
		$stm = $this->_pdo->prepare($sql);
	    $rs = $stm->execute($values);
        $error=$stm->errorInfo();
        if(!is_null($error[2])){
            throw new Exception($error[2]);
        }
		if( $insert_id ){
		    return $this->_pdo->lastInsertId();
		}
		if( $rs ){
			return $rs;
		}
		else {
			return $stm->errorCode() == '00000';
		}
	}
    
	/**
	 * Get a record by conditions.
	 *
	 * @param mixed $conditions
	 * @param mixed $selects
	 * @return mixed
	 */
	public function fetch($conditions, $selects = '*')
	{
		if( empty($conditions) || empty($selects) ) {
			return false;
		}
		if( is_array($conditions) ) {
			$where = $value = array();
			foreach( $conditions as $f=>$v )
			{
			    $where[] = "{$f}=?";
				$value[] = $v;
			}
			$where = implode(' AND ', $where);
		} else {
			$where = $conditions;
			$value = null;
		}
		if( is_array($selects) ) {
			$selects = implode(',', $selects);
		}
		$sql = "SELECT {$selects} FROM {$this->_table} WHERE {$where}";
		$stm = $this->_pdo->prepare($sql);
		$stm->execute($value);
		return $stm->fetch(PDO::FETCH_ASSOC);
	}
    
	/**
	 * Get all records by conditions.
	 *
	 * @param mixed $conditions
	 * @param int $pn
	 * @param int $limit
	 * @param mixed $selects
	 * @param string $orderby
	 * @return array
	 */
	public function fetchAll($conditions = null, $pn = 1, $limit = 30, $selects = '*', $orderby = null)
	{
		if( is_array($conditions) ) {
			$where = $value = array();
			foreach( $conditions as $f=>$v )
			{
			    $where[] = "{$f}=?";
				$value[] = $v;
			}
			$where = implode(' AND ', $where);
		} else {
			$where = $conditions;
			$value = null;
		}
		if( $where ) {
			$where = ' WHERE '.$where;
		}
		if( is_array($selects) ) {
			$selects = implode(',', $selects);
		}
		if( $orderby ) {
			$orderby = 'order by '.$orderby;
		}
		$offset = ($pn-1)*$limit;
		$sql = "SELECT {$selects} FROM {$this->_table} {$where} {$orderby} LIMIT {$offset},{$limit}";
		$stm = $this->_pdo->prepare($sql);
		$stm->execute($value);
		return $stm->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Get records count by conditions.
	 *
	 * @param mixed $conditions
	 * @param int $perpage
	 * @param string $countby
	 * @return int
	 */
	public function fetchCount($conditions = '', $perpage = 0, $countby = '*')
	{
		if( is_array($conditions) ) {
			$where = $value = array();
			foreach( $conditions as $f=>$v )
			{
			    $where[] = "{$f}=?";
				$value[] = $v;
			}
			$where = implode(' AND ', $where);
		} else {
			$where = $conditions;
			$value = null;
		}
		if( $where ) {
			$where = ' WHERE '.$where;
		}
	    $sql = "SELECT COUNT({$countby}) FROM {$this->_table} {$where}";
		$stm = $this->_pdo->prepare($sql);
		$stm->execute($value);
		$count = $stm->fetchColumn(0);
		if( $perpage > 0 ) {
			$count = ceil($count / $perpage);
		}
		return $count;
	}
    
	/**
	 * Delete record(s) by conditions.
	 *
	 * @param mixed $conditions
	 * @return bool
	 */
	public function delete($conditions)
	{
		if( empty($conditions) ) {
			return false;
		}
		if( is_array($conditions) ) {
			$where = $value = array();
			foreach( $conditions as $f=>$v )
			{
			    $where[] = "{$f}=?";
				$value[] = $v;
			}
			$where = implode(' AND ', $where);
		} else {
			$where = $conditions;
			$value = null;
		}
		$sql = "DELETE FROM {$this->_table} WHERE {$where}";
		$stm = $this->_pdo->prepare($sql);
		$rs = $stm->execute($value);
        $error=$stm->errorInfo();
        if(!is_null($error[2])){
            throw new Exception($error[2]);
        }
		if( $rs ) {
			return $rs;
		} else {
			return $stm->errorCode() == '00000';
		}
	}
    
	/**
	 * Update records by conditions.
	 *
	 * @param mixed $data
	 * @param mixed $conditions
	 * @return bool
	 */
	public function update($data, $conditions)
	{
		if( empty($conditions) || empty($data) ) {
			return false;
		}
		$value = array();
		if( is_array($data) ) {
			$set = array();
			foreach( $data as $f=>$v )
			{
				$set[] = "{$f}=?";
				$value[] = $v;
			}
			$set = implode(',', $set);
		} else {
			$set = $data;
		}
		if( is_array($conditions) ) {
			$where = array();
			foreach( $conditions as $f=>$v )
			{
			    $where[] = "{$f}=?";
				$value[] = $v;
			}
			$where = implode(' AND ', $where);
		} else {
			$where = $conditions;
		}
		$sql = "UPDATE {$this->_table} SET {$set} WHERE {$where}";
		$stm = $this->_pdo->prepare($sql);
		$rs = $stm->execute($value);
		$error=$stm->errorInfo();
        if(!is_null($error[2])){
            throw new Exception($error[2]);
        }
		if( $rs ) {
			return $rs;
		} else {
			return $stm->errorCode() == '00000';
		}
	}
	
	/**
	 * Get a record by sql.
	 * 
	 * @param string $sql
	 * @param mixed $params
	 * @return mixed
	 */
	public function fetchBySql($sql, $params = null)
	{
		$stm = $this->_pdo->prepare($sql);
		$stm->execute($params);
		return $stm->fetch(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Get all records by sql.
	 * 
	 * @param string $sql
	 * @param string $params
	 * @return array
	 */
	public function fetchAllBySql($sql, $params = null)
	{
		$stm = $this->_pdo->prepare($sql);
		$stm->execute($params);
		return $stm->fetchAll(PDO::FETCH_ASSOC);
	}
}
