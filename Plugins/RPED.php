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
 
class RPED {
 
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access private
	 */
	private $wiki;
 
	/**
	 * maxURLLength
	 * Default maximum length of the URL to be posted
	 * 
	 * @var int
	 * @access private
	 */
	private $defaultMaxURLLength;
 
	/**
	 * Construction method for the RPED class
	 * 
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @return void
	 */
	function __construct( &$wikiClass ) {
		$this->wiki = $wikiClass;
		$defaultMaxURLLength = 2000;
		return;
	}
 
	/**
	 * Insert a page title into the rped_page table
	 * 
	 * @static
	 * @access public
	 * @param string $pageTitle The title of the page to insert
	 * @return void
	 */
	public function insert( $page ) {
		$this->wiki->apiQuery(
			array(
				'action' => 'rped',
				'insert' => $page,
		), true );
	}
 
	/**
	* Delete a page title from the rped_page table
	 * 
	 * @static
	 * @access public
	 * @param string $pageTitle The title of the page to insert
	 * @return void
	 */
	public function delete( $page ) {
		$this->wiki->apiQuery(
			array(
				'action' => 'rped',
				'delete' => $page,
		), true );
	}
 
	/**
	 * Insert/delete an array of page titles into/from the rped_page table
	 * 
	 * @static
	 * @access public
	 * @param string $command Either 'insert' or 'delete'
	 * @param array $pageArray The array of page title to insert
	 * @param int $maxURLLength The maximum length of the url to be POSTed
	 * @return void
	 */
	public function insertOrDeleteArray( $command, $pageArray, $maxURLLength = 0) {	
		if ( $command != 'insert' && $command != 'delete' )
		{
			die('Something tried to call insertOrDeleteArray without'
			    .'specifying an insert or delete command.' );
		}
		if ( $maxURLLength == 0 ) {
			$maxURLLength = $this->defaultMaxURLLength;
		}
		$line = '';
		foreach ( $pageArray as $page ) {
			if ( $line != '' ) {
				$line .= '|';
			}
			if ( strlen( $line ) + strlen( $page ) > $maxURLLength ) {
				if ( $command == 'delete' ) {
					$this->delete( $line );	
				} else {
					$this->insert( $line );
				}
				$line = '';
			}
			$line .= $page;
		}
		if ( $command == 'delete' ) {
			$this->delete( $line );	
		} else {
			$this->insert( $line );
		}
	}
 
	/**
	 * Insert an array of page titles into/from the rped_page table
	 * 
	 * @static
	 * @access public
	 * @param array $pageArray The array of page title to insert
	 * @param int $maxURLLength The maximum length of the url to be POSTed
	 * @return void
	 */
	public function insertArray( $pageArray, $maxURLLength = 0) {
		$this->insertOrDeleteArray( 'insert', $pageArray, $maxURLLength );
	}
 
	/**
	 * Delete an array of page titles from the rped_page table
	 * 
	 * @static
	 * @access public
	 * @param array $pageArray The array of page title to insert
	 * @param int $maxURLLength The maximum length of the url to be POSTed
	 * @return void
	 */
	public function deleteArray( $pageArray, $maxURLLength = 0) {
		$this->insertOrDeleteArray( 'delete', $pageArray, $maxURLLength );
	}
}
