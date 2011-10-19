<?php
/**
 * Class DB
 *
 * Simple independent query builder class
 * uses PDO
 * generates SQL that confronts MySQL and SQLite syntax
 * (hopefully supports the others too)
 *
 * @author Captain_kurO <me@captainkuro.com>
 * @copyright Copyright (c) 2011, Captain_kurO
 */

if (!function_exists('is_assoc')) {
	function is_assoc($array) {
	  return is_array($array) && (bool)count(array_filter(array_keys($array), 'is_string'));
	}
}

class DB {
	// Constants of operator
	const OP_NOT     = 'NOT';
	const OP_OR      = "OR";
	const OP_OR_NOT	 = "OR NOT";
	const OP_AND     = "AND";
	const OP_AND_NOT = "AND NOT";
	// Constants of join
	const JOIN       = "JOIN";
	const JOIN_INNER = "INNER JOIN";
	const JOIN_LEFT  = "LEFT JOIN";
	const JOIN_RIGHT = "RIGHT JOIN";
	const JOIN_OUTER       = "OUTER JOIN";
	const JOIN_LEFT_OUTER  = "LEFT OUTER JOIN";
	const JOIN_RIGHT_OUTER = "RIGHT OUTER JOIN";
	const JOIN_NATURAL             = "NATURAL JOIN";
	const JOIN_NATURAL_LEFT        = "NATURAL LEFT JOIN";
	const JOIN_NATURAL_RIGHT       = "NATURAL RIGHT JOIN";
	const JOIN_NATURAL_LEFT_OUTER  = "NATURAL LEFT OUTER JOIN";
	const JOIN_NATURAL_RIGHT_OUTER = "NATURAL RIGHT OUTER JOIN";
	
	// Attributes:
	protected $_conn = null; // the connection object
	protected $_driver = ''; // the database driver
	protected $_last = "";   // last executed query
	protected $_query_only = false; // if true then no database connection made
	// RAW SQL fragments:
	protected $_select   = array(); // SELECT select_expr
	protected $_from     = array(); // FROM table_references
	protected $_join     = array(); // JOIN table_references join_constraint
	protected $_where    = array(); // WHERE where_conditions
	protected $_groupby  = array(); // GROUP BY {col_name | expr | position} [ASC | DESC], ... [WITH ROLLUP]
	protected $_having   = array(); // HAVING where_conditions
	protected $_orderby  = array(); // ORDER BY {col_name | expr | position} [ASC | DESC], ...]
	protected $_distinct = false;   // SELECT DISTINCT
	protected $_limit    = false;   // LIMIT {[offset,] row_count | row_count OFFSET offset}
	protected $_offset   = false;   // LIMIT {[offset,] row_count | row_count OFFSET offset}
	protected $_set      = array(); // INSERT INTO [database.]table [(col_name,...)] VALUES (expr,...)
	
	/**
	 * Constructor
	 * @param array $config  contains [dsn], [username], [password], and optional [option]
	 */
	public function __construct($config) {
		$this->reset();
		if (isset($config['query_only']) && $config['query_only']) {
			$this->_query_only = true;
		} else {
			$this->_driver = substr($config['dsn'], 0, strpos($config['dsn'], ':')); // DSN is prefixed with <database driver>:
			try {
				if (@$config['option']) { // There is driver specific options specified
					$this->_conn = new PDO($config['dsn'], $config['username'], $config['password'], $config['option']);
				} else {
					$this->_conn = new PDO($config['dsn'], $config['username'], $config['password']);
				}
			} catch (PDOException $e) {
				throw new Exception('Connection failed: ' . $e->getMessage());
			}
		}
	}
	
	// GETTERS:
	public function conn() { return $this->_conn; }
	public function connection() { return $this->_conn; }
	public function driver() { return $this->_driver; }
	
	/**
	 * Get/Set $this->_distinct
	 * @param bool $set  if provided then set the properties also
	 * @return bool
	 */
	public function distinct($set = null) {
		if ($set !== null) $this->_distinct = (bool)$set;
		return $this->_distinct;
	}
	
