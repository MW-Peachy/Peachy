<?php

/*
This file is part of Peachy MediaWiki Bot API

Peachy is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * @file
 * Database plugin, contains functions that interact with a mysql/postgresql database
 * Much of this is from {@link http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/includes/db/} which is licenced under the GPL.
 */

/**
 * DatabaseBase class, specifies the general functions for the Database classes
 * @abstract
 * @package Database
 */
abstract class DatabaseBase {
	
	/**
	 * String of the last SQL query that was run
	 * @var string
	 * @access protected
	 */
	protected $mLastQuery;
	
	/**
	 * Array of the most recent {@link DatabaseBase::select()} parameters
	 * @var array
	 * @access protected
	 */
	protected $mLastSelectParams;
	
	/**
	 * MySQL/PostgreSQL/MySQLi connection object
	 * @var object|resource
	 * @access protected
	 */
	protected $mConn = false;
	
	/**
	 * Server to connect to
	 * @var string
	 * @access protected
	 */
	protected $mServer;
	
	/**
	 * Port to connect to, in format :XXXX
	 * @var string
	 * @access protected
	 */
	protected $mPort;
	
	/**
	 * Username to connect with
	 * @var string
	 * @access protected
	 */
	protected $mUser;
	
	/**
	 * Password to connect with
	 * @var string
	 * @access protected
	 */
	protected $mPassword;
	
	/**
	 * Database to connect to
	 * @var string
	 * @access protected
	 */
	protected $mDB;
	
	/**
	 * Prefix used for tables
	 * @var string
	 * @access protected
	 */
	protected $mPrefix;
	
	
	/**
	 * Whether or not the connection is open
	 * @var bool
	 * @access protected
	 */
	protected $mOpened = false;
	
	/**
	 * Construct function, sets class variables and connects
	 * @param string $server Server to connect to
	 * @param string $port Port
	 * @param string $user Username
	 * @param string $pass Password
	 * @param string $db Database
	 * @return void
	 */
	function __construct( $server, $port, $user, $password, $dbname ) {
		$this->mServer = $server;
		$this->mPort = $port;
		$this->mUser = $user;
		$this->mPassword = $password;
		$this->mDB = $dbname;
		
		Hooks::runHook( 'DatabaseConnect', array( &$this->mServer, &$this->mPort, &$this->mUser, &$this->mPassword, &$this->mDB ) );
		
		$this->open();
	}
	
	/**
	 * Opens a MySQL connection
	 */
	abstract function open();
	
	/**
	 * Closes a MySQL connection
	 */
	function close() {
		return true;
	} 
	
	/**
	 * Run a MySQL query
	 * @param string $sql Query to run
	 * @return ResultWrapper|bool 
	 */
	public function query( $sql ) {
		$this->mLastQuery = $sql;
		
		Hooks::runHook( 'DatabasePreRunQuery', array( &$sql ) );
		
		$ret = $this->doQuery( $sql );
		
		Hooks::runHook( 'DatabasePostRunQuery', array( &$ret ) );
		
		if( is_bool( $ret ) ) return $ret;
		
		$ret = $this->resultObject( $ret );
		if( !$ret ) {
			throw new DBError( $this->lastError(), $this->lastErrno(), $sql ); 
		}
		
		return $ret; 
	}
	
	/**
	 * Actual query running
	 */
	abstract function doQuery( $sql );
	
	/**
	 * Standardizes MySQL resource, converts to a {@link ResultWrapper} object
	 * @param resource|ResultWrapper  $result
	 * @return ResultWrapper|bool
	 */
	function resultObject( $result ) {
		if( empty( $result ) ) {
			return false;
		} elseif ( $result instanceof ResultWrapper ) {
			return $result;
		} else {
			return new ResultWrapper( $this, $result );
		}
	}
	
