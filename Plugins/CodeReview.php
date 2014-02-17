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

class CodeReview {

	private $wiki;
	
	private $repo;

	function __construct( &$wikiClass, $repo ) {
		$this->wiki = $wikiClass;
		$this->repo = $repo;
		
		if( !array_key_exists( 'CodeReview', $wikiClass->get_extensions() ) ) {
			throw new DependencyError( "CodeReview", "http://www.mediawiki.org/wiki/Extension:CodeReview" );
		}
	}
	
	public function update( $rev ) {
	
		Hooks::runHook( 'StartCodeReviewUpdate', array( &$rev ) );
		
		$apiRes = $this->wiki->apiQuery(
			array(
				'action' => 'codeupdate',
				'repo' => $this->repo,
				'rev' => $rev
		), true);
		
		print_r($apiRes);
	}
	
	public function diff() {}
	
	public function testupload() {}
	
	public function comments() {}
	
	

}
