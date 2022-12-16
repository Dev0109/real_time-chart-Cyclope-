<?php
/*
* A Lighter version of the mysql 
*/

class mysql_light
{

	var $host=MYSQL_DB_HOST;   //hold the host where the MySql server is located
	var $user=MYSQL_DB_USER;        //store de user name required for connection
	var $pass=MYSQL_DB_PASS;            //password required by the username
	var $database=MYSQL_DB_NAME;        // database to connect

	var $conHwnd=0;      //store the conection handle
	var $qHwnd=0;        //store the last query handle
	var $record=array(); //store a record readed from database;
	var $row=0;          //store the last row from record

	var $isTrans=false;  ////we know if we are in a tranzaction

	/*
	Public function ,constructor of the class
	@parameter $strCon -connection string
	ie: "host=sheep port=5432 dbname=mary user=lamb password=baaaa"
	if not provided default will be used: Host:localhost;Port:5432 ;User:postgres;
	Password:postgres;Database:""
	@return db class object
	*/
	function __construct($strHost="",$strUser="",$strPass="",$strDatabase="")
	{
		
		if(($strHost) && ($strUser) && ($strDatabase))
		{
			$this->host=$strHost;
			$this->user=$strUser;
			$this->pass=$strPass;
			$this->database=$strDatabase;
		}


		$this->con_open();
	}

	/*
	Private function conOpen()
	Open a connection to Postgres Sql server.
	@return : true if success else false
	*/
	function con_open()
	{
		if($this->conHwnd)
		{
//			if(!mysql_select_db($this->database,$this->conHwnd))
			if(!mysqli_select_db($this->conHwnd, $this->database))
			{
//				$this->raise_error("db::conOpen()", "Failed to select given database:".$this->database." ! ".mysql_error());
				$this->raise_error("db::conOpen()", "Failed to select given database:".$this->database." ! ".mysqli_error($this->conHwnd));
			}
			return true;
		}

//		$this->conHwnd = mysql_connect($this->host,$this->user,$this->pass) ;
		$this->conHwnd = mysqli_connect($this->host,$this->user,$this->pass) ;
		if(!$this->conHwnd)
		{
//			$this->raise_error("db::conOpen()", "Failed to connect to MySql server ! ".mysql_error());
			$this->raise_error("db::conOpen()", "Failed to connect to MySql server ! ".mysqli_error($this->conHwnd));
		}

//		if(!mysql_select_db($this->database,$this->conHwnd))
		if(!mysqli_select_db($this->conHwnd, $this->database))
		{
//			$this->raise_error("db::conOpen()", "Failed to select given database:".$this->database." ! ".mysql_error());
			$this->raise_error("db::conOpen()", "Failed to select given database:".$this->database." ! ".mysqli_error($this->conHwnd));
		}
		return true;
	}

	/*
	Public function conClose()
	used to close the active Connection
	Must return true if the connection is openned.Else will return false.
	This will occur only when the connection with server is lost from some
	reason.
	*/
	function con_close()
	{
		if($this->conHwnd)
		{
//			return mysql_close($this->conHwnd);
			return mysqli_close($this->conHwnd);
		}
	}
	/*
	Public function query()
	@parameter $strQuery -any valid Postgres Internal command
	@return query handle ;
	*/
	function query($strQuery)
	{
		//echo ($strQuery);
		if(!$strQuery)
		{
			$this->raise_error("db::query()","Query can't be an empty string !");
		}

		$this->con_open();

		//if($this->qHwnd)
		//    {
		//     $this->query_close();
		//  }

//		$this->qHwnd = mysql_query($strQuery, $this->conHwnd);
		$this->qHwnd = mysqli_query($this->conHwnd, $strQuery);

		if(!$this->qHwnd)
		{
			//print_dbg_msgs();
//			$this->raise_error("db::query()","Failed to run Query:$strQuery ".mysql_error());
			$this->raise_error("db::query()","Failed to run Query:$strQuery ".mysqli_error($this->conHwnd));
		}
		$this->row = 0;
		return $this->qHwnd;
	}
	/*
	Public Function moveNext()
	move the pointer to the next row in the query result
	@return false if the end is reached ,else return the
	current position
	*/
	function move_next()
	{
//		$this->record = @mysql_fetch_array($this->qHwnd);
		$this->record = @mysqli_fetch_array($this->qHwnd);
		if(is_array($this->record))
		{
			$this->row++;
			return $this->row;
		}
		else
		{
			return false;
		}
	}

	/*
	public function getField() -get value of the specified field
	short variant f()   -shorter variant of getField()
	@parameter $mixFld -The name of the wanted field (ie:'Name') or the
	numeric position (started from 0) based on the query (ie: 2)
	*/
	function get_field($mixFld)
	{
		//echo urldecode($this->record[$mixFld]);
		//return htmlspecialchars($this->record[$mixFld]);
		return ($this->record[$mixFld]);
	}

	function f($mixFld)
	{
		return $this->get_field($mixFld);
	}

	function query_close()
	{
//		@mysql_free_result($this->qHwnd);
		@mysqli_free_result($this->qHwnd);
	}

	/* Private Function
	Called wen need to display an error message inside of class
	*/
	function raise_error($f,$errMsg)
	{
		if ($this->isTrans)
		{
//			mysql_query("ROLLBACK");
			mysqli_query($this->qHwnd, "ROLLBACK");
//			mysql_query("set AUTOCOMMIT=1");
			mysqli_query($this->qHwnd, "set AUTOCOMMIT=1");
		}
		echo "<center><table border=0 cellpadding=2 cellspacing=2>";
		echo "<tr><td><font color=\"red\" face=\"Times New Roman\"> ";
		echo "Error:".$f." failed.ERROR MESSAGE IS: ".$errMsg;
		echo "</font></td></tr></center>";
		die("Process Halted!");
	}
	//end class here
}
 ?>