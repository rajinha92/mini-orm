<?php

namespace app\Models;

use app\Core\DB;

abstract class AbstractModel{

	/**
	* Table/Entity name
	* @var string
	*/
	protected $table;
	/**
	* Primary key name
	* @var string
	*/
	protected $primary = 'id';
	/**
	* Array with table's columns, except primary key
	* @var Array
	*/
	protected $columns = [];
	/**
	* select sql structure
	* @var string
	*/
	protected $sqlSelect = "select {select} {from} {join} {where} {groupBy} {having} {orderBy} {start} {length}";
	/**
	* insert sql structure
	* @var string
	*/
	protected $sqlInsert = "insert into {insert} ({columns}) values ({values})";
	/**
	* update sql structure
	* @var string
	*/
	protected $sqlUpdate = "update {update} set {columns} {where}";

	/**
	* delete sql structure
	* @var string
	*/
	protected $sqlDelete = "delete from {delete} where {primary} = :primary";
	/**
	* holds pdo statement errors
	* @var Array
	*/
	protected $errorInfo;
	/**
	* quantity of parametrs in where clauses
	* @var integer
	*/
	private $paramCount = 0;
	/**
	* array of parameters to bind on executing select statement
	* @var Array
	*/
	private $paramsToBind = [];
	/**
	* Database PDO connection holder
	* @var PDO
	*/
	private $pdo;

	/**
	* Constructor - check if the Model has at least table name and one column assigned to it
	*/
	public function __construct(){
		if (empty($this->table))
		throw new InvalidConfigurationException("The property \$table must be assigned.");
		if(empty($this->columns))
		throw new InvalidConfigurationException("The property \$columns must be assigned.");
	}

	/**
	* prepare columns of select statement
	* @param  Array $columns array of columns to select
	* @return __CLASS__
	*/
	public function select(Array $columns = null){
		if(empty($columns)){
			$this->sqlSelect = str_replace('{select}', ' * ', $this->sqlSelect);
		} else {
			$this->sqlSelect = str_replace('{select}', implode(',', $columns), $this->sqlSelect);
		}

		return $this;
	}

	/**
	* set the from table of select statement
	* @param  [type] $from table name
	* @return __CLASS__
	*/
	public function from($from = null){
		$this->sqlSelect = str_replace('{from}',' from '.(is_null($from)?$this->table:$from),$this->sqlSelect);
		return $this;
	}

	/**
	* prepare join of select statement
	* @param  string $table    join table
	* @param  string $on       on clause
	* @param  Array $params   array with values to bind
	* @param  string $joinType join type - default(inner)
	* @return __CLASS__
	*/
	public function join($table, $on, Array $params = null, $joinType = 'inner'){
		$join = $joinType.' join ';
		if(strpos($on, '?') !== false && !is_null($params)){
			$occurrences = substr_count($on, '?');
			if(count($params) != $occurrences)
			throw new \InvalidArgumentException("The number of parameters in \$on doesn't match the number of parameters in \$params");
			for($i = 0; $i < $occurrences; $i++){
				$count = ++$this->paramCount;
				$on = str_replace('?',':'.$count,$on);
				$this->paramsToBind[] = [':'.$count,$param[$i],$this->pdoType(gettype($param))];
			}
		}

		$this->sqlSelect = str_replace('{join}', $join. $table.' on '.$on.' {join} ', $this->sqlSelect);

		return $this;
	}

	/**
	* prepare where clause of select statement
	* @param  string $where where condition
	* @param  mixed $param param value when needed to bind ? on where clause
	* @return __CLASS__
	*/
	public function where($where, $param = null){
		if(strpos($this->sqlSelect,' where ') !== false){
			$where = ' and '.$where;
		} else {
			$where = ' where '.$where;
		}
		if(strpos($where,'?') !== false && is_null($param)){
			throw new \InvalidArgumentException("When a ? is specified in \$where clause, you should set a \$param too.");
		}
		else if(strpos($where,'?') !== false){
			$count = ++$this->paramCount;
			$where = str_replace('?',':'.$count, $where);
			$this->paramsToBind[] = [':'.$count,$param,$this->pdoType(gettype($param))];
		}
		$this->sqlSelect = str_replace('{where}', $where.' {where} ', $this->sqlSelect);

		return $this;
	}

