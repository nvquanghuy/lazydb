<?php
/**
 * LazyDB class
 * A simple database connector for lazy programmers. 
 * 
 * 
 * @author    huy   (nvquanghuy.com)
 * @lastedit  Sep 24, 2011
 * 
 * This class only support PHP5
 * - If you want to make it support PHP4, change the constructor
 *  from __constructor to LazyDB()
 * 
 * There are 3 state of database connection:
 * 1 - hasn't connected
 * 2 - connected, not select database
 * 3 - connected and database selected
 * 
 * NOTES:
 * - Create a folder logs and make it writeable. This folder will store all
 *  error logs that the system caught.
 * - Please make sure magic quotes is turned off. 
 * 
 * CHANGES LOG:
 * * 1.01
 * - Add query_select_single
 * - Add insert_batch
 * * 1.2
 * - Add support for slashing, on/off thru $this->handleSlashes (bool)
 * * 26/12/2010:
 * - Add $sql_suffix_options to insert_batch
 *
 * Sep 24, 2011:
 * > Added query_select_manualkey
 * > Added query_row, make it replace query_single
 * > Added query_col, make it replace query_select_single
 *
 * Sep 27, 2011
 * > Added createExpression()
 * > Auto-serializing array variable 
 */

define('CRLF', "\r\n");
error_reporting(E_ALL);

class LazyDB {
	/** Relative path to error logs folder
	 * When found a database error, it will store the log file to this folder
	 */
	public $LOGS_PATH = "./errorlogs";

	/** Server information */
	public $HOST;
	public $USERNAME;
	public $PASSWORD;
	public $DATABASE;
	
	/** Slashes handling, default is TRUE */
	public $handleSlashes = TRUE;
	
	/** Connection link */
	private $conn = "";
	
	/** Last database link has been made */
	private $last_link = "";
	
	/** Last query made, for error logging purpose */
	private $last_query = "";
	
	/**
	 * When you want to insert an expression to the query, to avoid LazyDB
	 * auto wrapping quotation to the string, use this function.
	 * 
	 * E.g.: 
	 * 
	 * $student = array(
   *    'name'    => "Johny",
   *    'created' => LazyDB::createExpression('NOW()')
   * );
   * $db->insert("students", $student);
	 * 
	 */
	public static function createExpression($sql_expression) {
	  return new LazyDBExpressionType($sql_expression);
	}
	
	public function __construct($host = "", $user = "", $pw = "", $db = "") {
		$this->HOST = $host;
		$this->USERNAME = $user;
		$this->PASSWORD = $pw;
		$this->DATABASE = $db;
		$this->connect ();
	}
	
	/**
	 * Connect to database.
	 * Also try to select database if possible.
	 * 
	 */
	public function connect() {
		$this->conn = mysql_connect ( $this->HOST, $this->USERNAME, $this->PASSWORD, /* new_link */ true );
		if (!$this->conn) {
			die ( 'Error connecting to mysql server.' );
		}
		
		if ($this->DATABASE) {
			$this->select_db();
		}
		
		return $this->conn;
	}
	
	/**
	 * Change current database.
	 * Assumption: connection already made. 
	 *
	 */
	public function select_db($database = "") {
		if ($database == "") {
			$database = $this->DATABASE;
		}
		
		if ($database != "") {
			if (! @mysql_select_db ( $database, $this->conn )) {
				$this->log_error ( @mysql_error () );
				die ( 'Error selecting database ' . $database );
			} else {
				// Successful
			}
		}
	}
	
	/**
	 * Return a list of databases.
	 * Assumption: connection already made.
	 * 
	 * @return array
	 */
	public function get_databases() {
		$dbs = $this->query_select ( "SHOW DATABASES;" );
		if (! function_exists ( '__tmp_huy_db_databases' )) {
			function __tmp_huy_db_databases($val) {
				return $val ['Database'];
			}
		}
		return array_map ( '__tmp_huy_db_databases', $dbs );
	}
	
	/**
	 * Get list of tables available in current database.
	 *
	 * @return array
	 */
	public function get_tables() {
		$tables = $this->query_select ( "SHOW TABLES;", "num" );
		if (! function_exists ( '__tmp_huy_db_tables' )) {
			function __tmp_huy_db_tables($val) {
				return $val [0];
			}
		}
		return array_map ( '__tmp_huy_db_tables', $tables );
	}
	
	/**
	 * General function, do a query
	 * 
	 */
	public function query( $sql ) {
		if (! $this->conn) {
			$this->connect ();
		}
		
		$this->last_link = mysql_query ( $sql, $this->conn );
		$this->last_query = $sql;
		
		if (! $this->last_link) {
			$this->log_error ( @mysql_error () );
			print "<span style='color: red'>SQL error occurred. Log written.</span>";
		}
		
		return $this->last_link;
	}
	


