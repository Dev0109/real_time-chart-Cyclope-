<?php
class database_driver{
	/**
	 * hold the host where the server is located
	 * @var string
	 * @access private
	 */
	protected $hostname;

	/**
	 * store de username required for connection
	 * @access private
	 * @var string
	 */
	protected $username;
	
	/**
	 * password required by the username
	 * @access private
	 * @var string
	 */	
	protected $password;

	/**
	 * database to connect
	 * @access private
	 * @var string
	 */
	protected $database;

	/**
	 * Prefix each table with this value
	 *
	 * @var string
	 */
	protected $tbl_prefix = '';
	
	/**
	 * store the conection handle
	 * @access private
	 * @protected resource
	 */
	public $connection_id = 0;
	
	/**
	 * store the last query handle
	 * @access private
	 * @protected resource
	 */
	protected $result_id = 0;
	
	/**
	 * Marker used to declare a bind
	 * @access private
	 * @protected string
	 */
    protected $bind_marker = '?';
    
    /**
     * If set to true it will stop the execution of sql's onerror
     * @access private
     * @protected bool
     */
    protected $exit_on_error = true;
    
    protected $driver = 'mysql';
    
    protected $connection_index = null;
    /**
     * Force the creation of a new connection
     * Should only be used if two connections are 
     * required otherwise the default behaviour is to share the same connection
     *
     * @protected bool
     */
    protected $create_new = false;

    protected $store;
    protected $goToOffset = false;

	protected $sql_select = array();
	protected $sql_from = array();
	protected $is_distinct = false;
	protected $sql_join = array();
	protected $sql_where = array();
	protected $sql_groupby = array();
	protected $sql_orderby = array();
	protected $limit_select = false;
	protected $limit_offset = false;
    
	protected $sql_insert = '';
	protected $sql_update = '';
	protected $sql_delete = '';
	protected $sql_mode = 1;
	
    /**
     * Constructor.  Accepts one parameter containing the database
	 * connection settings.
     *
     * @param array $params
     */
    function __construct($params = array()){
    	foreach ($params as $key => $val){
    		$this->$key = $val;
    	}
    	if(!count($params)){
    		//if there are no params sent we try to get the default constants and use those.
    		$constants2properites = array('DB_HOSTNAME' => 'hostname',
    									  'DB_USERNAME' => 'username',
    									  'DB_PASSWORD' => 'password',
    									  'DB_DATABASE' => 'database',
    									  'DB_DRIVER'   => 'driver',
    									  'DB_PREFIX' => 'tbl_prefix',
    									  'DB_EXIT_ON_ERROR' => 'exit_on_error',
    									  'DB_ALWAYS_CREATE_NEW' => 'create_new');
    		foreach ($constants2properites as $constant => $property){
    			if(defined($constant)){
    				$this->$property = constant($constant);
    			}
    		}
    	}
		if(!class_exists('database_store')){
			include('database_store.php');
		}
		$this->store = database_store::getInstance();

    	//the default behaviour is to init a connection on instatation so we do that
    	$this->connect();
    }
	
    /**
     * Creates a new connection to the databse
     * @access public
     * @return bool
     */
    public function connect(){
    	if(is_resource($this->connection_id)){
    		return true;
    	}
    	//else check to see if there is a connection in the store
    	$this->connection_index = $this->hostname.'.'.$this->username.'.'.$this->database.'.'.$this->driver;
    	
    	if(!$this->create_new){
	    	if($this->store->hasConnection() == false){
				$this->connection_id = $this->_connect_db();
				$this->store->setConnection($this->connection_index, $this->connection_id);
	    	}else{
	    		$this->connection_id = $this->store->getConnection($this->connection_index);
	    	}
    	}else{
	   		//we need to force a new connection :)
			$this->connection_id = $this->_connect_db();
			$this->store->setConnection($this->connection_index, $this->connection_id);
    	}
    	
    	if($this->database != ''){
			if(!$this->_select_db()){
				$this->raise_error("db::connect()", "Failed to select given database:".$this->database);
			}
		}
		return true;
    }
	
