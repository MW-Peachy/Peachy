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


class YAML {
	
	/**
	 * __construct function.
	 * 
	 * @access public
	 * @param mixed $data. (default: null)
	 * @return void
	 */
	function __construct($data = null) {
		$version = peachyCheckPHPVersion();
		if( $version[1] < 2 || ( $version[1] == 2 && $version[2] < 4 ) ) throw new DependancyError( "PHP 5.2.4", "http://php.net/downloads.php" );
		$this->data = $data;
	}
	
	/**
	 * load function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $data
	 * @return void
	 */
	public static function load($data) {
		return new YAML($data);
	}
	
	/**
	 * parse function.
	 * 
	 * @access public
	 * @static
	 * @param mixed $data
	 * @return void
	 */
	public static function parse($data,$indent = 5) {
		return self::__invoke($data,$indent);
	}
	
	/**
	 * toArray function.
	 * 
	 * @access public
	 * @return void
	 */
	public function toArray() {
		global $pgAutoloader;
		$pgAutoloader['sfYamlParser'] = 'Plugins/yaml/sfYamlParser.php';
		
		if( !is_string($this->data) ) {
			throw new BadEntryError( 'wrongtype', 'YAML::toArray() can only be used with a string' );
		}
		
		try {
			$yaml = new sfYamlParser();
			$parsed = $yaml->parse($this->data);
		}
		catch( Exception $e ) {
			throw new BadEntryError( 'badyaml', sprintf( 'YAML::toArray() needs a valid YAML string: %s', $e->getMessage() ) );
		}
		
		return $parsed;
		
	}
	
	/**
	 * toYaml function.
	 * 
	 * @access public
	 * @return void
	 */
	public function toYaml( $indent = 5 ) {
		global $pgAutoloader;
		$pgAutoloader['sfYamlDumper'] = 'Plugins/yaml/sfYamlDumper.php';
		
		if( !is_array($this->data) ) {
			throw new BadEntryError( 'wrongtype', 'YAML::toYaml() can only be used with an array' );
		}
		
		try {
			$yaml = new sfYamlDumper();
			$parsed = $yaml->dump( $this->data, $indent );
		}
		catch( Exception $e ) {
			throw new BadEntryError( 'badyaml', sprintf( 'YAML::toYaml() needs a valid array: %s', $e->getMessage() ) );
		}
		
		return $parsed;
		
	}
	
	/**
	 * __toString function.
	 * 
	 * @access public
	 * @return void
	 */
	public function __toString() {
		return $this->toYaml();
	}
	
	/**
	 * __invoke function.
	 * 
	 * @access public
	 * @param mixed $data
	 * @return void
	 */
	public function __invoke($data,$indent = 5) {
		if( is_array( $data ) ) {
			return YAML::load($data)->toYaml($indent);
		}
		else {
			return YAML::load($data)->toArray();
		}
	}
}

