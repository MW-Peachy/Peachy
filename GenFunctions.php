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
 * @file
 * Stores general functions that do not belong in a class
 */

/**
 * Case insensitive in_array function
 *
 * @param mixed $needle What to search for
 * @param array $haystack Array to search in
 * @param bool $strict
 * @return bool True if $needle is found in $haystack, case insensitive
 * @link http://us3.php.net/in_array
 */
function iin_array( $needle, $haystack, $strict = false ) {
	return in_array_recursive( strtoupper_safe( $needle ), array_map( 'strtoupper_safe', $haystack ), $strict );
}

/**
 * @return string
 */
function strtoupper_safe( $str ) {
	if( is_string( $str ) ) return strtoupper( $str );
	if( is_array( $str ) ) $str = array_map( 'strtoupper_safe', $str );
	return $str;
}

/**
 * Returns whether or not a string is found in another
 * Shortcut for strpos()
 *
 * @param string $needle What to search for
 * @param string $haystack What to search in
 * @param bool $insensitive Whether or not to do a case-insensitive search
 * @return bool True if $needle is found in $haystack
 * @link http://us3.php.net/strpos
 */
function in_string( $needle, $haystack, $insensitive = false ) {
	$fnc = 'strpos';
	if( $insensitive ) $fnc = 'stripos';

	return $fnc( $haystack, $needle ) !== false;
}

/**
 * Recursive in_array function
 *
 * @param string $needle What to search for
 * @param array $haystack What to search in
 * @param bool $insensitive Whether or not to do a case-insensitive search
 * @return bool True if $needle is found in $haystack
 * @link http://us3.php.net/in_array
 */
function in_array_recursive( $needle, $haystack, $insensitive = false ) {
	$fnc = 'in_array';
	if( $insensitive ) $fnc = 'iin_array';

	if( $fnc( $needle, $haystack ) ) return true;
	foreach( $haystack as $val ){
		if( is_array( $val ) ) {
			return in_array_recursive( $needle, $val );
		}
	}
	return false;
}

/**
 * Recursive glob() function.
 *
 * @access public
 * @param string $pattern . (default: '*')
 * @param int $flags . (default: 0)
 * @param string $path . (default: '')
 * @return void
 */
function rglob( $pattern = '*', $flags = 0, $path = '' ) {
	$paths = glob( $path . '*', GLOB_MARK | GLOB_ONLYDIR | GLOB_NOSORT );
	$files = glob( $path . $pattern, $flags );
	foreach( $paths as $path ){
		$files = array_merge( $files, rglob( $pattern, $flags, $path ) );
	}
	return $files;
}

/**
 * Detects the presence of a nobots template or one that denies editing by ours
 *
 * @access public
 * @param Wiki $wiki Wiki class
 * @param string $text Text of the page to check (default: '')
 * @param string $username Username to search for in the template (default: null)
 * @param string $optout Text to search for in the optout= parameter. (default: null)
 * @return bool True on match of an appropriate nobots template
 */
function checkExclusion( Wiki $wiki, $text = '', $username = null, $optout = null ) {
	if( !$wiki->get_nobots() ) return false;

	if( in_string( "{{nobots}}", $text ) ) return true;
	if( in_string( "{{bots}}", $text ) ) return false;

	if( preg_match( '/\{\{bots\s*\|\s*allow\s*=\s*(.*?)\s*\}\}/i', $text, $allow ) ) {
		if( $allow[1] == "all" ) return false;
		if( $allow[1] == "none" ) return true;
		$allow = array_map( 'trim', explode( ',', $allow[1] ) );
		if( !is_null( $username ) && in_array( trim( $username ), $allow ) ) {
			return false;
		}
		return true;
	}

	if( preg_match( '/\{\{(no)?bots\s*\|\s*deny\s*=\s*(.*?)\s*\}\}/i', $text, $deny ) ) {
		if( $deny[2] == "all" ) return true;
		if( $deny[2] == "none" ) return false;
		$allow = array_map( 'trim', explode( ',', $deny[2] ) );
		if( !is_null( $username ) && in_array( trim( $username ), $allow ) ) {
			return true;
		}
		return false;
	}

	if( !is_null( $optout ) && preg_match( '/\{\{(no)?bots\s*\|\s*optout\s*=\s*(.*?)\s*\}\}/i', $text, $allow ) ) {
		if( $allow[1] == "all" ) return true;
		$allow = array_map( 'trim', explode( ',', $allow[2] ) );
		if( in_array( trim( $optout ), $allow ) ) {
			return true;
		}
		return false;
	}
	return false;
}

/**
 * Outputs text if the given category is in the allowed types
 *
 * @param string $text Text to display
 * @param int $cat Category of text, such as PECHO_WARN, PECHO_NORMAL
 * @param string $func
 * @return void
 */
