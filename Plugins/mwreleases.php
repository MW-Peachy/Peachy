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

class MWReleases {

	private $versions = array( 'all' => array(), 'current' => array(), 'supported' => array(), 'beta' => array(), 'alpha' => array() );
	
	function __construct( $peachyCheck = false ) {
		if( !$peachyCheck ) {
			pecho( "Error: MWReleases initialized using new MWReleases. This is reserved for internal usage. Use MWReleases::load() instead.\n\n", PECHO_ERROR );
			return;
		}
		
		global $pgHTTP;
		
		$ret = unserialize( $pgHTTP->get( 'http://wiki.peachy.compwhizii.net/w/api.php?action=mwreleases&format=php' ) );
		if( !isset( $ret['mwreleases'] ) || !$ret ) return;
		
		foreach( $ret['mwreleases'] as $release ) {
			$this->versions['all'][] = $release['version'];
			if( isset( $release['current'] ) ) $this->versions['current'][] = $release['version'];
			if( isset( $release['supported'] ) ) $this->versions['supported'][] = $release['version'];
			if( isset( $release['beta'] ) ) $this->versions['beta'][] = $release['version'];
			if( isset( $release['alpha'] ) ) $this->versions['alpha'][] = $release['version'];
		}
	}
	
	public function isSupported( $version ) {
		return ( count( $this->versions['all'] ) ) ? in_array( $version, $this->versions['all'] ) : true;
	}
	
	public function newerVersionExists( $version ) {
		foreach( $this->versions['all'] as $theversion ) {
			if( version_compare( $theversion, $version, '>' ) ) {
				return true;
			}
		}
		return false;
	}
	
	public function get_current_version() {
		return max( $this->versions['current'] );
	}
	
	public function get_min_version() {
		return min( $this->versions['all'] );
	}
	
	/**
	 * Loads list of all SiteMatrix wikis
	 * 
	 * @static
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @param bool $peachyCheck Whether or not to check the Peachy version, used for detecting updates. You will not need to use this. Default false. 
	 * @return array List of all wikis
	 */
	public static function load( &$wikiClass, $peachyCheck = false ) {
		if( $peachyCheck ) {
			return new MWReleases( true );
		}
		
		if( !array_key_exists( 'MWReleases', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "MWReleases", "http://www.mediawiki.org/wiki/Extension:MWReleases" );
		}
		
		$SMres = $wikiClass->apiQuery(array(
				'action' => 'mwreleases',
			)
		);
		
		return $SMres['mwreleases'];
		
	}

}