	/**
	* prepare OR where clause with of select statement
	* @param  [type] $where where condition
	* @param  mixed $param param value when needed to bind ? on where clause
	* @return __CLASS__
	*/
	public function whereOr($where, $param = null){
		$where = ' or '.$where;
		if(strpos($where,'?') !== false && is_null($param)){
			throw new \InvalidArgumentException("When a ? is specified in \$where clause, you should set a \$param too.");
		}
		else if(strpos($where,'?') !== false){
			$count = ++$this->paramCount;
			$where = str_replace('?',':'.$count, $where);
			$this->paramsToBind[] = [':'.$count,$param,$this->pdoType(gettype($param))];
		}
		$this->sqlSelect = str_replace('{where}', $where.' {where} ', $this->sqlSelect);

		return $this;
	}

	/**
	* prepare the group by clause of select statement
	* @param  string $groupBy list of columns to group by
	* @return __CLASS__
	*/
	public function groupBy($groupBy){
		$this->sqlSelect = str_replace('{groupBy}', $groupBy, $this->sqlSelect);
		return $this;
	}

	/**
	* prepare the having clause of select statement
	* @param  string $having having condition
	* @param  mixed $param  param value when needed to bind ? of having clause
	* @return __CLASS__
	*/
	public function having($having, $param = null){
		if(strpos($having,'?') !== false && is_null($param)){
			throw new \InvalidArgumentException("When a ? is specified in \$having clause, you should set a \$param too.");
		}
		else if(strpos($having,'?') !== false){
			$having = str_replace('?',':having',$having);
			$this->paramsToBind[] = [':having',$param,$this->pdoType(gettype($param))];
		}
		$this->sqlSelect = str_replace('{having}', $having, $this->sqlSelect);

		return $this;
	}

	/**
	* prepare the order by clause of select statement
	* @param  string $orderBy list of columns to order by
	* @return __CLASS__
	*/
	public function orderBy($orderBy){
		$this->sqlSelect = str_replace('{orderBy}', $orderBy, $this->sqlSelect);
		return $this;
	}

	/**
	* set a offset to record set returned by select statement
	* @param integer $offset quantity to skip on select query
	* @return __CLASS__
	*/
	public function offset($offset){
		$this->sqlSelect = str_replace('{start}', ' limit '.$offset.', ', $this->sqlSelect);
		return $this;
	}

	/**
	* set the number of records to take from select statement
	* @param  integer $length quantity to take on select query
	* @return __CLASS__
	*/
	public function take($length){
		if(strpos($this->sqlSelect,' limit ') === false){
			$length .= ' limit '.$length;
		}
		$this->sqlSelect = str_replace('{length}', $length, $this->sqlSelect);
		return $this;
	}