	/**
	 * Used to close the active Connection
	 * It will return true if the connection is openned, else will return false.
	 *
	 * @access public
	 * @return bool
	 */
	function disconnect(){
		if(is_resource($this->connection_id)){
			return $this->_close();
		}
	}
	
	public function select($select = '*',$distinct = false){
		if($select === true){
			$this->is_distinct = true;
		}
		if(is_string($select)){
			$select = explode(',', $select);
		}
		if(is_array($select) && count($select)){
			foreach ($select as $val){
				$val = trim($val);
		
				if ($val != ''){
					array_push($this->sql_select,$val);
				}
			}
		}
		return $this;
	}
	
	public function from($table){
		if(is_string($table) && strlen($table)){
			array_push($this->sql_from,$this->_protect_identifiers($table,false));
		}
		return $this;
	}
	
	public function join($table = '', $condition = '',$type = 'INNER', $escape = true){
		if ($type != ''){
			$type = strtoupper(trim($type));

			if(!in_array($type, array('LEFT', 'RIGHT', 'OUTER', 'INNER', 'LEFT OUTER', 'RIGHT OUTER'))){
				$type = '';
			}else{
				$type .= ' ';
			}
		}

		// Strip apart the condition and protect the identifiers
		if (preg_match('/([\w\.]+)([\W\s]+)(.+)/', $condition, $match)){
			$match[1] = $escape ? $this->_protect_identifiers($match[1]) : $match[1];
			$match[3] = $escape ? $this->_protect_identifiers($match[3]) : $match[3];
		
			$condition = $match[1].$match[2].$match[3];
		}
		
		// Assemble the JOIN statement
		$join = $type.'JOIN '.$this->_protect_identifiers($table, false).' ON '.$condition;
		array_push($this->sql_join,$join);
		return $this;
	}
	
	public function where($key, $value = null,$escape = true){
		return $this->_where($key, $value, 'AND ', $escape);
	}

	public function where_and($key,$value = null,$escape = true){
		return $this->_where($key, $value, 'AND ', $escape);
	}
	
	public function where_or($key,$value = null,$escape = true){
		return $this->_where($key, $value, 'OR ', $escape);
	}
	
	public function between($field, $min, $max, $escape = true){
		$expr = $this->_protect_identifiers($field).' BETWEEN ';
		if($escape){
			$expr.= $this->escape($min).' AND '.$this->escape($max);
		}else{
			$expr.= $min.' AND '.$max;
		}
		array_push($this->sql_where,$expr);
		return $this;
	}
	
	private function _where($key,$value = null,$type = '', $escape = true){
		if(!is_array($key)){
			$key = array($key => $value);
		}
		$prefix = (count($this->sql_where)==0) ? '' : $type;
		$count = 0;
		foreach ($key as $k => $v){
			if($count > 0){
				$prefix = $type;
			}
			
			$k = $this->_protect_identifiers($k);
			if(!is_null($v)){
				$v = $escape === true ? $this->escape($v) : $v;				
			}
			if(!$this->_has_operator($k)){
				$k .= ' = ';
			}
			array_push($this->sql_where,$prefix.$k.$v);
			$count++;
		}
		return $this;
	}
	
	public function __call($name, $arguments){
		 if(in_array(strtolower($name),array('and','or'))){
	 	 	$methodToCall = "where_".$name;
	 	 	if(count($arguments) == 2){
	 	 		return $this->$methodToCall($arguments[0],$arguments[1]);
	 	 	}
	 	 	return $this->$methodToCall($arguments[0]);
	 	 }
	 	 trigger_error("Call to undefined method ".__CLASS__."::".$name."()",E_USER_ERROR);
	 }
	
