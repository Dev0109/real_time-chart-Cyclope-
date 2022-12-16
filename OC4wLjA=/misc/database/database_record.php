<?php
class database_record implements ArrayAccess,Iterator,Paginate{
	protected $connection_id;
	protected $result_id;
	protected $result_object = array();
	protected $current_row = 0;
	protected $startingRow = 0;
	protected $rowPerPage = false;
	protected $broadcastEvents = false;
	protected $dispatcher = null;
	protected $id;
	
	public function __construct($connection_id, $result_id, $offset = null){
		$this->connection_id = $connection_id;
		$this->result_id = $result_id;
		if(!is_null($offset)){
			$this->moveTo($offset);
		}
	}
	
	/**
	 * move the pointer to the next row in the query result
	 * @access public
	 * @return bool false if the end is reached ,else return the current position
	 */
	public function next(){
		if($this->rowPerPage !== false){
			if($this->rowPerPage <= $this->current_row){
				$this->result_object = array();
				return false;
			}
		}
	    $this->result_object = $this->_fetch_array();
	    if(is_array($this->result_object)){
	        $this->current_row++;
	        $this->result_object = $this->broadcast('hasData',$this->result_object);
	        return $this->current_row;
	    }
    	return false;
	}

	/**
	 * offsets record pointer
	 *
	 * @param integer $row_number
	 * @return bool
	 */
	public function moveTo($row_number=0){
   		if(!$this->result_id){
   			return false;
   		}
   		if(!$this->records_count()){
   			return false;
   		}
   		
   		if($this->rowPerPage !== false && is_numeric($this->rowPerPage)){
   			$tmp_row_number = $row_number;
   			$row_number = ceil($row_number * $this->rowPerPage);
   		}
   		
   		if(($this->records_count()-1) < $row_number){
   			$row_number = $this->records_count()-1;
   		}
   		
   		if($row_number<0){
   			$row_number=0;
   		}
   		//if(!$this->_seek($row_number)){
		//$this->raise_error("db::moveTo","Failed to go at specified row!");
		//}

		if($this->rowPerPage !== false && is_numeric($this->rowPerPage)){
			$this->startingRow = $tmp_row_number;
		}else{
			$this->startingRow = $row_number;
		}
		$this->current_row = 0;
   		return true;
   	}
   	public function move_to($offset){
   		return $this->moveTo($offset);
   	}
	public function reset(){
		$this->moveTo(0);
	}
   	
 	/**
 	 * Get value of the specified field
 	 * @access public
 	 * @param string $mixFld
 	 * @return mixed
 	 */
	public function get_field($mixFld){
		return ($this->result_object[$mixFld]);
	}

	public function row(){
		if(is_array($this->result_object) && (0 < count($this->result_object))){
			return $this->result_object;
		}
		if($this->next()){
			return $this->result_object;
		}
		return array();
	}

	public function setRowPerPage($rowPerPage = 30){
		$this->rowPerPage = $rowPerPage;
	}
	
	public function getRowPerPage(){
		return $this->rowPerPage;
	}
	
	public function getCurrentPage(){
		return $this->startingRow;
	}
	
	/**
	 * Alias for get_field
	 *
	 * @param string $mixFld
	 * @return mixed
	 * @access public
	 */
	public function field($mixFld){
		$row = $this->row();
		return current($row);
	}
	
	/**
	 * Alias for get_field
	 *
	 * @param string $mixFld
	 * @return mixed
	 * @access public
	 */
	public function f($mixFld){
		return $this->get_field($mixFld);
	}

	/**
	 * Return the value of the field name from the $glob variable
	 * if it is set, otherwise returns the value of the current
	 * record in the RecordSet.
	 *
	 * @param mixed $mixFld
	 * @return mixed
	 * @access public
	 */
	function gf($mixFld){
		global $glob;
		if($glob[$mixFld]){
			return htmlspecialchars(stripslashes($glob[$mixFld]));
		}
		return($this->get_field($mixFld));
	}

	
	/**
	 * Return a list of database field names based on the curently selected values
	 *
	 * @return array
	 * @access public
	 */
	public function list_fields(){
		if(!count($this->result_object)){
			//we did not read in the 1st result set yet
			$this->next();
		}
		$out_arr = array();		
		foreach ($this->result_object as $key => $value){
			if(!is_numeric($key)){
				array_push($out_arr,$key);
			}
		}
		return $out_arr;
	}

	/**
	 * alias for records_count used for pagination
	 *
	 * @access public
	 * @return int
	 */
	public function getTotalRecords(){
		return $this->_num_rows();
	}	
	
	/**
	 * the number of records from the last query
	 *
	 * @access public
	 * @return int
	 */
	public function records_count(){
		return count((array)$this);
	}

    /**
     * the number of fields from the last query
     *
     * @access public
     * @return int
     */
	public function fields_count(){
		return $this->_num_fields();
	}

	/**
	 * Function responsable for displaying nice error messages
	 *
	 * @param string $f
	 * @param string $errMsg
	 */
  	protected function raise_error($f,$errMsg){
  		showError($errMsg);
    }
	
	public function next_array(){
		return $this->result_object;
	}

 	public function offsetSet($offset, $value) {
        $this->result_object[$offset] = $value;
    }
    
    public function offsetExists($offset) {
        return isset($this->result_object[$offset]);
    }
    
    public function offsetUnset($offset) {
        unset($this->result_object[$offset]);
    }
    
    public function offsetGet($field) {
        return $this->f($field);
    }
	
    public function rewind(){
    	$this->moveTo($this->startingRow);
    	return $this->next();
    }

    public function current(){
    	return $this->next_array();
    }

    public function key(){
    	return $this->current_row;
    }

    public function valid(){
    	return !empty($this->result_object);
    }
    
    public function setEventDispatcher($dispatcher){
    	$this->dispatcher = $dispatcher;
    	$this->broadcastEvents = true;
    	$this->id = 'sqlresult-'.rand(0,300);
    	return $this->id;
    }
    
    public function broadcast($evt,$params){
    	if(!$this->broadcastEvents){
    		return $params;
    	}
    	$e = $this->dispatcher->filter($this, $this->id.'-'.$evt, $params);
    	return $e->getReturnValue();
    }
}