	/**
	 * Return the last executed query
	 * @return string
	 */
	public function lastQuery() {
		return $this->_last;
	}
	
	/**
	 * Returns the ID of the last inserted row or sequence value 
	 * @param string $name  Name of the sequence object from which the ID should be returned.
	 * @return string
	 */
	public function lastInsertId($name = null) {
		return $this->_conn->lastInsertId($name);
	}
	
	/**
	 * Escape a value
	 * @param null|string|int|float $val
	 * @return string
	 */
	public function escapeValue($val) {
		if ($val === null) {
			return "NULL";
		} else if (is_bool($val)) {	// because SQLite doesn't have Boolean type
			if ($val) {
				return "1";	// true becomes 1
			} else {
				return "0";	// false becomes 0
			}
		} else if (is_numeric($val)) {
			return (string)$val;
		} else if (is_string($val)) {
			// escape according to driver
			if ($this->_conn) $quoted = $this->_conn->quote($val);
			if ($quoted !== false) {
				return $quoted;
			} else { // pdo can't quote, last resort
				return "'" . mysql_real_escape_string($val) . "'";	// escape ' , \ , \r , \t dan \n
			}
		} else if (is_array($val)) {
			$temp = "[";
			$first = true;
			foreach ($val as $vali) {
				if ($first) {
					$first = false;
				} else {
					$temp .= ",";
				}
				$temp .= $this->escapeValue($vali);
			}
			$temp .= "]";
			return $temp;
		} else {
			// undetermined type
			return "";
		}
	}
	
	/**
	 * Escape an identifier
	 * @param string $val
	 * @return string
	 */
	public function escapeName($val) {
		/* Possible types of val:
		 * - regular, normal name:	book 				-> `book`
		 * - with dot: 				table.book 			-> `table`.`book`
		 * - with phrase AS: 		table.book AS title	-> `table`.`book` AS `title`
		 */
		// @TODO: escape according to driver
		$val = trim($val); // trim
		$val = preg_replace('/\s*\.\s*/', "`.`", $val);          // escape "."
		$val = preg_replace('/\s+[Aa][Ss]\s+/', "` AS `", $val); // escape " AS "
		
		$temp = "`" . $val . "`";	// trim, then escape "." and " AS "
		$temp = preg_replace('/`\*`/', "*", $temp);            // change `*` into *
		$temp = preg_replace('/\s+DESC`$/i', "` DESC", $temp); // change  DESC` into ` DESC 
		$temp = preg_replace('/\s+ASC`$/i', "` ASC", $temp);   // change  ASC` into ` ASC
		return $temp;
	}
	