	public function group_by($by = ''){
		if (is_string($by)){
			$by = explode(',', $by);
		}
	
		foreach ($by as $val){
			$val = trim($val);
		
			if($val != ''){
				array_push($this->sql_groupby,$this->_protect_identifiers($val));
			}
		}
		return $this;
	}
	
	public function order_by($by = ''){
		if (is_string($by)){
			$by = explode(',', $by);
		}
	
		foreach ($by as $val){
			$val = trim($val);
		
			if($val != ''){
				array_push($this->sql_orderby,$this->_protect_identifiers($val));
			}
		}
		return $this;
	}
	
	public function limit($value,$offset = false){
		if(is_numeric($value)){
			$this->limit_select = $value;
		}
		$this->limit_offset = $offset;
		return $this;
	}
	
	public function get($table = '',$offset = null){
		
		if(strlen($table)){
			$this->from($table);
		}
		if(is_numeric($table)){
			$offset = $table;
		}
		$this->goToOffset = $offset;
		
		//we use this to make sure we can use the offset part of it as pagination in oracle
		$this->_prepare_query();
		$sql = $this->_buildQueryString();
		
		$result = $this->query($sql);
		
		$this->_reset_select();
		return $result;
	}
	
	public function escape($str){
		switch(gettype($str)){
			case 'string':
				$str = "'".$this->_escape($str)."'";
				break;
			case 'boolean':
				$str = ($str === FALSE) ? 0 : 1;
				break;
			default:
				$str = ($str === NULL) ? 'NULL' : $str;
				break;
		}
		return $str;
	}
	
	public function insert_into($table){
		if(strlen($table)){
			$this->sql_insert = $this->_protect_identifiers($table,false);
			$this->sql_mode = 1;
		}
		return $this;
	}

	public function insert($table){
		return $this->insert_into($table);
	}
	
	public function update($table){
		if(strlen($table)){
			$this->sql_update = $this->_protect_identifiers($table,false);;
			$this->sql_mode = 2;
		}
		return $this;
	}
	
	public function delete_from($table){
		if(strlen($table)){
			$this->sql_delete = $this->_protect_identifiers($table,false);;
			$this->sql_mode = 3;
		}
		return $this;
	}
	
	public function set($key = array(), $value = null, $escape = true){
		if(!is_array($key)){
			$key = array($key => $value);
		}
		
		$sql = $this->_buildWriteString($key, $escape);
		$result = $this->query($sql);
		$this->_reset_write();	
		return $result;
	}
	
	public function explain($strQuery){
		if($this->is_write_type($strQuery)){
			return false;
		}
		if(strstr($strQuery,'SELECT') === false){
			return false;
		}
		
		return $this->query('EXPLAIN '.$strQuery);
	}
	
	private function _reset_select(){
		$defaults = array(
			'sql_select' => array(),
			'sql_from' => array(),
			'is_distinct' => false,
			'sql_join' => array(),
			'sql_where' => array(),
			'sql_groupby' => array(),
			'sql_orderby' => array(),
			'limit_select' => false,
			'limit_offset' => false
		);
		foreach ($defaults as $key=> $def){
			$this->$key = $def;
		}		
	}
	
	private function _reset_write(){
		$defaults = array(
				'sql_insert' => '',
				'sql_update' => '',
				'sql_delete' => '',
				'sql_where' => array(),
				'sql_mode' => 1,
		);
		foreach ($defaults as $key=> $def){
			$this->$key = $def;
		}		
	}
	
