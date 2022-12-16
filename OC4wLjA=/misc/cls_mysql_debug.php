<?php
/**
 * @author Bodi Zsolt <bodi.zsolt@gmail.com>
 * @copyright MedeeaWeb Works
 * @version 1.2
 * @package mysql_db
 */
class mysql_debug{
	var $sql = array();
	var $mysql_database_connection = 0;
	var $err = array();
	
	function setConnection($connection){
		$this->mysql_database_connection = $connection;
	}
	
	function getConnection(){
		return $this->mysql_database_connection;
	}
	
	function save($sql){
		if($sql != ''){
			$this->sql[] = $sql;
			return true;
		}
		return false;
	}
	
	function display(){
		return $this->sql;
	}

	function setError($err){
		return array_push($this->err,$err);
	}
	
	function getError(){
		return $err;
	}
}

/**
 * Singelton used to get a reference to the mysql_debug class
 *
 * @return object
 */
function &get_debug_instance(){
	static $DEBUGER;
	if(is_object($DEBUGER)){	
		return $DEBUGER;	
	}
	$DEBUGER = new mysql_debug();
	return $DEBUGER;
}
?>