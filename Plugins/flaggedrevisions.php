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

class FlaggedRevs {
	
	private $wiki;

	function __construct( &$wikiClass ) {
	
		if( !array_key_exists( 'FlaggedRevs', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "FlaggedRevs", "http://www.mediawiki.org/wiki/Extension:FlaggedRevs" );
		}
		
		$this->wiki = $wikiClass;
	}
	
	public function review( $revid, $reason = null, $status = 1 ) {
		
		if( !in_array( 'review', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to review revisions", PECHO_FATAL );
			return false;
		}
		
		$tokens = $this->wiki->get_tokens();
		
		if( $tokens['edit'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		
		if( mb_strlen( $reason, '8bit' ) > 255 ) {
			pecho( "Comment is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
			return false;
		}
		
		pecho( "Reviewing $revid...\n\n", PECHO_NOTICE );
		
		$editarray = array(
			'flag_accuracy' => $status,
			'action' => 'review',
			'token' => $tokens['edit'],
			'revid' => $revid
		);
		
		if( !empty( $reason ) ) $editArray['comment'] = $reason;
		
		if( $this->wiki->get_maxlag() ) {
			$editarray['maxlag'] = $this->wiki->get_maxlag();
		}
		
		Hooks::runHook( 'StartReview', array( &$editarray ) );
		
		$result = $this->wiki->apiQuery( $editarray, true );
		
		if( isset( $result['review'] ) ) {
			if( isset( $result['review']['revid'] ) ) {
				return true;
			}
			else {
				pecho( "Review error...\n\n" . print_r($result['review'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Review error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	
	public function stabilize( $title, $level = 'none', $reason = null, $autoreview = false, $watch = false ) {
		
		if( !in_array( 'stablesettings', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to change the stabilization settings", PECHO_FATAL );
			return false;
		}
		
		$tokens = $this->wiki->get_tokens();
		
		if( $tokens['edit'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		
		if( mb_strlen( $reason, '8bit' ) > 255 ) {
			pecho( "Comment is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
			return false;
		}
		
		pecho( "Stabilizing title...\n\n", PECHO_NOTICE );
		
		$editarray = array(
			'action' => 'review',
			'title' => $title,
			'token' => $tokens['edit'],
			'protectlevel' => $level
		);
		
		if( $watch ) $editArray['watch'] = 'yes';
		if( !empty( $reason ) ) $editArray['reason'] = $reason;
		
		if( $this->wiki->get_maxlag() ) {
			$editarray['maxlag'] = $this->wiki->get_maxlag();
		}
		
		Hooks::runHook( 'StartStabilize', array( &$editarray ) );
		
		$result = $this->wiki->apiQuery( $editarray, true );
		
		if( isset( $result['stabilize'] ) ) {
			if( isset( $result['stabilize']['title'] ) ) {
				return true;
			}
			else {
				pecho( "Stabilization error...\n\n" . print_r($result['stabilize'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Stabilization error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
		
	}
	
	public function flagconfig() {}
	
	public function reviewedpages() {}
	
	public function unreviewedpages() {}
	
	public function oldreviewedpages() {}
	
	
	

}