	/**
	* bind params and execute select query
	* @return stdClass
	*/
	public function exec(){
		$this->prepareQuery();
		$this->pdo = DB::getInstance();
		$stmt = $this->pdo->prepare($this->sqlSelect);
		foreach($this->paramsToBind as $paramToBind){
			$stmt->bindParam($paramToBind[0],$paramToBind[1],$paramToBind[2]);
		}

		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		$resultSet = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class($this));
		return $resultSet;
	}

	/**
	* fetch all records of the model
	* @return stdClass
	*/
	public function all(){
		$this->pdo = DB::getInstance();
		$stmt = $this->pdo->prepare("select * from $this->table");
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		$resultSet = $stmt->fetchAll(\PDO::FETCH_CLASS, get_class($this));
		return $resultSet;
	}

	/**
	* shows the select query built until now
	* @return string
	*/
	public function toSql(){
		$this->prepareQuery();
		return $this->sqlSelect;
	}

	/**
	* return the object of given primary key
	* @param  mixed $id primary key value
	* @return __CLASS__
	*/
	public function find($id){
		$this->pdo = DB::getInstance();
		$columns = array_merge([$this->primary], $this->columns);
		$stmt = $this->pdo->prepare("select ".implode(',',$columns)." from $this->table where $this->primary = :primary limit 1");
		$stmt->bindParam(':primary',$id, $this->pdoType(gettype($id)));
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $stmt->fetch(\PDO::FETCH_CLASS, get_class($this));
	}

	/**
	* perform a insert query to the database
	* @return mixed last inserted id
	*/
	public function insert(){
		$this->pdo = DB::getInstance();
		$columns = [];
		$values = [];
		foreach($this->columns as $column){
			if(isset($this->{$column})){
				$columns[] = $column;
				$values[] = ':'.$column;
			}
		}
		$this->sqlInsert = str_replace(['{insert}','{columns}','{values}'],[$this->table,implode(',',$columns),implode(',',$values)],$this->sqlInsert);
		$stmt = $this->pdo->prepare($this->sqlInsert);
		foreach ($columns as $column) {
			$stmt->bindParam(':'.$column, $this->{$column},$this->pdoType(gettype($this->{$column})));
		}
		$stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		$this->{$this->primary} = +$this->pdo->lastInsertId();
		return $this->{$this->primary};

	}

	/**
	* perform update query to the database
	* @param  string $where where condition to update
	* @return boolean
	*/
	public function update($where = null){
		if(!isset($this->{$this->primary}))
		throw new \InvalidArgumentException("Nothing to update, fill the primary key field");
		if(is_null($where))
		$where = " where ".$this->primary." = ".$this->{$this->primary};
		else
		$where = " where ".$where;
		$this->pdo = DB::getInstance();
		$columns = [];
		$updates = [];
		foreach($this->columns as $column){
			if(isset($this->{$column})){
				$columns[] = [$column,':'.$column];
				$updates[] = $column.' = :'.$column;
			}
		}
		$this->sqlUpdate = str_replace(['{update}','{columns}','{where}'],[$this->table,implode(',',$updates),$where], $this->sqlUpdate);
		$stmt = $this->pdo->prepare($this->sqlUpdate);
		foreach ($columns as $column) {
			$stmt->bindParam($column[1], $this->{$column[0]},$this->pdoType(gettype($this->{$column[0]})));
		}
		$return = $stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $return;
	}

	/**
	* save the current model to database choosing between insert or update based on primary key presence
	* @return mixed inserted id or boolean to update success
	*/
	public function save(){
		return isset($this->{$this->primary})?$this->update():$this->insert()	;
	}

	/**
	* delete the current model from database based on primary key value
	* @return boolean
	*/
	public function delete(){
		if(!isset($this->{$this->primary}))
		throw new \InvalidArgumentException("No value found for key $this->primary");
		$this->sqlDelete = str_replace(['{delete}','{primary}'],[$this->table,$this->primary],$this->sqlDelete);
		$this->pdo = DB::getInstance();
		$stmt = $this->pdo->prepare($this->sqlDelete);
		$stmt->bindParam(':primary',$this->{$this->primary},$this->pdoType(gettype($this->{$this->primary})));
		$return = $stmt->execute();
		$this->errorInfo = $stmt->errorInfo();
		return $return;
	}

	/**
	* return the first row of the resultant query or create a new object
	* @return __CLASS__
	*/
	public function firstOrNew(){
		$resultSet = $this->exec();
		if(empty($resultSet))
		return new get_class($this);
		else
		return $resultSet[0];
	}

	/**
	* return the error info array
	* @return Array
	*/
	public function errorInfo(){
		return $this->errorInfo();
	}

	private function pdoType($phpType){
		switch($phpType){
			case "integer": return \PDO::PARAM_INT;
			case "boolean": return \PDO::PARAM_BOOL;
			case "double": return \PDO::PARAM_STR;
			case "string": return \PDO::PARAM_STR;
			case "NULL": return \PDO::PARAM_NULL;
		}
	}

	private function prepareQuery(){
		$this->sqlSelect = str_replace('{from}', " * from $this->table", $this->sqlSelect);
		$this->sqlSelect = str_ireplace(['{select}', '{from}', '{join}', '{where}', '{groupBy}', '{having}', '{orderBy}' ,'{start}','{length}'],'',$this->sqlSelect);
		$this->sqlSelect = trim($this->sqlSelect);
	}
}

class InvalidConfigurationException extends \Exception{}