	/**
	 * The query() function returns a database result object when "read" type queries are run,
	 * which you can use to show your results. When "write" type queries are run it simply returns
	 * TRUE or FALSE depending on success or failure.
	 *
	 * @param string $strQuery
	 * @param bool $return_object
	 * @return mixed
	 */
	public function query($strQuery = '', $return_object = true){
		if($strQuery == ''){
			$this->raise_error("db::query()","Query can't be an empty string !");
		}

		if(!is_resource($this->connection_id)){
			$this->connect();
		}
		// Start the Query Timer
		if(DEBUG_CONTEXT){
			$start = $this->getTime();
		}
		$this->result_id = $this->_db_query($strQuery);
		
		if(DEBUG_CONTEXT){
			$this->logQuery($strQuery, $start);
		}
		
		if(!$this->result_id){
			$this->raise_error("db::query()","Failed to run Query:".$strQuery.$this->_error(),$this->_errno());
		}

		if($this->is_write_type($strQuery) === true){
			//we need to figure out which on it is and return the correct anwser :)
			if($this->sql_mode == 1){//insert
				return $this->_insert_id();
			}
			return $this->_affected_rows();
		}
		if($return_object !== true){
			return $this->result_id;
		}
		if(!class_exists('database_record')){
			require_once(INSTALLPATH.'database/database_record.php');
		}
		if(!class_exists($this->driver.'_record')){
			require_once(INSTALLPATH.'database/driver/'.$this->driver.'/'.$this->driver.'_record.php');
		}
		$recordClass = $this->driver.'_record';
		$RES = new $recordClass($this->connection_id,$this->result_id,$this->goToOffset);
		return $RES;
	}
	
	protected function logQuery($sql, $start) {
		$query = array(
				'sql' => $sql,
				'time' => ($this->getTime() - $start)*1000
			);
		$query['readableTime'] = $this->getReadableTime($query['time']);
		$this->store->push($query);
	}
	
	protected function getTime() {
		$time = microtime();
		$time = explode(' ', $time);
		$time = $time[1] + $time[0];
		$start = $time;
		return $start;
	}
	
	protected function getReadableTime($time) {
		$ret = $time;
		$formatter = 0;
		$formats = array('ms', 's', 'm');
		if($time >= 1000 && $time < 60000) {
			$formatter = 1;
			$ret = ($time / 1000);
		}
		if($time >= 60000) {
			$formatter = 2;
			$ret = ($time / 1000) / 60;
		}
		$ret = number_format($ret,3,'.','') . ' ' . $formats[$formatter];
		return $ret;
	}
	
	private function _buildQueryString(){
		
		$sql = ( ! $this->is_distinct) ? 'SELECT ' : 'SELECT DISTINCT ';
	
		if(count($this->sql_select) == 0){
			$sql .= '*';		
		}else{				
			foreach ($this->sql_select as $key => $val){
				$this->sql_select[$key] = $this->_protect_identifiers($val);
			}
			
			$sql .= implode(', ', $this->sql_select);
		}

		// ----------------------------------------------------------------
		
		// Write the "FROM" portion of the query

		if(count($this->sql_from) > 0){
			$sql .= "\nFROM ";
			$sql .= join(',',$this->sql_from);
		}

		// ----------------------------------------------------------------
		
		// Write the "JOIN" portion of the query

		if(count($this->sql_join) > 0){
			$sql .= "\n";

			$sql .= implode("\n", $this->sql_join);
		}

		// ----------------------------------------------------------------
		
		// Write the "WHERE" portion of the query

		if(count($this->sql_where) > 0){
			$sql .= "\n";
			$sql .= "WHERE ";
		}

		$sql .= implode("\n", $this->sql_where);

		// ----------------------------------------------------------------
		
		// Write the "GROUP BY" portion of the query
	
		if(count($this->sql_groupby) > 0){
			$sql .= "\nGROUP BY ";
			$sql .= implode(', ', $this->sql_groupby);
		}

		// ----------------------------------------------------------------
		
		// Write the "ORDER BY" portion of the query

		if (count($this->sql_orderby) > 0){
			$sql .= "\nORDER BY ";
			$sql .= implode(', ', $this->sql_orderby);
		}
		
		if (is_numeric($this->limit_select)){
			$sql .= "\n";
			$sql = $this->_limit($sql, $this->limit_select, $this->limit_offset);
		}

		return $sql;
	}	