	/**
	 * Do a SELECT query and return the resultset. 
	 * 
	 * @param mixed $sql sql string
	 */
	public function query_select($sql, $type = "assoc") {
		$this->query ( $sql );
		$ans = array ();
		while ( $r = $this->fetch_array ( $this->last_link, $type ) ) {
			$ans [] = $r;
		}
		$this->free_result ();
		
		return $ans;
	}

	/*
	 * Same as query_select but returns an array with keys as specified by the 
	 * parameter $manualkey (must be a valid column from the database)
	 *
	 * @param 
	 * @return 
	 */
	public function query_select_manualkey($sql, $manualkey, $type = "assoc") {
		$this->query ( $sql );
		$ans = array ();
		while ( $r = $this->fetch_array ( $this->last_link, $type ) ) {
			$ans [$r[$manualkey]] = $r;
		}
		$this->free_result();
		
		return $ans;
	}
	
	/**
	 * Return only the first row
	 * 
	 * @deprecated
	 * @see query_row
	 * @param mixed $sql
	 * @param $type either 'assoc' or 'num' or 'both'. Default is 'assoc' 
	 */
	public function query_single($sql, $type = "assoc") {
		$this->query ( $sql );
		if ($r = $this->fetch_array ( $this->last_link, $type )) {
			$this->free_result ();
			return $r;
		}
		return FALSE;
	}
	
	/**
	 * Return only the first row of a SELECT statement
	 */
  public function query_row($sql, $type = "assoc") {
		return $this->query_single($sql, $type);
	}
	
	/**
	 * Query single result.
	 * Usually used when doing SELECT COUNT.
	 * 
	 * @param mixed $sql
	 * @param $type either 'assoc' or 'num' or 'both'. Default is 'assoc'
	 * 
	 * @return FALSE if NO RECORD
	 */
	public function query_scalar($sql) {
		$this->query ( $sql );
		if ($ans = $this->fetch_array ( $this->last_link, "num" )) {
			$this->free_result ();
			return $ans [0];
		}
		return FALSE;
	}
	
	/**
	 * Query and return array of 1 element each
	 *
	 * @deprecated
	 * @see query_col
	 * For eg: SELECT userid FROM users, will return array(uid,uid,..)
	 */
	public function query_select_single($sql) {
		$tmp = $this->query_select ( $sql, "num" );
		$ans = array ();
		foreach ( $tmp as $item ) {
			$ans [] = $item [0];
		}
		return $ans;
	}
	
	/**
	 * Query and return array of 1 element each
	 * To replace query_select_single
	 */
	public function query_col($sql) {
	  return $this->query_select_single( $sql );
	}

	/**
	 * Fetch row by row (ASSOC)
	 * Will return array with fieldname as key
	 * 
	 * @param $type either 'assoc' or 'num' or 'both'. Default is 'assoc'
	 */
	private function fetch_array($query_id = "", $type = "assoc") {
		if ($query_id == "") {
			$query_id = $this->last_link;
		}
		if ($type == "assoc") {
			$const = MYSQL_ASSOC;
		} elseif ($type == "num") {
			$const = MYSQL_NUM;
		} else {
			$const = MYSQL_BOTH;
		}
		$row = mysql_fetch_array ( $query_id, $const );
		return $row;
	}
	
	/**
	 * Fetch row by row (NUM)
	 * Will return array with index as key
	 * 
	 */
	public function fetch_num($query_id = "") {
		return $this->fetch_array ( $query_id, "num" );
	}
	
	/**
	 * Fetch row by row (ASSOC)
	 * Will return array with index as key
	 * 
	 */
	public function fetch_row($query_id = "") {
		return $this->fetch_array ( $query_id, "assoc" );
	}
	
	/**
	 * Get number of rows in resultset
	 * 
	 */
	public function get_num_rows($query_id = "") {
		if ($query_id == "") {
			$query_id = $this->last_link;
		}
		return mysql_num_rows ( $query_id );
	}
	
	/**
	 * Get number of affected rows
	 * 
	 */
	public function get_affected_rows() {
		return mysql_affected_rows ();
	}
	
	/**
	 * Generate and execute an UPDATE statement
	 * 
	 * @param mixed $table_name
	 * @param mixed $updates
	 * @param mixed $where
	 */
	public function update($table, $data = array(), $where = "") {
		$statements = array ();
		foreach ( $data as $key => $value ) {
			$statements [] = '`' . $key . '` = ' . $this->get_field_value ( $value );
		}
		$statements_str = implode ( ",", $statements );
		if ($where != "") {
			$where = " WHERE " . $where;
		}
		$query = "UPDATE `$table` SET $statements_str $where";
		$this->query ( $query );
	}
	
