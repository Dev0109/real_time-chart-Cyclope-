<?php
class database_store{

	private static $instance;
	private $queries = array();
	private $database_connections = array();
	
	private function __construct(){}
	
	public static function getInstance(){
		if(!isset(self::$instance)){
			$c = __CLASS__;
			self::$instance = new $c;
		}
		return self::$instance;
	}
	
	public function hasConnection(){
		return count($this->database_connections) ? true : false;
	}
	
	public function setConnection($connection_index, $connection_resource){
		return $this->database_connections[$connection_index] = $connection_resource;
	}
	
	public function getConnection($connection_index = null){
		if(!is_null($connection_index)){
			return $this->database_connections[$connection_index];
		}
		return reset($this->database_connections);
	}

	public function push($query){
		array_push($this->queries,$query);
	}
	
	public function pop(){
		return array_pop($this->queries);
	}
	
	public function getQueries(){
		return $this->queries;
	}
	
	public function display(){
		$ret = array();
		foreach ($this->queries as $query){
			array_push($ret,$query['sql']);
		}
		return $ret;
	}
	
}

function get_debug_instance(){
	return database_store::getInstance();
}