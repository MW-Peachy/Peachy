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
 * DatabasePgSQL class, specifies the PostgreSQL-specific functions 
 * @todo Add hooks in here
 * @package Database
 */
class DatabasePgSQL extends DatabaseBase {

	public function get_type() {
		return 'pgsql';
	}
	
	public function doQuery( $sql ) {
		if (function_exists('mb_convert_encoding')) {
			$sql = mb_convert_encoding($sql,'UTF-8');
		} 
		
		$ret = pg_query( $sql, $this->mConn ); 
		
		return $ret;
	}
	
	public function open() {
		if( !function_exists( 'pg_connect' ) ) {
			throw new DependancyError( "PostgreSQL", "http://us2.php.net/manual/en/book.pgsql.php" );
		}
		
		$version = pg_version();
		if( version_compare( $version['client'], '8.1' ) < 0 ) {
			throw new DependancyError( "PostgreSQL 8.1", "http://us2.php.net/manual/en/book.pgsql.php" );
		}
		
		$this->close(); 
		
		$this->mOpened = false; 
		
		$this->mConn = pg_connect( "host={$this->mServer} port=" . substr( $this->mPost, 1 ) . " dbname={$this->mDB} user={$this->mUser} password={$this->mPassword}" );
		
		if( !$this->mConn ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}
		
		$this->mOpened = true; 
		
		$this->doQuery( "SET client_encoding='UTF8'" ); 
		
		return $this->mConn;
	}
	
	public function close() {
		$this->mOpened = false;
		
		if( $this->mConn ) {
			return pg_close( $this->mConn ); 
		}
		else {
			return true;
		}
	}
	
	public function fetchObject( $res ) {
		$row = @pg_fetch_object( $res ); 
		
		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}
		
		return $row;
	}
	
	public function fetchRow( $res ) {
		$row = @pg_fetch_assoc( $res ); 
		
		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}
		
		return $row;
	}
	
	public function numRows( $res ) {
		$row = @pg_num_rows( $res ); 
		
		if( $this->lastErrno() ) {
			throw new DBError( $this->lastErrno(), $this->lastError() );
		}
		
		return $row;
	}
	
	public function numFields( $res ) {
		return @pg_num_fields( $res ); 
	}
	
	public function get_field_name( $res, $n ) {
		return pg_field_name( $res, $n ); 
	}
	
	public function get_insert_id() { 
		return null; 
		##FIXME:: Run a SELECT query using the LastQuery params to get the ID
	} 
	
	public function lastErrno() {
		return 0;
	}
	
	public function lastError() {
		if ( $this->mConn ) {
			$error = pg_last_error( $this->mConn );
			if ( !$error ) {
				$error = pg_last_error();
			}
		} 
		else {
			$error = pg_last_error();
		}
		
		return $error;
	}

	public function affectedRows() { 
		return pg_affected_rows( $this->mConn ); 
	} 
	
	public function strencode( $s ) {
		return pg_escape_string( $s );
	} 	
	
	public function dataSeek( $res, $row ) {
		return pg_result_seek( $res, $row );
	}
	
}