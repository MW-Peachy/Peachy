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

class AbuseFilter {
	
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access private
	 */
	private $wiki;
	
	/**
	 * Construction method for the AbuseFilter class
	 * 
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @return void
	 */
	function __construct( &$wikiClass ) {
		$this->wiki = $wikiClass;
		
		if( !array_key_exists( 'Abuse Filter', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "AbuseFilter", "http://www.mediawiki.org/wiki/Extension:AbuseFilter" );
		}
	}
	
	/**
	 * Returns the abuse filter log
	 * 
	 * @access public
	 * @param int $filter Filter ID. Default null
	 * @param string $user Show entries by this user. Default null
	 * @param string $title Show entries to this title. Default null
	 * @param int $limit Number of entries to retrieve. Defautl null
	 * @param string $start Timestamp to start at. Default null
	 * @param string $end Timestamp to end at. Default null
	 * @param string $dir Direction to list. Default older
	 * @param array $prop Properties to retrieve. Default array( 'ids', 'filter', 'user', 'ip', 'title', 'action', 'details', 'result', 'timestamp' )
	 * @return array
	 */
	public function abuselog( $filter = null, $user = null, $title = null, $limit = null, $start = null, $end = null, $dir = 'older', $prop = array( 'ids', 'filter', 'user', 'ip', 'title', 'action', 'details', 'result', 'timestamp' ) ) {

		$tArray = array(
			'prop' => $prop,
			'_code' => 'afl',
			'afldir' => $dir,
			'_limit' => $limit,
			'list' => 'abuselog',
		);
		
		if( !is_null( $filter ) ) $tArray['aflfilter'] = $filter;
		if( !is_null( $user ) ) $tArray['afluser'] = $user;
		if( !is_null( $title ) ) $tArray['afltitle'] = $title;
		if( !is_null( $start ) ) $tArray['aflstart'] = $start;
		if( !is_null( $end ) ) $tArray['aflend'] = $end;
		
		pecho( "Getting abuse log...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler($tArray);
	}
	
	/**
	 * Returns a list of all filters
	 * 
	 * @access public
	 * @param int $start Filter ID to start at. Default null
	 * @param int $end Filter ID to end at. Default null
	 * @param string $dir Direction to list. Default newer
	 * @param bool $enabled Only list enabled filters. true => only enabled, false => only disabled, null => all
	 * @param bool $deleted Only list deleted filters. true => only deleted, false => only non-deleted, null => all
	 * @param bool $private Only list private filters. true => only private, false => only non-private, null => all
	 * @param int $limit Number of filters to get. Default null
	 * @param array $prop Properties to retrieve. Default array( 'id', 'description', 'pattern', 'actions', 'hits', 'comments', 'lasteditor', 'lastedittime', 'status', 'private' )
	 * @return array
	 */
	public function abusefilters( $start = null, $end = null, $dir = 'newer', $enabled = null, $deleted = false, $private = null, $limit = null, $prop = array( 'id', 'description', 'pattern', 'actions', 'hits', 'comments', 'lasteditor', 'lastedittime', 'status', 'private' ) ) {
		
		$tArray = array(
			'prop' => $prop,
			'_code' => 'abf',
			'abfdir' => $dir,
			'_limit' => $limit,
			'abfshow' => array(),
			'list' => 'abusefilters'
		);
		
		if( !is_null( $enabled ) ) {
			if( $enabled ) {
				$tArray['abfshow'][] = 'enabled';
			}
			else {
				$tArray['abfshow'][] = '!enabled';
			}
		}
		
		if( !is_null( $deleted ) ) {
			if( $deleted ) {
				$tArray['abfshow'][] = 'deleted';
			}
			else {
				$tArray['abfshow'][] = '!deleted';
			}
		}
		
		if( !is_null( $private ) ) {
			if( $private ) {
				$tArray['abfshow'][] = 'private';
			}
			else {
				$tArray['abfshow'][] = '!private';
			}
		}
		
		$tArray['abfshow'] = implode( '|', $tArray['abfshow'] );
		
		if( !is_null( $start ) ) $tArray['abfstartid'] = $start;
		if( !is_null( $end ) ) $tArray['abfendid'] = $end;
		
		pecho( "Getting abuse filter list...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler($tArray);
	}
	
	

}