function outputText( $text, $cat = 0, $func = 'echo' ) {
	global $pgVerbose;

	Hooks::runHook( 'OutputText', array( &$text, &$cat, &$func ) );

	if( in_array( $cat, $pgVerbose ) ) {
		if( $func == 'echo' ) {
			echo $text;
		} else {
			$func( $text );
		}
	}
}

/**
 * Shortcut for {@link outputText}
 *
 * @param string $text Text to display
 * @param int $cat Category of text, such as PECHO_WARN, PECHO_NORMAL
 * @param string $func
 * @link outputText
 * @return void
 */
function pecho( $text, $cat = 0, $func = 'echo' ) {
	global $webOutput;
	if( $webOutput ) $text = str_replace( "\n", "<br>", $text );
	outputText( $text, $cat, $func );
}

/**
 * Echo function with color capabilities.
 *
 * Syntax:
 *
 * <i>[Text to colorize|NAME] where NAME is the name of a defined style.</i> For example:
 *
 * <i>This text is standard terminal color. [This text will be yellow.|COMMENT] [This text will be white on red.|ERROR]</i>
 *
 * Defined styles:
 * <ul>
 * <li>ERROR: White on red, bold</li>
 * <li>INFO: Green text, bold</li>
 * <li>PARAMETER: Cyan text</li>
 * <li>COMMENT: Yellow text</li>
 * <li>GREEN_BAR: White on green, bold</li>
 * <li>RED_BAR: White on red, bold</li>
 * <li>YELLOW_BAR: Black on yellow, bold</li>
 * <li>INFO_BAR: Cyan text, bold</li>
 * </ul>
 *
 * You can define your own styles by using this syntax:
 *
 *   <code>lime_colorizer::style('STYLE_NAME', array('bg' => 'red', 'fg' => 'white'));</code>
 *
 * (Available colors: black, red, green, yellow, blue, magenta, cyan, white)
 *
 * You can also set options for how the text is formatted (not available on all systems):
 *
 *   <code>lime_colorizer::style('STYLE_NAME', array('bg' => 'red', 'fg' => 'white', 'bold' => true ));</code> (sets bold text)
 *
 * Available options: bold, underscore, blink, reverse, conceal
 *
 *
 * @access public
 * @param string $text
 * @return void
 */
function cecho( $text, $return = false ) {
	global $pgColorizer;

	if( !isset( $pgColorizer ) ) $pgColorizer = new lime_colorizer( true );

	$text = preg_replace( '/\[(.+?)\|(\w+)\]/se', '$pgColorizer->colorize("$1", "$2")', $text );

	if( $return ) return $text;

	echo $text;

}


/**
 * Generates a diff between two strings
 *
 * @package Text_Diff
 * @deprecated since 18 June 2013
 */
function getTextDiff() {
	Peachy::deprecatedWarn( 'getTextDiff()', 'Diff::load()' );
	$args = func_get_args();
	return call_user_func_array( array( 'Diff', 'load' ), $args );
}

/**
 * Gets the first defined Wiki object
 *
 * @return Wiki|bool
 * @package initFunctions
 */
function &getSiteObject() {

	foreach( $GLOBALS as $var ){
		if( is_object( $var ) ) {
			if( get_class( $var ) == "Wiki" ) {
				return $var;
			}
		}
	}

	return false;
}

/**
 * Returns an instance of the Page class as specified by $title or $pageid
 *
 * @param mixed $title Title of the page (default: null)
 * @param mixed $pageid ID of the page (default: null)
 * @param bool $followRedir Should it follow a redirect when retrieving the page (default: true)
 * @param bool $normalize Should the class automatically normalize the title (default: true)
 * @return Page
 * @package initFunctions
 */
function &initPage( $title = null, $pageid = null, $followRedir = true, $normalize = true ) {
	$wiki = getSiteObject();
	if( !$wiki ) return false;

	$page = new Page( $wiki, $title, $pageid, $followRedir, $normalize );
	return $page;
}

/**
 * Returns an instance of the User class as specified by $username
 *
 * @param mixed $username Username
 * @return User
 * @package initFunctions
 */
function &initUser( $username ) {
	$wiki = getSiteObject();
	if( !$wiki ) return false;

	$user = new User( $wiki, $username );
	return $user;
}

/**
 * Returns an instance of the Image class as specified by $filename or $pageid
 *
 * @param string $filename Filename
 * @return Image
 * @package initFunctions
 */
function &initImage( $filename = null ) {

	$wiki = getSiteObject();
	if( !$wiki ) return false;

	$image = new Image( $wiki, $filename );
	return $image;
}

