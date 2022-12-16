<?php
/**
 * @author Bodi Zsolt <bodi.zsolt@gmail.com>
 * @copyright MedeeaWeb Works
 * @version 1.1
 * @package mysql_db
 */
class mysql_record
{
	var $conn_id		= NULL;
	var $result_id		= NULL;
	var $result_array	= array();
	var $result_object	= array();
	var $current_row 	= 0;

	/**
	 * move the pointer to the next row in the query result
	 * @access public
	 * @return bool false if the end is reached ,else return the current position
	 */
	function next(){
	    $this->result_object = @mysql_fetch_array($this->result_id);
	    if(is_array($this->result_object)) {
	        $this->current_row++;
	        return $this->current_row;
	    }
    	return false;
	}

	/**
	 * move the pointer to the next row in the query result
	 * @access public
	 * @return array
	 */
	function next_array(){
		while ($row = @mysql_fetch_assoc($this->result_id)){
			$this->result_array[] = $row;
		}
        $this->current_row++;
    	return $this->result_array;
	}

	/**
	 * offsets record pointer
	 *
	 * @param integer $row_number
	 * @return bool
	 */
	function move_to($row_number=0){
   		if(!$this->result_id){
   			return false;
   		}
   		if(!$this->records_count()){
   			return false;
   		}
   		if(($this->records_count()-1) < $row_number){
   			$row_number = $this->records_count()-1;
   		}
   		if($row_number<0){
   			$row_number=0;
   		}
		if(!mysql_data_seek($this->result_id,$row_number)){
			$this->raise_error("db::move_to","Failed to go at specified row!");
		}
   		return true;
   	}

	function reset(){
		$this->move_to(0);		
	}
   	
 	/**
 	 * Get value of the specified field
 	 * @access public
 	 * @param string $mixFld
 	 * @return mixed
 	 */
	function get_field($mixFld){
		return ($this->result_object[$mixFld]);
	}

	function row(){
		if(is_array($this->result_object) && (0 < count($this->result_object))){
			return $this->result_object;
		}
		if($this->next()){
			return $this->result_object;
		}
		return array();
	}

	/**
	 * Alias for get_field
	 *
	 * @param string $mixFld
	 * @return mixed
	 * @access public
	 */
	function f($mixFld){
		return $this->get_field($mixFld);
	}

	/**
	 * Print in Browser the value of the field name from the $glob variable
     * if it is set, otherwise returns the value of the current
     * record in the RecordSet.
	 *
	 * @param string $mixFld
	 * @see gf();
	 */
	function pf($mixFld){
		echo $this->gf($mixFld);
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
		global $glob,$default;
		if($glob[$mixFld]){
			return htmlspecialchars(stripslashes($glob[$mixFld]));
		}elseif($default["$mixFld"]){
			return htmlspecialchars(stripslashes($default["$mixFld"]));
		}else{
			return($this->get_field($mixFld));
		}
	}

	/**
	 * Return a list of database field names based on the curently selected values
	 *
	 * @return array
	 * @access public
	 */
	function list_fields(){
		$out_arr = array();
		//let's see if it's an object or an array
		if(is_array($this->result_array) && 0 < count($this->result_array)){
			//defenetly a result object
				return array_keys($this->result_array[0]);//the first row should be enough
		}elseif (is_array($this->result_object) && 0 < count($this->result_object)){
			//hm...object?
			foreach (array_keys($this->result_object) as  $value ){
				if(!is_numeric($value)){
					array_push($out_arr,$value);
				}
			}
			return $out_arr;
		}
		return array();
	}

	/**
	 * the number of records from the last query
	 *
	 * @access public
	 * @return int
	 */
	function records_count(){
		return mysql_num_rows($this->result_id);
	}

    /**
     * the number of fields from the last query
     *
     * @access public
     * @return int
     */
	function fields_count(){
		return mysql_num_fields($this->result_id);
	}

	/**
	 * Function responsable for displaying nice error messages
	 *
	 * @param string $f
	 * @param string $errMsg
	 */
  	function raise_error($f,$errMsg){
	    if ($this->isTrans){
		    mysql_db_query($this->database,"ROLLBACK");
		    mysql_db_query($this->database,"set AUTOCOMMIT=1");
	    }
	    echo "<center><table border=0 cellpadding=2 cellspacing=2>";
	    echo "<tr><td><font color=\"red\" face=\"Times New Roman\"> ";
	    echo "Error:".$f." failed.ERROR MESSAGE IS: ".$errMsg;
	    echo "</font></td></tr></center>";
	    die("Process Halted!");
    }
    
}
?>