<?php
class mysql_driver extends database_driver{

	private $_escape_char = '`';
	
	protected function _escape_identifier($identifier){
		if($this->_escape_char == ''){
			return $identifier;
		}
	
		if (strpos($identifier, '.') !== false){
			$str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $identifier).$this->_escape_char;			
		}else{
			$str = $this->_escape_char.$identifier.$this->_escape_char;
		}
		// remove duplicates if the user already included the escape
		return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
	}
	
	protected function _escape($str){
		if(is_array($str)){
			foreach($str as $key => $val){
				$str[$key] = $this->_escape($val);
	   		}
	   		return $str;
	   	}
				
		if(function_exists('mysqli_real_escape_string') && is_resource($this->connection_id)){
			return mysqli_real_escape_string($this->connection_id, $str);
		//}elseif (function_exists('mysql_escape_string')){
		//	return mysql_escape_string($str);
		}
		return addslashes($str);
	}
	
	protected function _prepare_query(){
		
	}
	
	protected function _limit($sql,$limit,$offset){
		return $sql;
	}
	
	protected function _error(){
		return @mysqli_error($this->connection_id);
	}
	
	protected function _errno(){
		return @mysqli_errno($this->connection_id);
	}
	
	protected function _select_db(){
		return mysqli_select_db($this->connection_id, $this->database);
	}
	
	protected function _db_query($sql){
		return @mysqli_query($this->connection_id, $sql);
	}
	
	protected function _connect_db(){
		$con_id = mysqli_connect($this->hostname,$this->username,$this->password);
/*		if(!is_resource($con_id)){
			$this->raise_error("db::conOpen()", "Failed to connect to server ! ".$this->_error());
		}*/
		return $con_id;
	}
	
	protected function _close_db(){
		return @mysqli_close($this->connection_id);
	}
	
	protected function _affected_rows(){
		return mysqli_affected_rows($this->connection_id);
	}
	
	protected function _insert_id(){
		return mysqli_insert_id($this->connection_id);
	}
	
	protected function _close(){
		@mysqli_close($this->connection_id);
	}
}
