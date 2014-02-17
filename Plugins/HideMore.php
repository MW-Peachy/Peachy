<?php

/*

	This program is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.

	THIS SOFTWARE AND DOCUMENTATION IS PROVIDED "AS IS," AND COPYRIGHT
	HOLDERS MAKE NO REPRESENTATIONS OR WARRANTIES, EXPRESS OR IMPLIED,
	INCLUDING BUT NOT LIMITED TO, WARRANTIES OF MERCHANTABILITY OR
	FITNESS FOR ANY PARTICULAR PURPOSE OR THAT THE USE OF THE SOFTWARE
	OR DOCUMENTATION WILL NOT INFRINGE ANY THIRD PARTY PATENTS,
	COPYRIGHTS, TRADEMARKS OR OTHER RIGHTS.COPYRIGHT HOLDERS WILL NOT
	BE LIABLE FOR ANY DIRECT, INDIRECT, SPECIAL OR CONSEQUENTIAL
	DAMAGES ARISING OUT OF ANY USE OF THE SOFTWARE OR DOCUMENTATION.

	You should have received a copy of the GNU General Public License
	along with this program. If not, see <http://gnu.org/licenses/>.

*/

class HideMore {
	
	private $hideExternalLinks = true;
	private $leaveMetaHeadings = true;
	
	private $noWikiIgnoreRegex = '<!-- ?(cat(egories)?|\\{\\{.*?stub\\}\\}.*?|other languages?|language links?|inter ?(language|wiki)? ?links|inter ?wiki ?language ?links|inter ?wikis?|The below are interlanguage links\\.?) ?-->';
	
	private $hiddenTokens = array();
	
	public function __construct( $hideExternalLinks = true, $leaveMetaHeadings = true ) {
		$this->hideExternalLinks = $hideExternalLinks;
		$this->leaveMetaHeadings = $leaveMetaHeadings;
	}
	
	private function replace( $matches, $text ) {
		
		$pos = 0;
		$ret = null;
		
		foreach( $matches as $m ) {
			
			$ret .= substr( $text, $pos, $m[1] - $pos );
			
			$ret .= "⌊⌊⌊⌊" . count( $this->hiddenTokens ) . "⌋⌋⌋⌋";
			
			$pos = $m[1] + strlen( $m[0] );
			
			$this->hiddenTokens["⌊⌊⌊⌊" . count( $this->hiddenTokens ) . "⌋⌋⌋⌋"] = $m[0];
			
		}
		
		$ret .= substr( $text, $pos, strlen( $text ) - $pos );
		
		return $ret;
		
	}
	
	public function hide( $text ) {
		$this->hiddenTokens = array();
		
		preg_match_all( '/<\s*source(?:\s.*?|)>(.*?)<\s*\/\s*source\s*>/miS', $text, $sources, PREG_OFFSET_CAPTURE );
		
		$text = $this->replace( $sources[0], $text );
		
		
		
		preg_match_all( '/<nowiki>.*?<\/\s*nowiki>|<pre\b.*?>.*?<\/\s*pre>|<math\b.*?>.*?<\/\s*math>|<!--.*?-->|<timeline\b.*?>.*?<\/\s*timeline>/miS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		
		foreach( $unformatted[0] as $id => $match ) {
			if( $this->leaveMetaHeadings && preg_match( '/<!-- ?(cat(egories)?|\{\{.*?stub\}\}.*?|other languages?|language links?|inter ?(language|wiki)? ?links|inter ?wiki ?language ?links|inter ?wikis?|The below are interlanguage links\.?) ?-->/iS', $match[0] ) ) unset( $unformatted[0][$id] );
		}
		$text = $this->replace( $unformatted[0], $text );
		
		if( $this->hideExternalLinks ) {
			preg_match_all( '/(https?|ftp|mailto|irc|gopher|telnet|nntp|worldwind|news|svn):\/\/(?:[\w\._\-~!\/\*""\'\(\):;@&=+$,\?%#\[\]]+?(?=}})|[\w\._\-~!\/\*""\'\(\):;@&=+$,\?%#\[\]]*)|\[(https?|ftp|mailto|irc|gopher|telnet|nntp|worldwind|news|svn):\/\/.*?\]/iS', $text, $externallinks, PREG_OFFSET_CAPTURE );
			$text = $this->replace( $externallinks[0], $text );
			
		}
		
		unset( $unformatted );
		preg_match_all( '/<\s*blockquote\s*>(.*?)<\s*\/\s*blockquote\s*>|<\s*poem\s*>(.*?)<\s*\/\s*poem\s*>|<\s*source(?:\s.*?|)>(.*?)<\s*\/\s*source\s*>|<\s*code\s*>(.*?)<\s*\/\s*code\s*>|<\s*noinclude\s*>(.*?)<\s*\/\s*noinclude\s*>|<\s*includeonly\s*>(.*?)<\s*\/\s*includeonly\s*>|<nowiki>.*?<\/\s*nowiki>|<pre\b.*?>.*?<\/\s*pre>|<math\b.*?>.*?<\/\s*math>|<!--.*?-->|<timeline\b.*?>.*?<\/\s*timeline>/miS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		unset( $unformatted );
		preg_match_all( '/^(=+)(.*?)(=+)/miS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		unset( $unformatted );
		preg_match_all( '/^:.*/miS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		unset( $unformatted );
		preg_match_all( '/\[\[[^\[\]\n]+\]\](\w+)/iS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		unset( $unformatted );
		preg_match_all( '/<cite[^>]*?>[^<]*<\s*\/cite\s*>/iS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		unset( $unformatted );
		preg_match_all( '/(<ref\s+name\s*=\s*[^<>]*?\/\s*>|<ref\b[^>\/]*?>.*?<\s*\/\s*ref\s*>)/iS', $text, $unformatted, PREG_OFFSET_CAPTURE );
		$text = $this->replace( $unformatted[0], $text );
		
		return $text;
	}

	public function addBack( $text ) {
		
		preg_match_all( '/⌊⌊⌊⌊(\d*)⌋⌋⌋⌋/S', $text, $mc, PREG_OFFSET_CAPTURE );
		
		$pos = 0;
		$ret = null;
		
		foreach( $mc[0] as $i => $m ) {
			
			$ret .= substr( $text, $pos, $m[1] - $pos );
			$ret .= $this->hiddenTokens["⌊⌊⌊⌊" . $i . "⌋⌋⌋⌋"];
			
			$pos = $m[1] . strlen( $m[0] );
			
			$this->hiddenTokens["⌊⌊⌊⌊" . count( $this->hiddenTokens ) . "⌋⌋⌋⌋"] = $m[0];
			
		}
		
		$ret .= substr( $text, $pos, strlen( $text ) - $pos );
		
		$this->hiddenTokens = array();
		
		return $ret;
		
	}

}
