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
 * DatabaseMySQL class, specifies the MySQL-specific functions
 * @package Database
 */
class DatabaseMySQL extends DatabaseBase {

	/**
	 * Runs a mysql query.
	 * @param string $sql SQL to run
	 * @return resource
	 */
	public function doQuery( $sql ) {
		$ret = mysql_query( $sql, $this->mConn );
		return $ret;
	}

	/**
	 * Connects to a mysql database
	 * @throws DependencyError
	 * @return bool Whether or not the connection was opened
	 */
	public function open() {
		if( !function_exists( 'mysql_connect' ) ) {
			throw new DependencyError( "MySQL", "http://us2.php.net/manual/en/book.mysql.php" );
		}

		if( version_compare( mysql_get_client_info(), '5.0' ) < 0 ) {
			throw new DependencyError( "MySQL 5.0", "http://us2.php.net/manual/en/book.mysql.php" );
		}

		$this->close();

		$this->mOpened = false;

		if( ini_get( 'mysql.connect_timeout' ) <= 3 ) {
			$numAttempts = 2;
		} else {
			$numAttempts = 1;
		}

		for( $i = 0; $i < $numAttempts && !$this->mConn; $i++ ){
			if( $i > 1 ) {
				usleep( 1000 );
			}

			$this->mConn = mysql_connect( $this->mServer . $this->mPort, $this->mUser, $this->mPassword, true );
		}

		if( $this->mConn && !is_null( $this->mDB ) ) {
			$this->mOpened = @mysql_select_db( $this->mDB, $this->mConn );
		} else {
			$this->mOpened = (bool)$this->mConn;
		}

		return $this->mOpened;
	}

	/**
	 * Closes the database connection
	 * @return bool
	 */
	public function close() {
		$this->mOpened = false;

		if( $this->mConn ) {
			return mysql_close( $this->mConn );
		} else {
			return true;
		}
	}

	/**
	 * Returns a MySQL object using mysql_fetch_object()
	 *
	 * @param resource $res MySQL result from mysql_query()
	 *
	 * @throws DBError
	 * @return object
	 */
	public function fetchObject( $res ) {

		Hooks::runHook( 'DatabaseFetchObject', array( &$res ) );

		$row = mysql_fetch_object( $res );

		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}

		return $row;
	}

	/**
	 * Returns a MySQL array using mysql_fetch_assoc()
	 *
	 * @param resource $res MySQL result from mysql_query()
	 *
	 * @throws DBError
	 * @return array
	 */
	public function fetchRow( $res ) {

		Hooks::runHook( 'DatabaseFetchRow', array( &$res ) );
		$row = mysql_fetch_assoc( $res );

		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}

		return $row;
	}

	/**
	 * Returns the number of rows that were modified/returned
	 *
	 * @param resource $res MySQL result from mysql_query()
	 *
	 * @throws DBError
	 * @return int
	 */
	public function numRows( $res ) {

		Hooks::runHook( 'DatabaseNumRows', array( &$res ) );

		$row = mysql_num_rows( $res );

		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}

		return $row;
	}

	/**
	 * Returns the number of fields in a result using mysql_fetch_fields()
	 *
	 * @param resource $res MySQL result from mysql_query()
	 *
	 * @throws DBError
	 * @return int
	 */
	public function numFields( $res ) {

		Hooks::runHook( 'DatabaseNumFields', array( &$res ) );

		$row = mysql_num_fields( $res );

		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}

		return $row;
	}

	/**
	 * Get the name of a specified field using mysql_field_name()
	 * @param resource $res MySQL result from mysql_query()
	 * @param int $n Field offset
	 * @return string
	 */
	public function get_field_name( $res, $n ) {

		Hooks::runHook( 'DatabaseFieldName', array( &$res, &$n ) );

		return mysql_field_name( $res, $n );
	}

	/**
	 * Returns ID generated in the previous query
	 * @return int
	 */
	public function get_insert_id() {

		Hooks::runHook( 'DatabaseGetInsertId', array() );
		return mysql_insert_id( $this->mConn );
	}

	/**
	 * Returns the error code from the last query
	 * @return int
	 */
	public function lastErrno() {
		if( $this->mConn ) {
			return mysql_errno( $this->mConn );
		} else {
			return mysql_errno();
		}
	}

	/**
	 * Returns the error string from the last query
	 * @return string
	 */
	public function lastError() {
		if( $this->mConn ) {
			$error = mysql_error( $this->mConn );
			if( !$error ) {
				$error = mysql_error();
			}
		} else {
			$error = mysql_error();
		}

		return $error;
	}

	/**
	 * Returns the number of affected rows in the last query
	 * @return int
	 */
	public function affectedRows() {

		Hooks::runHook( 'DatabaseAffectedRows', array() );

		return mysql_affected_rows( $this->mConn );
	}

	/**
	 * Sanitizes a text field
	 * @param string $s
	 * @return string
	 */
	public function strencode( $s ) {

		Hooks::runHook( 'DatabaseEscape', array( &$s ) );

		$sQuoted = mysql_real_escape_string( $s, $this->mConn );

		if( !$sQuoted ) {
			$this->ping();
			$sQuoted = mysql_real_escape_string( $s, $this->mConn );
		}

		return $sQuoted;
	}

	/**
	 * Pings the server and reconnects if necessary
	 * @return bool
	 */
	public function ping() {

		Hooks::runHook( 'DatabasePing', array() );

		$ping = mysql_ping( $this->mConn );

		if( $ping ) {
			return true;
		}

		mysql_close( $this->mConn );

		$this->mOpened = false;

		$this->mConn = false;

		$this->open( $this->mServer, $this->mUser, $this->mPassword, $this->mDBname );

		return true;
	}

	/**
	 * Moves internal result pointer
	 * @param resource $res MySQL result from mysql_query()
	 * @param int $row Row number
	 * @return bool
	 */
	public function dataSeek( $res, $row ) {

		Hooks::runHook( 'DatabaseDataSeek', array( &$res, &$row ) );

		return mysql_data_seek( $res, $row );
	}


}