	abstract function fetchObject( $res ); 
	abstract function fetchRow( $res ); 
	abstract function numRows( $res ); 
	abstract function numFields( $res ); 
	abstract function get_field_name( $res, $n ); 
	abstract function get_insert_id(); 
	abstract function lastErrno(); 
	abstract function lastError(); 
	abstract function affectedRows(); 
	abstract function dataSeek( $res, $row );
	abstract function strencode( $s );
	
	/**
	 * SELECT frontend
	 * @param array|string $table Table(s) to select from. If it is an array, the tables will be JOINed.
	 * @param string|array $columns Columns to return
	 * @param string|array $where Conditions for the WHERE part of the query. Default null.
	 * @param array $options Options to add, such as GROUP BY, HAVING, ORDER BY, LIMIT, EXPLAIN. Default an empty array.
	 * @param array $join_on If selecting from more than one table, this adds an ON statement to the query. Defualt an empty array.
	 * @return resource|ResultWrapper MySQL object
	 */
	function select( $table, $columns, $where = array(), $options = array(), $join_on = array() ) {
		
		$this->mLastSelectParams = array( $table, $columns, $where, $options, $join_on );
		
		Hooks::runHook( 'DatabaseRunSelect', array( &$this->mLastSelectParams ) );
		
		if( is_array( $table ) ) {
			if( $this->mPrefix != '' ) {
				foreach( $table AS $id => $t ) {
					$table[$id] = $this->mPrefix . $t;
				}
			}
			
			if( !count( $join_on ) ) {
				$from = 'FROM ' . implode( ',', $table );
				$on = null;
			}
			else {
				$tmp = array_shift( $table );
				$from = 'FROM ' . $tmp;
				$from .= ' JOIN ' . implode( ' JOIN ', $table );
				
				$on = array();
				foreach( $join_on as $col => $val ) {
					$on[] = "$col = $val";
				}
				$on = 'ON ' . implode( ' AND ', $on );
			}
		}
		else {
			$from = 'FROM ' . $this->mPrefix . $table;
			$on = null;
		}
		
		
		if( is_array( $columns ) ) {
			$columns = implode( ',', $columns );
		}
		
		
		if( $where ) {
			if( is_array( $where ) ) {
			
				$where_tmp = array();
				
				foreach( $where as $col => $val ) {
					if( is_numeric( $col ) ) {
						$where_tmp[] = $val;
					}
					else {
						if( is_array( $val ) ) {
							$opr = $val[0];
							$val = $this->strencode( $val[1] );
							
							$where_tmp[] = "`$col` $opr '$val'";
						}
						else {
							$val = $this->strencode( $val );
							$where_tmp[] = "`$col` = '$val'";
						}
					}				
				}
				$where = implode( ' AND ', $where_tmp );
			}
			$where = "WHERE $where";
		}
		else {
			$where = null;
		}
		
		if( !is_array( $options ) ) {
			$options = array( $options );
		}
		
		$newoptions = array();
		$limit = null;
		$explain = null;
		
		foreach( $options as $option => $val ) {
			switch( $option ) {
				case 'LIMIT':
					$limit = "LIMIT $val";
					break;
				case 'EXPLAIN':
					$explain = "EXPLAIN $val";
					break;
				default:
					$newoptions[] = "$option $val";
			}
		}
		
		$newoptions = implode( ' ', $newoptions );
		
		$sql = "$explain SELECT $columns $from $on $where $newoptions $limit";
		
		return $this->query( $sql );
	}
	
	/**
	 * INSERT frontend
	 * @param string $table Table to insert into.
	 * @param array $values Values to set.
	 * @param array $options Options
	 * @param string $select What the operation is, either INSERT or REPLACE INTO. Default INSERT. 
	 * @return resource|ResultWrapper
	 */
	function insert( $table, $values, $options = array(), $select = "INSERT" ) {
	
		Hooks::runHook( 'DatabaseRunInsert', array( &$table, &$values, &$options, &$select ) );
		
		if ( !count( $values ) ) {
			return true;
		}
		
		if ( !is_array( $options ) ) {
			$options = array( $options );
		}
		
		$cols = array();
		$vals = array();
		foreach( $values as $col => $value ) {
			$cols[] = "`$col`";
			$vals[] = "'" . $this->strencode( $value ) . "'";
		}
		
		$cols = implode( ',', $cols );
		$vals = implode( ',', $vals );
		
		$sql = $select . " " . implode( ' ', $options ) . " INTO {$this->mPrefix}$table ($cols) VALUES ($vals)";

		return $this->query( $sql );
	}
	
