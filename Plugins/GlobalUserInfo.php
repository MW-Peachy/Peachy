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

class GlobalUserInfo {

	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access private
	 */
	private $wiki;
	
	/**
	 * Username
	 * 
	 * @var string
	 * @access private
	 */
	private $username;
	
	/**
	 * Global groups member is a part of
	 * 
	 * @var array
	 * @access private
	 */
	private $groups = array();
	
	/**
	 * Accounts that user has merged on other wikis
	 * 
	 * @var array
	 * @access private
	 */
	private $merged = array();
	
	/**
	 * Accounts that are not attached to the global account
	 * 
	 * @var array
	 * @access private
	 */
	private $unattached = array();
	
	/**
	 * Whether or not global account exists
	 * 
	 * @var bool
	 * @access private
	 */
	private $exists = true;
	
	/**
	 * Date that global account was created
	 * 
	 * @var string
	 * @access private
	 */
	private $registration;
	
	/**
	 * Global account ID
	 * 
	 * @var int
	 * @access private
	 */
	private $id;

	/**
	 * Construction method for the GlobalUserInfo class
	 * 
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @param mixed $username Username
	 * @return void
	 */
	function __construct( &$wikiClass, $username ) {
	
		if( !array_key_exists( 'Central Auth', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "CentralAuth", "http://www.mediawiki.org/wiki/Extension:CentralAuth" );
		}
		
		$this->username = ucfirst( $username );
		$this->wiki = $wikiClass;
		
		$guiRes = $this->wiki->apiQuery(
			array(
				'action' => 'query',
				'meta' => 'globaluserinfo',
				'guiuser' => ucfirst( $username ),
				'guiprop' => 'groups|merged|unattached',
		), false, false);
		
		if( !isset( $guiRes['query']['globaluserinfo'] ) ) {
			$this->exists = false;
			if( isset( $guiRes['error'] ) && $guiRes['error']['code'] != 'guinosuchuser' ) {
				throw new APIError( $guiRes['error'] );
			}
			elseif( @$guiRes['error']['code'] != 'guinosuchuser' ) {
				throw new APIError( array( 'code' => 'UnknownError', 'info' => 'Unknown API Error' ) );
			}
		}
		else {
			$this->groups = $guiRes['query']['globaluserinfo']['groups'];
			$this->merged = $guiRes['query']['globaluserinfo']['merged'];
			$this->merged = $guiRes['query']['globaluserinfo']['unattached'];
			$this->id = $guiRes['query']['globaluserinfo']['id'];
			$this->registration = $guiRes['query']['globaluserinfo']['registration'];
		}
		
	}
	
	/**
	 * Returns the global account ID
	 * 
	 * @return int
	 * @access public
	 */
	public function get_id() {
		return $this->id;
	}
	
	/**
	 * Returns the date that global account was created
	 * 
	 * @return string
	 * @access public
	 */
	public function get_registration() {
		return $this->registration;
	}
	
	/**
	 * Returns the global groups member is a part of
	 * 
	 * @return array
	 * @access public
	 */
	public function get_groups() {
		return $this->groups;
	}
	
	/**
	 * Returns the accounts that user has merged on other wikis
	 * 
	 * @return array
	 * @access public
	 */
	public function get_merged() {
		return $this->merged;
	}
	
	/**
	 * Returns the accounts that are not attached to the global account
	 * 
	 * @return array
	 * @access public
	 */
	public function get_unattached() {
		return $this->unattached;
	}
	
	/**
	 * Returns whether or not global account exists
	 * 
	 * @return bool
	 * @access public
	 */
	public function get_exists() {
		return $this->unattached;
	}

}