	/**
	 * Give items for SELECT statement
	 * @param string|array $names
	 * @param bool $escaped     if true then names will be escaped, default true
	 * @return DB $this
	 */
	public function select($names, $escaped = true) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!is_array($names)) {
			$names = explode(',', $names);
		}
		if ($escaped) {
			foreach ($names as $namesi) {
				$this->_select[] = $this->escapeName($namesi);
			}
		} else {
			foreach ($names as $namesi) {
				$this->_select[] = $namesi;
			}
		}
		return $this;
	}
	
	/**
	 * Give item names for FROM statement
	 * @param string|array $names
	 * @return DB $this
	 */
	public function from($names, $escaped = true) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!is_array($names)) {
			$names = explode(',', $names);
		}
		if ($escaped) {
			foreach ($names as $namesi) {
				$this->_from[] = $this->escapeName($namesi);
			}
		} else {
			foreach ($names as $namesi) {
				$this->_from[] = $namesi;
			}
		}
		return $this;
	}
	
	/**
	 * Add JOIN fragment
	 * @param string $name
	 * @param string $condition     expr not escaped automatically
	 * @param DB::JOIN_* $joinType  default "JOIN"
	 * @return DB $this
	 */
	public function join($name, $condition, $joinType = null) {
		if ($joinType === null) $joinType = self::JOIN;
		$this->_join[] = ($joinType . " " . $this->escapeName($name) . " ON " . $condition);
		return $this;
	}
	
	/**
	 * Add a WHERE segment
	 * @param string $name                 column name, may end with comparison operator [=, <>, <, <=, >, >=] 
	 * @param int|float|string|array $val  the value to be checked against
	 * @param DB::JOIN_* $op               either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @param bool $escaped                if true then name and val will get escaped, default true
	 * @return DB $this
	 */
	public function where($name, $val, $op = null, $escaped = true) {
		if ($op === null) $op = self::OP_AND;
		/* Possible types of name:
		 * - normal: "title"
		 * - with comparison operator: "title ="
		 * - with other operator: "title LIKE" or "year IN"
		 */
		$name = trim($name);	// trim name
		// cek whether name ends with operator or not
		$coop = "=";	// default comp operator
		//var matches = name.match(/(\w+)\s+(IN|LIKE|=|\<\>|\<|\<=|\>|\>=)$/i);
		if (preg_match('/(\w+)\s+(IN|LIKE|=|<>|<|<=|>|>=)$/i', $name, $matches)) {	// ends with comparison/other operator
			$name = $matches[1];	// extract the operator
			$coop = $matches[2];
		}
		if ($escaped) {
			$name = $this->escapeName($name);
			$val = $this->escapeValue($val);
		}
		// if this is the first element or the first in parenthese, then op is not needed
		if (count($this->_where) == 0 || preg_match('/\($/', end($this->_where))) {
			if ($op != self::OP_NOT) $op = "";	// only NOT is allowed
		}
		$this->_where[] = ($op . " " . $name . " " . $coop . " " . $val);
		return $this;
	}
	
	/**
	 * Add an opening parenthese
	 * @param DB::JOIN_* $op  either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @return DB $this
	 */
	public function wherePOpen($op = null) {
		if ($op === null) $op = self::OP_AND;
		if (count($this->_where) == 0 || preg_match('/\($/', end($this->_where))) {
			// if this is the first element or the first in parenthese, then op is not needed
			$this->_where[] = ("(");
		} else {
			$this->_where[] = ($op . " (");
		}
		return $this;
	}
	
	/**
	 * Add a closing parenthese
	 * @return DB $this
	 */
	public function wherePClose() {
		$this->_where[] = (")");	// simple, just add closing parenthese ")"
		return $this;
	}
	
	/**
	 * Add GROUP BY statement
	 * @param string|array $names
	 * @return DB $this
	 */
	public function group($names) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!is_array($names)) {
			$names = explode(',', $names);
		}
		foreach ($names as $namesi) {
			$this->_groupby[] = ($this->escapeName($namesi));
		}
		return $this;
	}
	
	/**
	 * Add a HAVING segment
	 * works exactly like where but using this._having
	 * @param string $name                 column name, may end with comparison operator [=, <>, <, <=, >, >=] 
	 * @param int|float|string|array $val  the value to be checked against
	 * @param DB::OP_* $op                 either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @param bool $escaped                if true then name and val will get escaped, default true
	 * @return DB $this
	 */
	public function having($name, $val, $op = null, $escaped = true) {
		if ($op === null) $op = self::OP_AND;
		/* Possible types of name:
		 * - normal: "title"
		 * - with comparison operator: "title ="
		 * - with other operator: "title LIKE" or "year IN"
		 */
		$name = trim($name);	// trim name
		// cek whether name ends with operator or not
		$coop = "=";	// default comp operator
		if (preg_match('/(\w+)\s+(IN|LIKE|=|<>|<|<=|>|>=)$/i', $name, $matches)) {	// ends with comparison/other operator
			$name = $matches[1];	// extract the operator
			$coop = $matches[2];
		}
		if ($escaped) {
			$name = $this->escapeName($name);
			$val = $this->escapeValue($val);
		}
		// if this is the first element or the first in parenthese, then op is not needed
		if (count($this->_having) == 0 || preg_match('/\($/', end($this->_having))) {
			if ($op != self::OP_NOT) $op = "";	// only NOT is allowed
		}
		$this->_having[] = ("$op $name $coop $val");
		return $this;
	}
	
	/**
	 * Add an opening parenthese
	 * @param DB::JOIN_* $op  either NOT|OR|OR_NOT|AND|AND_NOT default AND
	 * @return DB $this
	 */
	public function havingPOpen($op = null) {
		if ($op === null) $op = self::OP_AND;
		if (count($this->_having) == 0 || preg_match('/\($/', end($this->_having))) {
			// if this is the first element or the first in parenthese, then op is not needed
			$this->_having[] = ("(");
		} else {
			$this->_having[] = ($op . " (");
		}
		return $this;
	}
	
	/**
	 * Add a closing parenthese
	 * @return DB $this
	 */
	public function havingPClose() {
		$this->_having[] = (")");	// simple, just add closing parenthese ")"
		return $this;
	}
	
	/**
	 * Add ORDER statement
	 * @param string|array $names
	 * @return DB $this
	 */
	public function order($names) {
		/* Possible types of names:
		 * - a string:	"book"
		 * - a string with comma:	"book,address"
		 * - an array:	["book","address"]
		 */
		if (!is_array($names)) {
			$names = explode(',', $names);
		}
		foreach ($names as $namesi) {
			$this->_orderby[] = ($this->escapeName($namesi));
		}
		return $this;
	}
	
	/**
	 * Assign offset to limit
	 * @param int $n
	 * @return DB $this
	 */
	public function offset($n) {
		if (is_int($n)) {
			$this->_offset = $n;
		} else if (is_string($n)) {
			$this->_offset = (int)$n;
		} else {
			// not a number nor string, do nothing
			$this->_offset = false;
		}
		return $this;
	}
	
	/**
	 * Assign row limit
	 * @param int $n
	 * @return DB $this
	 */
	public function limit($n) {
		if (is_int($n)) {
			$this->_limit = $n;
		} else if (is_string($n)) {
			$this->_limit = (int)$n;
		} else {
			// not a number nor string, do nothing
			$this->_limit = false;
		}
		return $this;
	}
	
	/**
	 * Setting the value to be inserted/updated
	 * if `name` is an Object with [name1: val1, name2: val2,...] then it will be used and second parameter will be ignored
	 * @param string|array $name 
	 * @param string|int|float|null $val
	 * @param bool $escaped               if true then val will get escaped, default is true 
	 * @return DB $this
	 */
	public function set($name, $val = null, $escaped = true) {
		if (is_array($name)) {
			if ($escaped) {
				foreach ($name as $i => $namei) {
					$this->_set[$i] = $this->escapeValue($namei);
				}
			} else {
				foreach ($name as $i => $namei) {
					$this->_set[$i] = $namei;
				}
			}
		} else {
			if ($escaped) {
				$this->_set[$name] = $this->escapeValue($val);
			} else {
				$this->_set[$name] = $val;
			}
		}
		return $this;
	}
	
	/**
	 * Directly execute an SQL query
	 * and return the result
	 * @param string $query
	 * @return PDOStatement
	 */
	public function execute($query) {
		$this->_last = $query;
		return $this->_conn->query($query); // most likely PDOStatement
	}
	
	/**
	 * Return the generated SQL SELECT query in string
	 * @return string
	 */
	public function buildQuery() {
		return "SELECT " . $this->buildQuerySelect() 
			. " FROM " . $this->buildQueryFrom()
			. " WHERE " . $this->buildQueryWhere();
	}
	
	/**
	 * Generate the SELECT part
	 * @return string
	 */
	public function buildQuerySelect() {
		$query = "";
		// SELECT ...
		if ($this->_distinct) {
			$query .= " DISTINCT ";
		}
		if (count($this->_select) > 0) {
			$query .= implode(',', $this->_select);
		} else {
			$query .= "*";
		}
		return $query;
	}
	
	/**
	 * Generate the FROM part
	 * @return string
	 */
	public function buildQueryFrom() {
		$query = "";
		// FROM ...
		$query .= implode(',', $this->_from);
		if (count($this->_join) > 0) {
			// BETA
			$query .= " " . implode(' ', $this->_join);
		}
		return $query;
	}
	
	/**
	 * Generate the WHERE part
	 * @return string
	 */
	public function buildQueryWhere() {
		$query = "";
		// WHERE...
		if (count($this->_where) > 0) {
			// BETA TODO
			$query .= implode(' ', $this->_where);
		} else {
			$query .= "1"; // some databases cannot accept this
		}
		// GROUP BY ...
		if (count($this->_groupby) > 0) {
			$query .= " GROUP BY " . implode(',', $this->_groupby);
			// BETA TODO
		}
		// HAVING ...
		if (count($this->_having) > 0) {
			$query .= " HAVING " . implode(' ', $this->_having);
			// BETA TODO
		}
		// ORDER BY ...
		if (count($this->_orderby) > 0) {
			$query .= " ORDER BY " . implode(',', $this->_orderby);
			// BETA TODO
		}
		// LIMIT ...
		if ($this->_limit) {
			$query .= " LIMIT ";
			if ($this->_offset) {
				$query .= (string)$this->_offset . ",";
			}
			$query .= (string)$this->_limit;
		}
		return $query;
	}
	
	/**
	 * Return the query building to empty state
	 */
	public function reset() {
		$this->_select = array();
		$this->_from = array();
		$this->_join = array();
		$this->_where = array();
		$this->_groupby = array();
		$this->_having = array();
		$this->_orderby = array();
		$this->_distinct = false;
		$this->_limit = false;
		$this->_offset = false;
		$this->_set = array();
	}
	
	/**
	 * Retrieve information about the last error occured
	 * @return array
	 */
	public function lastError() {
		return array(
			'query' => $this->_last,
			'code' => $this->_conn->errorCode(),
			'info' => $this->_conn->errorInfo(),
		);
	}
	
	/**
	 * Return the result of current SELECT query built
	 * @return PDOStatement
	 */
	public function doGet() {
		$this->_last = $this->buildQuery();
		$result = $this->_conn->query($this->_last);
		$this->reset();
		if ($result !== false) {
			return $result;
		} else {
			throw new Exception('Error executing query SELECT: ' . print_r($this->lastError(), true));
		}
	}
	
	/**
	 * Return the first row 
	 * @return array
	 */
	public function doFetchRow() {
		return $this->doGet()->fetch(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Return all row in array form
	 * @return array
	 */
	public function doFetchAll() {
		return $this->doGet()->fetchAll(PDO::FETCH_ASSOC);
	}
	
	/**
	 * Return number of rows affected by the WHERE part of query
	 * @return int
	 */
	public function doCount() {
		// BETA 
		$this->_last = "SELECT COUNT(*) AS countrow FROM " . $this->buildQueryFrom() . " WHERE " . $this->buildQueryWhere();
		$result = $this->_conn->query($this->_last);
		if ($result !== false) {
			// return result.singleRow["countrow"];
			return $result->fetchColumn();
		} else {
			throw new Exception('Error executing query COUNT: ' . print_r($this->lastError(), true));
		}
	}
	
	/**
	 * Execute the INSERT query
	 * then return lastInsertId
	 * @param array $data    a key (column-name) <-> value (value) pair, if undefined then use this._set
	 * @param bool $escaped  if true then the value will be escaped, default is true 
	 * @return string
	 */
	public function doInsert($data = null, $escaped = true) {
		if (is_assoc($data)) {
			foreach ($data as $i => $datai) {
				$this->set($i, $datai, $escaped);
			}
		}
		// Typical INSERT statement: INSERT INTO table_name (column1,...,columnN) VALUES (value1,...,valueN)
		// Build the columns string and values string
		$columns = "(";
		$values = "(";
		$first = true;
		foreach ($this->_set as $i => $seti) {
			if ($first) {
				$first = false;
			} else {
				$columns .= ",";
				$values .= ",";
			}
			$columns .= $this->escapeName($i); // column name hasn't been escaped
			$values .= $seti; // value has been escaped before
		}
		$columns .= ")";
		$values .= ")";
		$this->_last = "INSERT INTO " . $this->buildQueryFrom() . " " . $columns . " VALUES " . $values;
		// closing
		$result = $this->_conn->query($this->_last);
		$this->reset();
		if ($result !== false) {
			return $this->lastInsertId();
		} else {
			throw new Exception('Error executing query INSERT: ' . print_r($this->lastError(), true));
		}
	}
	
	/**
	 * Execute the UPDATE query
	 * @return int  number of rows get effected
	 */
	public function doUpdate() {
		// Typical UPDATE statement: UPDATE table_name SET column1=value1,...,columnN=valueN WHERE where_condition
		$qset = "";
		$first = true;
		foreach ($this->_set as $i => $seti) {
			if ($first) {
				$first = false;
			} else {
				$qset .= ",";
			}
			$qset .= $this->escapeName($i) . " = " . $seti;
		}
		$this->_last = "UPDATE " . $this->buildQueryFrom() . " SET " . $qset . " WHERE " . $this->buildQueryWhere();
		// closing
		$result = $this->_conn->query($this->_last);	
		$this->reset();
		if ($result !== false) {
			return $result; // since i don't know what else to return :|
		} else {
			throw new Exception('Error executing query UPDATE: ' . print_r($this->lastError(), true));
		}
	}
	
	/**
	 * Execute the DELETE query
	 * the condition must be defined already (FROM and WHERE)
	 * @return int  number of rows get effected
	 */
	public function doDelete() {
		$this->_last = "DELETE FROM " . $this->buildQueryFrom() . " WHERE " . $this->buildQueryWhere();
		$result = $this->_conn->query($this->_last);
		$this->reset();
		if ($result !== false) {
			return $result;
		} else {
			throw new Exception('Error executing query DELETE: ' . print_r($this->lastError(), true));
		}
	}
	
	/// TRANSACTIONS:
	public function beginTransaction() {
		return $this->_conn->beginTransaction();
	}
	
	public function commit() {
		return $this->_conn->commit();
	}
	
	public function rollBack() {
		return $this->_conn->rollBack();
	}
	
	// Underscore equivalent:
	public function last_query()             { return $this->_last; }
	public function last_insert_id($name = null) { return $this->lastInsertId($name); }
	public function escape_value($val)       { return $this->escapeValue($val); }
	public function escape_name($val)        { return $this->escapeName($val); }
	public function where_popen($op = null)  { return $this->wherePOpen($op); }
	public function where_pclose()           { return $this->wherePClose(); }
	public function having_popen($op = null) { return $this->havingPOpen($op); }
	public function having_pclose()          { return $this->havingPClose(); }
	public function build_query()            { return $this->buildQuery(); }
	public function build_query_select()     { return $this->buildQuerySelect(); }
	public function build_query_from()       { return $this->buildQueryFrom(); }
	public function build_query_where()      { return $this->buildQueryWhere(); }
	public function last_error()             { return $this->lastError(); }
	public function do_get()                 { return $this->doGet(); }
	public function do_fetch_row()           { return $this->doFetchRow(); }
	public function do_fetch_all()           { return $this->doFetchAll(); }
	public function do_count()               { return $this->doCount(); }
	public function do_insert($data, $escaped = true) { return $this->doInsert($data, $escaped); }
	public function do_update()              { return $this->doUpdate(); }
	public function do_delete()              { return $this->doDelete(); }
	public function begin_transaction()      { return $this->beginTransaction(); }
	public function roll_back()              { return $this->rollBack(); }
}

// EXAMPLES
/*
$a = new DB(array(
	'dsn' => 'mysql:host=localhost;dbname=gesh', 
	'username' => 'root', 
	'password' => 'rootpassword',
));
$r = $a->select('*')->from('jabatan')->doGet();
print_r($r);
echo $a->lastQuery();
echo 'before'; print_r($r->fetchAll());
$r = $a->set('jabatan', 'pengamen')->from('jabatan')->doInsert();
echo $r;
print_r($a->select('*')->from('jabatan')->doGet()->fetchAll());
$a->from('jabatan')->where('jabatan', 'pengamen')->doDelete();
echo $a->last_query();
print_r($a->select('*')->from('jabatan')->doGet()->fetchAll());
*/