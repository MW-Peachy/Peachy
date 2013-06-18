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
 * Script object
 */

/**
 * Script class, contains methods that make writing scripts easier
 */
class Script {
	
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access protected
	 */
	protected $wiki;
	
	/**
	 * Arguments passed to the CLI
	 * 
	 * @var array
	 * @access protected
	 */
	protected $args;
	
	/**
	 * List of pages specified by the -xml:, -file:, -list:, etc parameters
	 * 
	 * @var array
	 * @access protected
	 */
	protected $list = array();
	
	/**
	 * Initializes the Script class, creates the Wiki object with the parameters passed to the CLI
	 * 
	 * @param array $argfunctions List of callback functions to run when an argument is passed. Default array(). 
	 * @return void
	 */
	function __construct( $argfunctions = array() ) {
		global $argv, $pgHooks, $pgIP;
		$this->parseArgs( $argv, $argfunctions );
		
		$pgHooks['StartLogin'][] = array( $this, 'fixConfig' );

		if( isset( $this->args['config'] ) ) {
			$this->wiki = Peachy::newWiki( $this->args['config'] );
		}
		else {
			if( isset( $this->args['username'] ) ) {
				if( isset( $this->args['password'] ) ) {
					if( isset( $this->args['baseurl'] ) ) {
						$this->wiki = Peachy::newWiki( null, $this->args['username'], $this->args['password'], $this->args['baseurl'] );
					}
					else {
						$this->wiki = Peachy::newWiki( null, $this->args['username'], $this->args['password'] );
					}
				}
				else {
					if( isset( $this->args['baseurl'] ) ) {
						$this->wiki = Peachy::newWiki( null, $this->args['username'], null, $this->args['baseurl'] );
					}
					else {
						$this->wiki = Peachy::newWiki( null, $this->args['username'] );
					}
				}
			}
			else {
				if( isset( $this->args['baseurl'] ) ) {
					$this->wiki = Peachy::newWiki( null, null, null, $this->args['baseurl'] );
				}
				else {
					if( is_file( $pgIP . 'Configs/scriptdefault.cfg' ) ) {
						$this->wiki = Peachy::newWiki( 'scriptdefault' );
					}
					else {
						$this->wiki = Peachy::newWiki();
					}
				}
			}
		}
		
		$this->makeList();
		
		if( count( $argfunctions ) ) {
			$this->runArgs( $argfunctions );
		}
		
	}
	
	/**
	 * Fills in the $this->args array
	 * 
	 * @access protected
	 * @param array $args Arguments passed to the CLI
	 * @return void
	 */
	protected function parseArgs( $args ) {
		foreach( $args as $arg ) {
			$tmp = explode( ':', $arg, 2 );
			
			if( $tmp[1] == '"' && substr( $tmp[1], -1 ) == '"' ) $tmp[1] = substr( $tmp[1], 1, strlen( $tmp[1] ) - 2 );
			
			if( $arg[0] == "-" ) $this->args[ substr( $tmp[0], 1 ) ] = $tmp[1];
			if( substr( $arg, 0, 2 ) == '--' ) $this->args[ substr( $tmp[0], 2 ) ] = $tmp[1];
			
		}
	}
	
	/**
	 * Runs the argument callbacks specified in the constructor
	 * 
	 * @access protected
	 * @param array $argfunctions List of callbacks
	 * @return void
	 */
	protected function runArgs( $argfunctions ) {
		foreach( $argfunctions as $arg => $callback ) {
			if( is_callable( $callback ) ) {
				call_user_func_array( $callback, $this->args[$arg] );
			}
		}
	}
	
	/**
	 * Parses the xml, file, list, etc arguments to fill in the $this->list variable
	 * 
	 * @access protected
	 * @return void
	 * @todo XML parsing needs to be improved
	 */
	protected function makeList() {
		if( isset( $this->args['xml'] ) ) {
			
			$this->list = XML::load( file_get_contents( $this->args['xml'] ) );
		}
		elseif( isset( $this->args['file'] ) ) {
			$this->list = explode( "\n", file_get_contents( $this->args['file'] ) );
		}
		elseif( isset( $this->args['list'] ) ) {
			$this->list = explode( "|", $this->args['list'] );
		}
		elseif( isset( $this->args['cat'] ) ) {
			if( isset( $this->args['recurse'] ) ) {
				$this->list = $this->wiki->categorymembers( $this->args['cat'], true );
			}
			else {
				$this->list = $this->wiki->categorymembers( $this->args['cat'] );
			}
		}
		
	}
	
	/**
	 * Returns the argument, or false if it was not specified
	 * 
	 * @access public
	 * @param string $arg Argument to retrieve
	 * @return string|bool
	 */
	public function getArg( $arg ) {
		if( isset( $this->args[$arg] ) ) {
			return $this->args[$arg];
		}
		return false;
	}
	
	/**
	 * Returns the list created in the {@link makeList} function, or false if no list.
	 * 
	 * @access public
	 * @return array|bool
	 */
	public function getList() {
		if( count( $this->list ) ) {
			return $this->list;
		}
		return false;
	}
	
	/**
	 * Returns the page specified in the --page param, or false if not specified.
	 * 
	 * @access public
	 * @return string|bool
	 */
	public function getPage() {
		if( $this->getArg( 'page' ) ) {
			return $this->getArg( 'page' );
		}
		return false;
	}
	
	/**
	 * Returns the wiki class specified in the constructor
	 * 
	 * @access public
	 * @return Wiki Instance of the Wiki class
	 */
	public function &getWiki() {
		return $this->wiki;
	}
	
	/**
	 * Hook to specify config params specified in arguments
	 * 
	 * @access public
	 * @param &$configs Config params
	 * @return void
	 */
	public function fixConfig( &$configs ) {
		if( $this->getArg( 'epm' ) ) $configs['editsperminute'] = $this->getArg( 'epm' );
		if( $this->getArg( 'httpecho' ) ) $configs['httpecho'] = "true";
		if( $this->getArg( 'runpage' ) ) $configs['runpage'] = $this->getArg( 'runpage' );
		if( $this->getArg( 'optout' ) ) $configs['optout'] = $this->getArg( 'optout' );
		if( $this->getArg( 'stoponnewmessages' ) ) $configs['stoponnewmessages'] = 'true';
		if( $this->getArg( 'verbose' ) ) $configs['verbose'] = $this->getArg( 'verbose' );
		if( $this->getArg( 'nobots' ) ) $configs['nobots'] = $this->getArg( 'nobots' );
		if( $this->getArg( 'maxlag' ) ) $configs['maxlag'] = $this->getArg( 'maxlag' );
	}
}