	private function _buildWriteString($key, $escape = true){
		$sql = '';
		switch ($this->sql_mode){
			case 1://INSERT
				//the insert part of it
				$sql = 'INSERT INTO '.$this->sql_insert;//it already has the qoutes in it
				$field = array();
				$value = array();
				foreach ((array)$key as $k => $v){
					array_push($field,$this->_protect_identifiers($k));
					if(!$escape){
						array_push($value,$v);
						continue;
					}
					array_push($value,$this->escape($v));
				}
				$sql .= '('.join(',',$field).') VALUES ('.join(',',$value).')';
				break;
			case 2://UPDATE
				//the insert part of it
				$sql = 'UPDATE '.$this->sql_update;//it already has the qoutes in it
				$value = array();
				foreach ((array)$key as $k => $v){
					array_push($value,$this->_protect_identifiers($k).' = '.($escape ? $this->escape($v) : $v));
				}
				$sql .= ' SET '.join(",\n",$value);
				//add the where part of it
				if(count($this->sql_where) > 0){
					$sql .= "\n";
					$sql .= "WHERE ";
				}
				$sql .= implode("\n", $this->sql_where);
				break;
			case 3://DELETE
				$sql = 'DELETE FROM '.$this->sql_delete;
				//add the where part of it
				if(count($this->sql_where) > 0){
					$sql .= "\n";
					$sql .= "WHERE ";
				}else{
					$sql .= "\n";
					$sql .= "WHERE ";
					array_push($this->sql_where,'1=1');
				}
				$sql .= implode("\n", $this->sql_where);
				break;
		}
		return $sql;
	}
	
	private function _has_operator($str){
		$str = trim($str);
		if(!preg_match("/(\s|<|>|!|=|is null|is not null)/i", $str)){
			return false;
		}
		return true;
	}
	
	private function _protect_identifiers($item = '',$has_field = true){
		// Convert tabs or multiple spaces into single spaces
		$item = preg_replace('/[\t| ]+/', ' ', trim($item));
			
		$opening_brace = strpos($item,'(');
		if($opening_brace !== false){
			//we need to find the first ( and the last ) and then call recursivly handle that
			$clossing_brace = strrpos($item,')');
			$use_closing_brace = true;
			if($clossing_brace === false){
				$clossing_brace = strlen($item);
				$use_closing_brace = false;
			}
			$before_item = substr($item,0,$opening_brace);
			$after_item = substr($item,$clossing_brace+1);
			$item = substr($item,$opening_brace+1,$clossing_brace-$opening_brace-1);
			$item = $this->_protect_identifiers($item,$has_field);
			return $before_item.'('.$item.( $use_closing_brace ? ')'.$after_item : '');
		}
		//we check to see if we only have a closing paranthesis..if yes this is the second argument of a funciton so we just return
		//it as is
		if(strpos($item,')') !== false){
			return $item;
		}
		
		$alias = '';
		if(strpos($item, ' ') !== false){		
			$alias = strstr($item, " ");
			$item = substr($item, 0, - strlen($alias));
		}
		
		
		
		
		if(strpos($item, '.') !== false){
			//we need to explode it and protect the right identifier
			$pieces = explode('.',$item);
			if($this->tbl_prefix){
				if($has_field){
					//move the cursor abit
					$position = count($pieces)-2;
					if(substr($pieces[$position], 0, strlen($this->tbl_prefix)) != $this->tbl_prefix){		
						$pieces[$position] = $this->tbl_prefix.$pieces[$position];
					}
				}else{
					//no field so it's the last one
					$position = count($pieces)-1;
					if(substr($pieces[$position], 0, strlen($this->tbl_prefix)) != $this->tbl_prefix){		
						$pieces[$position] = $this->tbl_prefix.$pieces[$position];
					}
				}
			}
			foreach ($pieces as $index => $piece){
				if($piece == '*'){
					continue;
				}
				$pieces[$index] = $this->_escape_identifier($piece);
			}
			return join('.',$pieces).$alias;
		}
		if(!$has_field){
			if ($this->tbl_prefix != ''){
				if(substr($item, 0, strlen($this->tbl_prefix)) != $this->tbl_prefix){		
					$item = $this->tbl_prefix.$item;
				}
			}
		}
		return $this->_escape_identifier($item).$alias;
	}

