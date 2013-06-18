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

class SiteMatrix {
	
	/**
	 * Loads list of all SiteMatrix wikis
	 * 
	 * @static
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @return array List of all wikis
	 */
	public static function load( &$wikiClass ) {

		if( !array_key_exists( 'SiteMatrix', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "SiteMatrix" );
		}
		
		$SMres = $wikiClass->apiQuery(array(
				'action' => 'sitematrix',
			)
		);
		
		$wikis = $SMres['sitematrix'];
		//return $wikis;
		
		$retarray = array(
			'raw' => $wikis,
			'urls' => array(),
			'langs' => array(),
			'names' => array(),
			'privates' => array()
		);
		
		foreach( $wikis as $site ) {
			if( is_array($site ) ) {
				if( isset( $site['site'])) {
				
					$retarray['langs'][] = $site['code'];
					$retarray['names'][$site['code']] = $site['name'];
					
					foreach( $site['site'] as $site2 ) {
						$retarray['urls'][] = $site2['url'];
						
						if( isset( $site2['private'] ) ) $retarray['privates'][] = $site2;
					}
				}
				else {
					foreach( $site as $site2 ) {
						$sites2['urls'][] = $site2['url'];
						
						if( isset( $site2['private'] ) ) $retarray['privates'][] = $site2;
					}
				}
			}
		}
		
		return $retarray;
		
	}

}
