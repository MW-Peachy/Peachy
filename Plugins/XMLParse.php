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

class XMLParse {

	/**
	 * Converts an XML url or string to a PHP array format
	 *
	 * @static
	 * @access public
	 * @param string $data Either an url to an xml file, or a raw XML string. Peachy will autodetect which is which.
	 * @return array Parsed XML
	 */
	public static function load( $data ) {
		$http = HTTP::getDefaultInstance();

		if( !function_exists( 'simplexml_load_string' ) ) {
			throw new DependencyError( "SimpleXML", "http://us.php.net/manual/en/book.simplexml.php" );
		}

		libxml_use_internal_errors( true );

		if( in_string( "<?xml", $data ) ) {
			$xmlout = $data;
		} else {
			$xmlout = $http->get( $data );
		}

		Hooks::runHook( 'PreSimpleXMLLoad', array( &$xmlout ) );

		$xml = simplexml_load_string( $xmlout );

		Hooks::runHook( 'PostSimpleXMLLoad', array( &$xml ) );

		if( !$xml ) {
			foreach( libxml_get_errors() as $error ){
				throw new XMLError( $error );
			}
		}

		$outArr = array();

		$namespaces = $xml->getNamespaces( true );
		$namespaces['default'] = '';

		self::recurse( $xml, $outArr, $namespaces );

		libxml_clear_errors();

		return $outArr;
	}

	/**
	 * @param SimpleXMLElement $xml
	 */
	private static function recurse( $xml, &$arr, $namespaces ) {

		foreach( $namespaces as $namespace ){

			foreach( $xml->children( $namespace ) as $elementName => $node ){

				$key = count( $arr );

				if( isset( $arr['_name'] ) ) $key--;
				if( isset( $arr['_attributes'] ) ) $key--;

				$arr[$key] = array();
				$arr[$key]['_name'] = $elementName;

				$arr[$key]['_attributes'] = array();

				foreach( $node->attributes( $namespace ) as $aname => $avalue ){
					$arr[$key]['_attributes'][$aname] = trim( $avalue );
				}

				if( !count( $arr[$key]['_attributes'] ) ) {
					unset( $arr[$key]['_attributes'] );
				}

				$text = trim( (string)$node );

				if( strlen( $text ) > 0 ) $arr[$key]['_text'] = $text;

				self::recurse( $node, $arr[$key], $namespaces );

				if( count( $arr[$key] ) == 1 && isset( $arr[$key]['_text'] ) ) {
					$arr[$key] = $arr[$key]['_text'];
				}
			}
		}
	}

}