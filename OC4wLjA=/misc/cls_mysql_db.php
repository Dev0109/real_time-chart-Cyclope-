<?php
/**
 * @author MedeeaWeb Works
 * @copyright MedeeaWeb Works
 * @package mysql
 * @version 5
 */
if(!defined('CURRENT_VERSION_FOLDER')){
	define('INSTALLPATH','');
}
if(!defined('INSTALLPATH')){
	define('INSTALLPATH',CURRENT_VERSION_FOLDER.'misc/');
}


require(INSTALLPATH.'database/database_driver.php');
require(INSTALLPATH.'database/driver/mysql/mysql_driver.php');
class mysql_db extends mysql_driver{
	
	/**
	 * store a record readed from database
	 * @access private
	 * @var array
	 */
	var $record = null;
	
	/**
	 * Marker used to declare a bind
	 *
	 * @var string
	 */
    var $bind_marker = '?';
	
	/**
	   * constructor of the class
	   *
	   * @example $dbu = new mysql_db(host=sheep, port=5432, dbname=mary, user=lamb, password=baaaa);
	   * @param string $strHost
	   * @param string $strUser
	   * @param string $strPass
	   * @param string $strDatabase
	   * @access public
	   */
	public function __construct($strHost = "",$strUser = "",$strPass = "",$strDatabase = "",$exitOnError = true) {
		$params = array(
			'hostname' => $strHost,
			'username' => $strUser,
			'password' => $strPass,
			'database' => $strDatabase,
			'exit_on_error' => $exitOnError,
			'driver' => 'mysql',
			'create_new' => false
		);
		//if there are no params sent we try to get the default constants and use those.
		$constants2properites = array('MYSQL_DB_HOST' => 'hostname',
									  'MYSQL_DB_USER' => 'username',
									  'MYSQL_DB_PASS' => 'password',
									  'MYSQL_DB_NAME' => 'database',
									  'MYSQL_EXIT_ON_ERROR' => 'exit_on_error');
		foreach ($constants2properites as $constant => $property){
			if(!defined($constant)){
				continue;
			}
			$params[$property] = constant($constant);
		}
		parent::__construct($params);
	}
	
	/**
	 * The query() function returns a database result object when "read" type queries are run,
	 * which you can use to show your results. When "write" type queries are run it simply returns
	 * TRUE or FALSE depending on success or failure.
	 *
	 * @param string $strQuery
	 * @param mixed $binds string or array of values to bind to this query
	 * @param bool $return_object
	 * @return mixed
	 */
	public function query($strQuery = '',$binds = false, $return_object = true){
		if($strQuery == ''){
			$this->raise_error("db::query()","Query can't be an empty string !");
		}

		if($binds !== false){
			if (!is_array($binds)){
				$binds = array($binds);
			}

			foreach ($binds as $val){
				$val = "'".$this->_escape($val)."'";

				//$val = str_replace($this->bind_marker, '{%bind_marker%}', $val);
				$strQuery = preg_replace("#".preg_quote($this->bind_marker, '#')."#", str_replace('$', '\$', $val), $strQuery, 1);
			}
		}
		$this->record = parent::query($strQuery,$return_object);
		return $this->record;
	}
	
	/**
	 * Returns the insert ID number when performing database inserts.
	 * @access public
	 * @param string $strQuery
	 * @return mixed
	 */
	public function query_get_id($strQuery = '',$binds = false, $return_object = false){
		$this->query($strQuery, $binds, $return_object);
		return $this->_insert_id();
	}
		
	/**
	 * Returns a single result row from recordset
	 * Alias for mysql_record->row();
	 *
	 * @access public
	 * @param string $strQuery
	 * @return array
	 */
	public function row($strQuery = '',$binds = false){
		$this->query($strQuery, $binds, true);
		return $this->record->row();
	}
	
	/**
	 * Returns the first field from the recordset or an empty string
	 * Alias for mysql_record->first();
	 *
	 * @access public
	 * @param string $strQuery
	 * @return mixed
	 */
	public function field($strQuery = '',$binds = false, $return_object = true){
		return current($this->row($strQuery, $binds, $return_object));
	}
	
	/**
	 * move the pointer to the next row in the query result
	 * @access public
	 * @return bool false if the end is reached ,else return the current position
	 * @deprecated  use mysql_record->next() or mysql_next->next_array() instead
	 */
	public function move_next(){
		return $this->record->next();
	}
	
	/**
	 * offsets record pointer
	 *
	 * @param integer $row_number
	 * @return bool
	 * @deprecated use mysql_record->move_to() instead
	 */
	public function move_to($row_number=0){
		return $this->record->moveTo($row_number);
	}
	
	/**
	 * Alias for get_field
	 *
	 * @param string $mixFld
	 * @return mixed
	 * @access public
	 * @deprecated  use mysql_record->f() instead;
	 */
	public function f($mixFld){
		return $this->record->f($mixFld);
	}
	
	/**
	 * Return the value of the field name from the $glob variable
	 * if it is set, otherwise returns the value of the current
	 * record in the RecordSet.
	 *
	 * @param mixed $mixFld
	 * @return mixed
	 * @access public
	 * @deprecated use mysql_record->gf() instead;
	 */
	public function gf($mixFld){
		return $this->record->gf($mixFld);
	}
	
	/**
	 * the number of records from the last query
	 *
	 * @access public
	 * @return int
	 * @deprecated use mysql_record->records_count() instead;
	 */
	public function records_count(){
		return $this->record->records_count();
	}
	
	/**
     * the number of fields from the last query
     *
     * @access public
     * @return int
     * @deprecated use mysql_record->fields_count instead;
     */
	public function fields_count(){
		return $this->record->fields_count();
	}
	
}//end class