	/**
	 * UPDATE frontend
	 * @param string $table Table to update.
	 * @param array $values Values to set.
	 * @param array $conds Conditions to update. Default *, updates every entry.
	 * @return resource|ResultWrapper
	 */
	function update( $table, $values, $conds = '*' ) { 
		
		Hooks::runHook( 'DatabaseRunUpdate', array( &$table, &$values, &$conds ) );
		
		$vals = array();
		foreach( $values as $col => $val ) {
			$vals[] = "`$col`" . "= '" . $this->strencode( $val ) . "'";
		}
		$vals = implode( ', ', $vals );
		
		
		$sql = "UPDATE {$this->mPrefix}$table SET " . $vals;
		
		if ( $conds != '*' ) {
		
			$cnds = array();
			
			foreach( $conds as $col => $val ) {
				$cnds[] = "`$col`" . "= '" . $this->strencode( $val ) . "'";
			}
			
			$cnds = implode( ', ', $cnds );
			
			$sql .= " WHERE " . $cnds;
		}
		
		return $this->query( $sql );
	}
	
	/**
	 * DELETE frontend
	 * @param string $table Table to delete from.
	 * @param array $conds Conditions to delete. Default *, deletes every entry.
	 * @return resource|ResultWrapper
	 */
	function delete( $table, $conds = '*' ) {
		
		Hooks::runHook( 'DatabaseRunDelete', array( &$sql ) );
		
		$sql = "DELETE FROM {$this->mPrefix}$table";
		
		if ( $conds != '*' ) {
		
			$cnds = array();
			foreach( $conds as $col => $val ) {
				$cnds[] = "`$col`" . "= '" . $this->strencode( $val ) . "'";
			}
			$cnds = implode( ' AND ', $cnds );
			
			$sql .= " WHERE " . $cnds;
		}
		
		return $this->query( $sql );
	}
	
	/**
	 * REPLACE frontend, shortcut for DatabaseBase::{@link insert}.
	 * @param string $table Table to insert into.
	 * @param array $values Values to set.
	 * @param array $options Options 
	 * @return resource|ResultWrapper
	 */
	function replace( $table, $values, $options = array() ) {
		return $this->insert( $table, $values, $options, "REPLACE INTO" );
	}
	
	/**
	 * Checks if a table exists
	 * @param string $table Table to search for.
	 * @param bool $prefix Whether or not to use the stored mPrefix variable 
	 * @return bool
	 */
	function tableExists( $table, $prefix = true ) {
		if( $prefix ) {
			$prefix = $this->mPrefix;
		}
		else {
			$prefix = null;
		}
		
		Hooks::runHook( 'DatabaseRunTableExists', array( &$table, &$prefix ) );
		
		$res = $this->query( "SELECT 1 FROM {$prefix}{$table} LIMIT 1" );
		
		if( $res ) {
			$this->freeResult( $res );
			return true;
		}
		else {
			return false;
		}
	}
	
	/**
	 * Sets a table prefix
	 * @param string $prefix Table prefix to set 
	 * @return void
	 */
	function set_prefix( $prefix ) {
		$this->mPrefix = $prefix;
	}
	
	/**
	 * Returns whether or not the connection was opened
	 * @return bool
	 */
	function is_opened() {
		return $this->mOpened;
	}
	
}

/**
 * ResultWrapper class.
 * Iterator is the built-in PHP class that allows other classes to use foreach(), for(), etc
 * 
 * @implements Iterator
 * @implements Countable
 * @implements ArrayAccess
 * @package Database
 */
