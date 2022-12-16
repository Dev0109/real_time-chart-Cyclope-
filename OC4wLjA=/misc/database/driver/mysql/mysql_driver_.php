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
				
		if(function_exists('mysql_real_escape_string') && is_resource($this->connection_id)){
			return mysql_real_escape_string($str, $this->connection_id);
		}elseif (function_exists('mysql_escape_string')){
			return mysql_escape_string($str);
		}
		return addslashes($str);
	}
	
	protected function _prepare_query(){
		
	}
	
	protected function _limit($sql,$limit,$offset){
		return $sql;
	}
	
	protected function _error(){
		return @mysql_error();
	}
	
	protected function _select_db(){
		return mysql_select_db($this->database,$this->connection_id);
	}
	
	protected function _db_query($sql){
		return @mysql_query($sql,$this->connection_id);
	}
	
	protected function _connect_db(){
		$con_id = mysql_connect($this->hostname,$this->username,$this->password);
		if(!is_resource($con_id)){
			$this->raise_error("db::conOpen()", "Failed to connect to server ! ".$this->_error());
		}
		return $con_id;
	}
	
	protected function _close_db(){
		return @mysql_close($this->connection_id);
	}
	
	protected function _affected_rows(){
		return mysql_affected_rows();
	}
	
	protected function _insert_id(){
		return mysql_insert_id();
	}
	
	protected function _close(){
		@mysql_close($this->connection_id);
	}
}