   	/**
	 * Determines if a query is a "write" type.
	 *
	 * @access	private
	 * @param	string	An SQL query string
	 * @return	boolean
	 */
	private function is_write_type($sql){
		if(!preg_match('/^\s*"?(INSERT|UPDATE|DELETE|REPLACE|CREATE|DROP|LOAD DATA|COPY|ALTER|GRANT|REVOKE|LOCK|UNLOCK)\s+/i', $sql)){
			return false;
		}
		return true;
	}
	
	function get_table_name($error_message){
		$db = new mysql_db();
		$tables = $this->query('show tables');
		$repair_tables = '';
		$i=0;
		while($tables->next()){
			$table = $tables->f('Tables_in_'.MYSQL_DB_NAME);
			if(strstr($error_message, $table)){
				if($i==0){
					$repair_tables=$table;
				} else {
					$repair_tables=$repair_tables.', '.$table;
				}
				$i++;
			}
		} 
		if($repair_tables != ''){
			return $repair_tables;
		} else {
			return false;
		}
	}
	
	protected function raise_error($f,$errMsg,$errNo=0){
		$matches = array(); 

		if(!DEBUG_CONTEXT  && $errNo != 1064){
			if($tbl = $this->get_table_name($errMsg)){
				if(!file_exists(CURRENT_VERSION_FOLDER.'logs/repair_')){
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/repair_',"REPAIR TABLE ".$tbl." ".time());
					file_put_contents(CURRENT_VERSION_FOLDER.'logs/log_repair',"
REPAIR TABLE ".$tbl." ".date('n d y'), FILE_APPEND);
					$this->query('REPAIR TABLE '.$tbl);
					unlink(CURRENT_VERSION_FOLDER.'logs/repair_');
				}
			}
		}  
		
		if(!DEBUG_CONTEXT){
			global $site_url;
			$glob = array('lastpage' => $site_url.'?'.$_SERVER['QUERY_STRING'],
						  'file' => $this->writeErrorFile($f,"Error Code".$errNo.":".$errMsg));
			include_once(CURRENT_VERSION_FOLDER.'php/pag/error.php');
			exit();
		}else{		
			@ob_start();
			echo "<center><table border=0 cellpadding=2 cellspacing=2>";
			echo "<tr><td><font color=\"red\" face=\"Times New Roman\"> ";
			echo "Error ".$errNo.":".$f." failed.ERROR MESSAGE IS: ".$errMsg;
			echo "</font></td></tr></center>";
			$data = @ob_get_contents();
			@ob_end_clean();
			
			if($this->exit_on_error){
				echo $data;
				exit();
			}
		}
		return false;
	}
	
	private function writeErrorFile($f, $errMsg){
		$backtrace = debug_backtrace();
		//remove some cruft
		array_shift($backtrace);
		array_shift($backtrace);
		array_shift($backtrace);
		array_shift($backtrace);
		$errorInfo = array(
			'f' => $f,
			'errMsg' => $errMsg,
			'backtrace' => $backtrace
		);
		$name = 'log_'.time().'.log';
		// file_put_contents(CURRENT_VERSION_FOLDER.'logs/'.$name,gzcompress(base64_encode(serialize($errorInfo))));
		file_put_contents(CURRENT_VERSION_FOLDER.'logs/'.$name,$errorInfo);
		return $name;
	}
}

interface Paginate{
	
	public function getCurrentPage();
	public function getRowPerPage();
	public function setRowPerPage($rowPerPage = 30);
	public function getTotalRecords();
}