class ResultWrapper implements Iterator, Countable, ArrayAccess {
	
	/**
	 * Database object (really the child of DatabaseBase)
	 * @var object
	 * @access public
	 */
	public $db;
	
	/**
	 * MySQL result
	 * @var resource
	 * @access public
	 */
	public $result;
	
	/**
	 * Iterator position
	 * @var int
	 * @access public
	 */
	public $pos = 0;
	
	/**
	 * Where the database result is stored
	 * @var array
	 * @access public
	 */
	public $endArray = array();

	/**
	 * Initiate the Iterator
	 * @param object $database Database object
	 * @param resource|ResultWrapper $result MySQL resource
	 * @return void
	 */
	function __construct( $database, $result ) {
		$this->db = $database;
		
		if ( $result instanceof ResultWrapper ) {
			$this->result = $result->result;
		} else {
			$this->result = $result;
		}
		
		while( $row = $this->fetchRow() ) {
			$this->endArray[] = $row;
		}
	}

	/**
	 * Get the number of rows in a MySQL object
	 * @return int
	 */
	function numRows() {
		return $this->db->numRows( $this->result );
	}
	
	/**
	 * Shortcut for $this->numRows, used to allow count()ing the ResultWrapper class
	 * @return int
	 */
	function count() {
		return $this->numRows();
	}

	/**
	 * Fetch the next row from the given result object, in object form.
	 * Fields can be retrieved with $row->fieldname, with fields acting like
	 * member variables.
	 *
	 * @return resource MySQL object
	 */
	function fetchObject() {
		return $this->db->fetchObject( $this->result );
	}

	/**
	 * Fetch the next row from the given result object, in associative array
	 * form.  Fields are retrieved with $row['fieldname'].
	 *
	 * @return array MySQL row object
	 */
	function fetchRow() {
		return $this->db->fetchRow( $this->result );
	}
	
	/**
	 * Free a result object
	 * @return void
	 */
	function free() {
		$this->db->freeResult( $this->result );
		unset( $this->result );
		unset( $this->db );
	}

	/**
	 * Change the position of the cursor in a result object
	 *
	 * @param $row Row to place cursor at
	 * @return void
	 * @see http://php.net/mysql_data_seek
	 */
	function seek( $row ) {
		$this->db->dataSeek( $this->result, $row );
	}

	/**
	 * Set the cursor to the first object
	 * @return void
	 */
	function rewind() {
		$this->pos = 0;
	}

	/**
	 * Return the current row
	 * @return resource
	 */
	function current() {
		return $this->endArray[ $this->pos ];
	}

	/**
	 * Return the current position
	 * @return int
	 */
	function key() {
		return $this->pos;
	}
	
	/**
	 * Set the cursor to the next object
	 * @return resource
	 */
	function next() {
		$this->pos++;
	}
	
	/**
	 * Return whether or not there is any current row
	 * @return bool
	 */
	function valid() {
		return isset( $this->endArray[ $this->pos ] );
	}
	
	/** 
	 * Called when a key is being set
	 * 
	 * @param string $key Key to set
	 * @param mixed $value Value to set
	 * @return void
	 */
	function offsetSet( $key, $value ) {
        $this->endArray[$key] = $value;
    }
    
    /** 
	 * Called when isset() is called on a key
	 * 
	 * @param string $key Key to check
	 * @return bool
	 */
    function offsetExists( $key ) {
        return isset( $this->endArray[$key] );
    }
    
    /** 
	 * Called when unset() is called on a key
	 * 
	 * @param string $key Key to unset
	 * @return void
	 */
    function offsetUnset( $key ) {
        unset( $this->endArray[$key] );
    }
    
    /** 
	 * Gets the value stored in the key, or false if it doesn't exist
	 * 
	 * @param string $key Key to get
	 * @return mixed|bool
	 */
    function offsetGet( $key ) {
        return isset( $this->endArray[$key] ) ? $this->endArray[$key] : false;
    }
}