if( !function_exists( 'mb_strlen' ) ) {

	/**
	 * Fallback implementation of mb_strlen.
	 *
	 * @link http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/includes/GlobalFunctions.php
	 * @param string $str String to get
	 * @return int
	 * @package Fallback
	 */
	function mb_strlen( $str ) {
		$counts = count_chars( $str );
		$total = 0;

		// Count ASCII bytes
		for( $i = 0; $i < 0x80; $i++ ){
			$total += $counts[$i];
		}

		// Count multibyte sequence heads
		for( $i = 0xc0; $i < 0xff; $i++ ){
			$total += $counts[$i];
		}

		return $total;
	}
}

if( !function_exists( 'mb_substr' ) ) {
	/**
	 * Fallback implementation for mb_substr. This is VERY slow, from 5x to 100x slower. Use only if necessary.
	 * @link http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/includes/GlobalFunctions.php
	 * @package Fallback
	 */
	function mb_substr( $str, $start, $count = 'end' ) {
		if( $start != 0 ) {
			$split = mb_substr_split_unicode( $str, intval( $start ) );
			$str = substr( $str, $split );
		}

		if( $count !== 'end' ) {
			$split = mb_substr_split_unicode( $str, intval( $count ) );
			$str = substr( $str, 0, $split );
		}

		return $str;
	}

	/**
	 * Continuing support for mb_substr. Do not use.
	 * @link http://svn.wikimedia.org/svnroot/mediawiki/trunk/phase3/includes/GlobalFunctions.php
	 * @package Fallback
	 * @param integer $splitPos
	 */
	function mb_substr_split_unicode( $str, $splitPos ) {
		if( $splitPos == 0 ) {
			return 0;
		}

		$byteLen = strlen( $str );

		if( $splitPos > 0 ) {
			if( $splitPos > 256 ) {
				// Optimize large string offsets by skipping ahead N bytes.
				// This will cut out most of our slow time on Latin-based text,
				// and 1/2 to 1/3 on East European and Asian scripts.
				$bytePos = $splitPos;
				while( $bytePos < $byteLen && $str{$bytePos} >= "\x80" && $str{$bytePos} < "\xc0" ){
					++$bytePos;
				}
				$charPos = mb_strlen( substr( $str, 0, $bytePos ) );
			} else {
				$charPos = 0;
				$bytePos = 0;
			}

			while( $charPos++ < $splitPos ){
				++$bytePos;
				// Move past any tail bytes
				while( $bytePos < $byteLen && $str{$bytePos} >= "\x80" && $str{$bytePos} < "\xc0" ){
					++$bytePos;
				}
			}
		} else {
			$splitPosX = $splitPos + 1;
			$charPos = 0; // relative to end of string; we don't care about the actual char position here
			$bytePos = $byteLen;
			while( $bytePos > 0 && $charPos-- >= $splitPosX ){
				--$bytePos;
				// Move past any tail bytes
				while( $bytePos > 0 && $str{$bytePos} >= "\x80" && $str{$bytePos} < "\xc0" ){
					--$bytePos;
				}
			}
		}

		return $bytePos;
	}
}

if( !function_exists( 'iconv' ) ) {
	/**
	 * Fallback iconv function.
	 *
	 * iconv support is not in the default configuration and so may not be present.
	 * Assume will only ever use utf-8 and iso-8859-1.
	 * This will *not* work in all circumstances.
	 *
	 * @access public
	 * @param mixed $from
	 * @param mixed $to
	 * @param mixed $string
	 * @return void
	 * @package Fallback
	 */
	function iconv( $from, $to, $string ) {
		if( substr( $to, -8 ) == '//IGNORE' ) $to = substr( $to, 0, strlen( $to ) - 8 );
		if( strcasecmp( $from, $to ) == 0 ) return $string;
		if( strcasecmp( $from, 'utf-8' ) == 0 ) return utf8_decode( $string );
		if( strcasecmp( $to, 'utf-8' ) == 0 ) return utf8_encode( $string );
		return $string;
	}
}

if( !function_exists( 'istainted' ) ) {

	/**
	 * Fallback istainted function.
	 *
	 * @access public
	 * @param mixed $var
	 * @return integer
	 * @package Fallback
	 */
	function istainted( $var ) {
		return 0;
	}

	/**
	 * Fallback taint function.
	 *
	 * @access public
	 * @param mixed $var
	 * @param int $level
	 * @return void
	 * @package Fallback
	 */
	function taint( $var, $level = 0 ) { }

	/**
	 * Fallback untaint function.
	 *
	 * @access public
	 * @param mixed $var
	 * @param int $level
	 * @return void
	 * @package Fallback
	 */
	function untaint( $var, $level = 0 ) { }

	/**
	 * @package Fallback
	 */
	define( 'TC_HTML', 1 );

	/**
	 * @package Fallback
	 */
	define( 'TC_SHELL', 1 );

	/**
	 * @package Fallback
	 */
	define( 'TC_MYSQL', 1 );

	/**
	 * @package Fallback
	 */
	define( 'TC_PCRE', 1 );

	/**
	 * @package Fallback
	 */
	define( 'TC_SELF', 1 );
}