	/**
	 * Build and execute an insert query
	 * 
	 * @param mixed $table
	 * @param mixed $data
	 */
	public function insert($table, $data = array()) {
		$fields = array ();
		$values = array ();
	
		foreach ( $data as $key => $value ) {
		  $fields [] = $key;
			$values [] = $this->get_field_value ( $value );
		}
		
		$fields_str = implode ( "`,`", $fields );
		$values_str = implode ( ",", $values );
		
		$query = "INSERT INTO `$table` (`$fields_str`) VALUES ($values_str)";
		
		$this->query ( $query );
		
		return $this->get_insert_id ();
	}
	
	/**
	 * Build and execute a batch-INSERT statement
	 * 
	 * @param $entries
	 * @return 1 if success, 0 if fail.
	 *   
	 */
	public function insert_batch($table, $entries, $extra_suffix_sql = "") {
		if (sizeof ( $entries ) == 0) {
			return - 1;
		}
		
		$fields = array ();
		$values = array (); // overall values
		

		/** First build the field names */
		$idx = 0;
		foreach ( $entries [0] as $key => $value ) {
			$fields [] = $key;
		}
		
		/** Now loop thru the entries, build the VALUES string. */
		foreach ( $entries as $entry ) {
			$entry_values = array ();
			$sanity_idx = 0; // for sanity check
			foreach ( $entry as $key => $value ) {
				if ($fields [$sanity_idx ++] != $key) {
					die ( 'Bad input data in $db->insert_batch.' );
				}
				$entry_values [] = $this->get_field_value ( $value );
			}
			$values [] = '(' . implode ( ',', $entry_values ) . ')';
		}
		
		$fields_str = implode ( "`,`", $fields );
		$values_str = implode ( ",", $values );
		
		$query = "INSERT INTO `$table` (`$fields_str`) VALUES $values_str $extra_suffix_sql";
		$this->query ( $query );
		
		return 1;
	}
	
	/**
	 * release resource
	 * 
	 * @param mixed $query_id
	 */
	public function free_result($query_id = "") {
		if ($query_id == "") {
			$query_id = $this->last_link;
		}
		@mysql_free_result ( $query_id );
	}
	
	/**
	 * Get lastest AUTO_INCREMENT id
	 *
	 * @return int
	 */
	public function get_insert_id() {
		return mysql_insert_id ( $this->conn );
	}
	
	/**
	 * Close the connection
	 */
	public function close() {
		if (! $this->conn) {
			die ( 'Connection not established. How to close?' );
		}
		mysql_close ( $this->conn );
	}
	
	/*#############################################
	 * PRIVATE FUNCTIONS
	 *############################################/
	
	/**
	 * Return MYSQL value string.
	 */
	private function get_field_value($value) {
	  if ($value instanceof LazyDBExpressionType) {
	    return $value->getExpression();
	  } else if (is_array ( $value )) {
			return "'" . $this->add_slashes( serialize($value) ) . "'";
		} else {
			return "'" . $this->add_slashes ( $value ) . "'";
		}
	}
	
	/**
	 * Log down errors found in the folder logs
	 */
	private function log_error($error) {
		$CRLF = "\r\n";
		
		$time = time ();
		
		// eg: 2009_01_13_GMT_08_dberr.log
		$filename = date ( "Y_m_d", $time ) . '_GMT_' . $this->gmt_timezone () . '_dberr.log';
		$h = @fopen ( $this->LOGS_PATH . '/' . $filename, "a" );
		if (! $h) {
			// Cannot create/open file, or permission denied.
			print '<span style="color: red; font-weight: bold; font-size: 15px;">Database error. Log cannot be written.</span>';
			return;
		}
		fwrite ( $h, date ( 'H:i:s', $time ) . " GMT " . $this->gmt_timezone () . CRLF );
		fwrite ( $h, $error . CRLF );
		fwrite ( $h, $this->last_query . CRLF );
		fwrite ( $h, CRLF . CRLF );
		
		fclose ( $h );
	}
	
	/**
	 * Before insert to database add the slashes.
	 */
	private function add_slashes($data) {
		return addslashes ( $data );
	}

	/**
	 * Return current GMT unix timestamp
	 * @return unix timestamp
	 */
	function gmt_time() {
		$now = time ();
		$tz = $this->gmt_timezone ();
		$seconds = 3600 * $tz;
		return $now - $seconds;
	}
	
	/**
	 * Return server timezone
	 * @return int
	 */
	function gmt_timezone() {
		$tz = substr ( date ( "O", time () ), 1, 2 );
		return $tz;
	}
}

class LazyDBExpressionType {
  protected $exp = null;
  
  public function getExpression() {
    return $this->exp;
  }
  
  public function __construct($exp) {
    $this->exp = $exp;
  }
  
  public function __toString() {
    return $this->exp;
  }
  
}