/**
 * Database class, the actual class the user directly interfaces with.
 */
class Database {
	
	/**
	 * Type of database to use. Default mysqli
	 * @var string
	 * @access private
	 */
	private $type = 'mysqli';
	
	/**
	 * Server to connect to
	 * @var string
	 * @access private
	 */
	private $server;
	
	/**
	 * Port to use
	 * @var string
	 * @access private
	 */
	private $port;
	 
	/**
	 * Username
	 * @var string
	 * @access private
	 */
	private $user;
	 
	/**
	 * Password
	 * @var string
	 * @access private
	 */
	private $password;
	 
	/**
	 * Database name
	 * @var string
	 * @access private
	 */
	private $db;
	
	/**
	 * Database class
	 * @var object
	 * @access private
	 */
	private $object;
	
	/**
	 * Constructor.
	 * If passed as server, user, pass, dbname; the port is ignored. 
	 * If passed as server, port, user, pass, dbname; the port is as given
	 * Basically, the function will detect if a port was specified. 
	 *
	 * @param string $server Server to connect to
	 * @param int $port Port, default 3306
	 * @param string $user Username
	 * @param string $pass Password
	 * @param string $dbname Database, default null
	 * @return Database
	 */
	function __construct( $server, $port = 3306, $user, $pass, $dbname = null ) {
		if( func_num_args() > 4 ) {
			$this->server = $server;
			$this->port = ':' . $port;
			$this->user = $user;
			$this->password = $pass;
			$this->db = $dbname;
		}
		else {
			$this->server = $server;
			$this->port = '';
			$this->user = $port;
			$this->password = $user;
			$this->db = $pass;
		}
		
		$this->object = $this;	
		
		Hooks::runHook( 'LoadDatabase', array( &$this->server, &$this->port, &$this->user, &$this->password, &$this->db ) );

	}
	
	/**
	 * Sets the database type
	 * @param string $type Database type, either 'mysqli', 'mysql', or 'pgsql'
	 * @return void
	 */
	public function set_type( $type ) {
		$this->type = $type;
		
		if( $this->object != $this ) {
			$this->doInit();
		}
	}
	
	/**
	 * Inclused and initiates the appropriate DatabaseBase child
	 * @return object
	 * @deprecated since 18 June 2013
	 */
	public function &init() {
		self::deprectaedWarn( null, null, "Warning: Database::init() is deprecated. Thanks to the wonders of PHP 5, the call can just be removed." );
		return $this->doInit();
	}
	
	/**
	 * Inclused and initiates the appropriate DatabaseBase child
	 * @return object
	 */
	private function &doInit() {
		if( !class_exists( 'mysqli' ) ) {
			$this->type = 'mysql';
		}
		
		Hooks::runHook( 'InitDatabase', array( &$this->type ) );
		
		switch( $this->type ) {
			case 'mysqli':
				$this->object = new DatabaseMySQLi( $this->server, $this->port, $this->user, $this->password, $this->db );
				return $this->object;
				break;
			case 'mysql':
				$this->object = new DatabaseMySQL( $this->server, $this->port, $this->user, $this->password, $this->db );
				return $this->object;
				break;
			case 'pgsql':
				$this->object = new DatabasePgSQL( $this->server, $this->port, $this->user, $this->password, $this->db );
				return $this->object;
				break;
			default:
				$this->object = new DatabaseMySQLi( $this->server, $this->port, $this->user, $this->password, $this->db );
				return $this->object;
				break;
		}	
	}
	
	public function __call( $name, $arguments ) {
		
		if( $this->object == $this ) {
			$this->doInit();
		}
		
		if( method_exists( $this->object, $name ) ) { 
			return call_user_func_array( array( $this->object, $name ), $arguments );
		}
		else {
			throw new Exception( "Call to invalid method: " . get_class( $this->object ) . "::$name( " . implode( ', ', $arguments ) . " )" );
		}
	}
	
}