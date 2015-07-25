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
 * Module of static functions for generating XML
 */
class Xml {
	/**
	 * Format an XML element with given attributes and, optionally, text content.
	 * Element and attribute names are assumed to be ready for literal inclusion.
	 * Strings are assumed to not contain XML-illegal characters; special
	 * characters (<, >, &) are escaped but illegals are not touched.
	 *
	 * @param string|null $element element name
	 * @param array $attribs Name=>value pairs. Values will be escaped.
	 * @param string $contents NULL to make an open tag only; '' for a contentless closed tag (default)
	 * @param bool $allowShortTag whether '' in $contents will result in a contentless closed tag
	 * @return string
	 */
	public static function element( $element = null, $attribs = null, $contents = '', $allowShortTag = true ) {
		$out = '<' . $element;
		if( !is_null( $attribs ) ) {
			$out .= self::expandAttributes( $attribs );
		}
		if( is_null( $contents ) ) {
			$out .= '>';
		} else {
			if( $allowShortTag && $contents === '' ) {
				$out .= ' />';
			} else {
				$out .= '>' . htmlspecialchars( $contents ) . "</$element>";
			}
		}
		return $out;
	}

	static function encodeAttribute( $text ) {
		$encValue = htmlspecialchars( $text, ENT_QUOTES );

		// Whitespace is normalized during attribute decoding,
		// so if we've been passed non-spaces we must encode them
		// ahead of time or they won't be preserved.
		$encValue = strtr(
			$encValue, array(
				"\n" => '&#10;',
				"\r" => '&#13;',
				"\t" => '&#9;',
			)
		);

		return $encValue;
	}

	/**
	 * Given an array of ('attributename' => 'value'), it generates the code
	 * to set the XML attributes : attributename="value".
	 * The values are passed to self::encodeAttribute.
	 * Return null if no attributes given.
	 * @param array|null $attribs of attributes for an XML element
	 * @throws Exception
	 * @return null|string
	 */
	public static function expandAttributes( $attribs = null ) {
		$out = '';
		if( is_null( $attribs ) ) {
			return null;
		} elseif( is_array( $attribs ) ) {
			foreach( $attribs as $name => $val ){
				$out .= " {$name}=\"" . self::encodeAttribute( $val ) . '"';
			}
			return $out;
		} else {
			throw new Exception( 'Expected attribute array, got something else in ' . __METHOD__ );
		}
	}

	/**
	 * Format an XML element as with self::element(), but run text through the
	 * $wgContLang->normalize() validator first to ensure that no invalid UTF-8
	 * is passed.
	 *
	 * @param $element String:
	 * @param array $attribs Name=>value pairs. Values will be escaped.
	 * @param string $contents NULL to make an open tag only; '' for a contentless closed tag (default)
	 * @return string
	 */
	public static function elementClean( $element, $attribs = array(), $contents = '' ) {
		if( $attribs ) {
			$attribs = array_map( array( 'UtfNormal', 'cleanUp' ), $attribs );
		}
		if( $contents ) {
			$contents = WebRequest::normalize_static( $contents );
		}
		return self::element( $element, $attribs, $contents );
	}

	/**
	 * This opens an XML element
	 *
	 * @param string $element name of the element
	 * @param array $attribs of attributes, see self::expandAttributes()
	 * @return string
	 */
	public static function openElement( $element, $attribs = null ) {
		return '<' . $element . self::expandAttributes( $attribs ) . '>';
	}

	/**
	 * Shortcut to close an XML element
	 * @param string $element element name
	 * @return string
	 */
	public static function closeElement( $element ) {
		return "</$element>";
	}

	/**
	 * Same as self::element(), but does not escape contents. Handy when the
	 * content you have is already valid xml.
	 *
	 * @param string $element element name
	 * @param array $attribs of attributes
	 * @param string $contents content of the element
	 * @return string
	 */
	public static function tags( $element, $attribs = null, $contents ) {
		return self::openElement( $element, $attribs ) . $contents . "</$element>";
	}

	/**
	 * Shortcut to make a span element
	 * @param string $text content of the element, will be escaped
	 * @param string $class class name of the span element
	 * @param array $attribs other attributes
	 * @return string
	 */
	public static function span( $text, $class, $attribs = array() ) {
		return self::element( 'span', array( 'class' => $class ) + $attribs, $text );
	}

	/**
	 * Shortcut to make a specific element with a class attribute
	 * @param string $text content of the element, will be escaped
	 * @param string $class class name of the span element
	 * @param string $pgTag element name
	 * @param array $attribs other attributes
	 * @return string
	 */
	public static function wrapClass( $text, $class, $pgTag = 'span', $attribs = array() ) {
		return self::tags( $pgTag, array( 'class' => $class ) + $attribs, $text );
	}

	/**
	 * Convenience function to build an HTML text input field
	 * @param string $name value of the name attribute
	 * @param bool|int $size value of the size attribute
	 * @param mixed $value mixed value of the value attribute
	 * @param array $attribs other attributes
	 * @return string HTML
	 */
	public static function input( $name, $size = false, $value = false, $attribs = array() ) {
		$attributes = array( 'name' => $name );

		if( $size ) {
			$attributes['size'] = $size;
		}

		if( $value !== false ) { // maybe 0
			$attributes['value'] = $value;
		}

		return self::element( 'input', $attributes + $attribs );
	}

	/**
	 * Convenience function to build an HTML password input field
	 * @param string $name value of the name attribute
	 * @param bool|int $size value of the size attribute
	 * @param $value mixed value of the value attribute
	 * @param array $attribs other attributes
	 * @return string HTML
	 */
	public static function password( $name, $size = false, $value = false, $attribs = array() ) {
		return self::input( $name, $size, $value, array_merge( $attribs, array( 'type' => 'password' ) ) );
	}

	/**
	 * Internal function for use in checkboxes and radio buttons and such.
	 *
	 * @param $name string
	 * @param $present bool
	 *
	 * @return array
	 */
	public static function attrib( $name, $present = true ) {
		return $present ? array( $name => $name ) : array();
	}

	/**
	 * Convenience function to build an HTML checkbox
	 * @param string $name value of the name attribute
	 * @param bool $checked Whether the checkbox is checked or not
	 * @param array $attribs other attributes
	 * @return string HTML
	 */
	public static function check( $name, $checked = false, $attribs = array() ) {
		return self::element(
			'input', array_merge(
				array(
					'name'  => $name,
					'type'  => 'checkbox',
					'value' => 1
				),
				self::attrib( 'checked', $checked ),
				$attribs
			)
		);
	}

	/**
	 * Convenience function to build an HTML radio button
	 * @param string $name value of the name attribute
	 * @param string $value value of the value attribute
	 * @param bool $checked Whether the checkbox is checked or not
	 * @param array $attribs other attributes
	 * @return string HTML
	 */
	public static function radio( $name, $value, $checked = false, $attribs = array() ) {
		return self::element(
			'input', array(
						 'name'  => $name,
						 'type'  => 'radio',
						 'value' => $value
					 ) + self::attrib( 'checked', $checked ) + $attribs
		);
	}

	/**
	 * Convenience function to build an HTML form label
	 * @param string $label text of the label
	 * @param $id
	 * @param array $attribs an attribute array.  This will usually be
	 *     the same array as is passed to the corresponding input element,
	 *     so this function will cherry-pick appropriate attributes to
	 *     apply to the label as well; only class and title are applied.
	 * @return string HTML
	 */
	public static function label( $label, $id, $attribs = array() ) {
		$a = array( 'for' => $id );

		// FIXME avoid copy pasting below:
		if( isset( $attribs['class'] ) ) {
			$a['class'] = $attribs['class'];
		}
		if( isset( $attribs['title'] ) ) {
			$a['title'] = $attribs['title'];
		}

		return self::element( 'label', $a, $label );
	}

	/**
	 * Convenience function to build an HTML text input field with a label
	 * @param string $label text of the label
	 * @param string $name value of the name attribute
	 * @param string $id id of the input
	 * @param int|Bool $size value of the size attribute
	 * @param string|Bool $value value of the value attribute
	 * @param array $attribs other attributes
	 * @return string HTML
	 */
	public static function inputLabel( $label, $name, $id, $size = false, $value = false, $attribs = array() ) {
		list( $label, $input ) = self::inputLabelSep( $label, $name, $id, $size, $value, $attribs );
		return $label . '&#160;' . $input;
	}

	/**
	 * Same as self::inputLabel() but return input and label in an array
	 *
	 * @param $label String
	 * @param $name String
	 * @param $id String
	 * @param $size Int|Bool
	 * @param $value String|Bool
	 * @param $attribs array
	 *
	 * @return array
	 */
	public static function inputLabelSep( $label, $name, $id, $size = false, $value = false, $attribs = array() ) {
		return array(
			self::label( $label, $id, $attribs ),
			self::input( $name, $size, $value, array( 'id' => $id ) + $attribs )
		);
	}

	/**
	 * Convenience function to build an HTML checkbox with a label
	 *
	 * @param $label
	 * @param $name
	 * @param $id
	 * @param $checked bool
	 * @param $attribs array
	 *
	 * @return string HTML
	 */
	public static function checkLabel( $label, $name, $id, $checked = false, $attribs = array() ) {
		return self::check( $name, $checked, array( 'id' => $id ) + $attribs ) .
			   '&#160;' .
			   self::label( $label, $id, $attribs );
	}

	/**
	 * Convenience function to build an HTML radio button with a label
	 *
	 * @param $label
	 * @param $name
	 * @param $value
	 * @param $id
	 * @param $checked bool
	 * @param $attribs array
	 *
	 * @return string HTML
	 */
	public static function radioLabel( $label, $name, $value, $id, $checked = false, $attribs = array() ) {
		return self::radio( $name, $value, $checked, array( 'id' => $id ) + $attribs ) .
			   '&#160;' .
			   self::label( $label, $id, $attribs );
	}

	/**
	 * Convenience function to build an HTML submit button
	 * @param string $value label text for the button
	 * @param array $attribs optional custom attributes
	 * @return string HTML
	 */
	public static function submitButton( $value, $attribs = array() ) {
		return self::element( 'input', array( 'type' => 'submit', 'value' => $value ) + $attribs );
	}

	/**
	 * Convenience function to build an HTML drop-down list item.
	 * @param string $text text for this item. Will be HTML escaped
	 * @param string $value form submission value; if empty, use text
	 * @param $selected boolean: if true, will be the default selected item
	 * @param array $attribs optional additional HTML attributes
	 * @return string HTML
	 */
	public static function option( $text, $value = null, $selected = false,
								   $attribs = array() ) {
		if( !is_null( $value ) ) {
			$attribs['value'] = $value;
		}
		if( $selected ) {
			$attribs['selected'] = 'selected';
		}
		return self::element( 'option', $attribs, $text );
	}

	/**
	 * Build a drop-down box from a textual list.
	 *
	 * @param $name Mixed: Name and id for the drop-down
	 * @param $list Mixed: Correctly formatted text (newline delimited) to be used to generate the options
	 * @param $other Mixed: Text for the "Other reasons" option
	 * @param $selected Mixed: Option which should be pre-selected
	 * @param $class Mixed: CSS classes for the drop-down
	 * @param $tabindex Mixed: Value of the tabindex attribute
	 * @return string
	 */
	public static function listDropDown( $name = '', $list = '', $other = '', $selected = '', $class = '', $tabindex = null ) {
		$optgroup = false;

		$options = self::option( $other, 'other', $selected === 'other' );

		foreach( explode( "\n", $list ) as $option ){
			$value = trim( $option );
			if( $value == '' ) {
				continue;
			} elseif( substr( $value, 0, 1 ) == '*' && substr( $value, 1, 1 ) != '*' ) {
				// A new group is starting ...
				$value = trim( substr( $value, 1 ) );
				if( $optgroup ) {
					$options .= self::closeElement( 'optgroup' );
				}
				$options .= self::openElement( 'optgroup', array( 'label' => $value ) );
				$optgroup = true;
			} elseif( substr( $value, 0, 2 ) == '**' ) {
				// groupmember
				$value = trim( substr( $value, 2 ) );
				$options .= self::option( $value, $value, $selected === $value );
			} else {
				// groupless reason list
				if( $optgroup ) {
					$options .= self::closeElement( 'optgroup' );
				}
				$options .= self::option( $value, $value, $selected === $value );
				$optgroup = false;
			}
		}

		if( $optgroup ) {
			$options .= self::closeElement( 'optgroup' );
		}

		$attribs = array();

		if( $name ) {
			$attribs['id'] = $name;
			$attribs['name'] = $name;
		}

		if( $class ) {
			$attribs['class'] = $class;
		}

		if( $tabindex ) {
			$attribs['tabindex'] = $tabindex;
		}

		return self::openElement( 'select', $attribs )
			   . "\n"
			   . $options
			   . "\n"
			   . self::closeElement( 'select' );
	}

	/**
	 * Shortcut for creating fieldsets.
	 *
	 * @param string|bool $legend Legend of the fieldset. If evaluates to false, legend is not added.
	 * @param string|bool $content Pre-escaped content for the fieldset. If false, only open fieldset is returned.
	 * @param array $attribs Any attributes to fieldset-element.
	 *
	 * @return string
	 */
	public static function fieldset( $legend = false, $content = false, $attribs = array() ) {
		$s = self::openElement( 'fieldset', $attribs ) . "\n";

		if( $legend ) {
			$s .= self::element( 'legend', null, $legend ) . "\n";
		}

		if( $content !== false ) {
			$s .= $content . "\n";
			$s .= self::closeElement( 'fieldset' ) . "\n";
		}

		return $s;
	}

	/**
	 * Shortcut for creating textareas.
	 *
	 * @param string $name The 'name' for the textarea
	 * @param string $content Content for the textarea
	 * @param int $cols The number of columns for the textarea
	 * @param int $rows The number of rows for the textarea
	 * @param array $attribs Any other attributes for the textarea
	 *
	 * @return string
	 */
	public static function textarea( $name, $content, $cols = 40, $rows = 5, $attribs = array() ) {
		return self::element(
			'textarea',
			array(
				'name' => $name,
				'id'   => $name,
				'cols' => $cols,
				'rows' => $rows
			) + $attribs,
			$content, false
		);
	}

	/**
	 * Returns an escaped string suitable for inclusion in a string literal
	 * for JavaScript source code.
	 * Illegal control characters are assumed not to be present.
	 *
	 * @param string $string to escape
	 * @return String
	 */
	public static function escapeJsString( $string ) {
		// See ECMA 262 section 7.8.4 for string literal format
		$pairs = array(
			"\\"           => "\\\\",
			"\""           => "\\\"",
			'\''           => '\\\'',
			"\n"           => "\\n",
			"\r"           => "\\r",

			# To avoid closing the element or CDATA section
			"<"            => "\\x3c",
			">"            => "\\x3e",

			# To avoid any complaints about bad entity refs
			"&"            => "\\x26",

			# Work around https://bugzilla.mozilla.org/show_bug.cgi?id=274152
			# Encode certain Unicode formatting chars so affected
			# versions of Gecko don't misinterpret our strings;
			# this is a common problem with Farsi text.
			"\xe2\x80\x8c" => "\\u200c", // ZERO WIDTH NON-JOINER
			"\xe2\x80\x8d" => "\\u200d", // ZERO WIDTH JOINER
		);

		return strtr( $string, $pairs );
	}

	/**
	 * Encode a variable of unknown type to JavaScript.
	 * Arrays are converted to JS arrays, objects are converted to JS associative
	 * arrays (objects). So cast your PHP associative arrays to objects before
	 * passing them to here.
	 *
	 * @param $value
	 * @return int|string
	 */
	public static function encodeJsVar( $value ) {
		if( is_bool( $value ) ) {
			$s = $value ? 'true' : 'false';
		} elseif( is_null( $value ) ) {
			$s = 'null';
		} elseif( is_int( $value ) ) {
			$s = $value;
		} elseif( is_array( $value ) && // Make sure it's not associative.
				  array_keys( $value ) === range( 0, count( $value ) - 1 ) ||
				  count( $value ) == 0
		) {
			$s = '[';
			foreach( $value as $elt ){
				if( $s != '[' ) {
					$s .= ', ';
				}
				$s .= self::encodeJsVar( $elt );
			}
			$s .= ']';
		} elseif( is_object( $value ) || is_array( $value ) ) {
			// Objects and associative arrays
			$s = '{';
			foreach( (array)$value as $name => $elt ){
				if( $s != '{' ) {
					$s .= ', ';
				}
				$s .= '"' . self::escapeJsString( $name ) . '": ' .
					  self::encodeJsVar( $elt );
			}
			$s .= '}';
		} else {
			$s = '"' . self::escapeJsString( $value ) . '"';
		}
		return $s;
	}

	/**
	 * Create a call to a JavaScript function. The supplied arguments will be
	 * encoded using self::encodeJsVar().
	 *
	 * @since 1.17
	 * @param string $name The name of the function to call, or a JavaScript expression
	 *    which evaluates to a function object which is called.
	 * @param array $args The arguments to pass to the function.
	 * @param bool $pretty If true, add non-significant whitespace to improve readability.
	 * @return string|bool: String if successful; false upon failure
	 */
	public static function encodeJsCall( $name, $args, $pretty = false ) {
		foreach( $args as &$arg ){
			$arg = self::encodeJsVar($arg);
			if( $arg === false ) {
				return false;
			}
		}

		return "$name(" . ( $pretty
			? ( ' ' . implode( ', ', $args ) . ' ' )
			: implode( ',', $args )
		) . ");";
	}

	/**
	 * Check if a string is well-formed XML.
	 * Must include the surrounding tag.
	 *
	 * @param string $text string to test.
	 * @return bool
	 *
	 * @todo Error position reporting return
	 */
	public static function isWellFormed( $text ) {
		$parser = xml_parser_create( "UTF-8" );

		# case folding violates XML standard, turn it off
		xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, false );

		if( !xml_parse( $parser, $text, true ) ) {
			//$err = xml_error_string( xml_get_error_code( $parser ) );
			//$position = xml_get_current_byte_index( $parser );
			//$fragment = $this->extractFragment( $html, $position );
			//$this->mXmlError = "$err at byte $position:\n$fragment";
			xml_parser_free( $parser );
			return false;
		}

		xml_parser_free( $parser );

		return true;
	}

	/**
	 * Check if a string is a well-formed XML fragment.
	 * Wraps fragment in an \<html\> bit and doctype, so it can be a fragment
	 * and can use HTML named entities.
	 *
	 * @param $text String:
	 * @return bool
	 */
	public static function isWellFormedXmlFragment( $text ) {
		$html =
			self::hackDocType() .
			'<html>' .
			$text .
			'</html>';

		return self::isWellFormed( $html );
	}

	static function hackDocType() {
		$wgHtmlEntities = array(
			'Aacute'   => 193,
			'aacute'   => 225,
			'Acirc'    => 194,
			'acirc'    => 226,
			'acute'    => 180,
			'AElig'    => 198,
			'aelig'    => 230,
			'Agrave'   => 192,
			'agrave'   => 224,
			'alefsym'  => 8501,
			'Alpha'    => 913,
			'alpha'    => 945,
			'amp'      => 38,
			'and'      => 8743,
			'ang'      => 8736,
			'Aring'    => 197,
			'aring'    => 229,
			'asymp'    => 8776,
			'Atilde'   => 195,
			'atilde'   => 227,
			'Auml'     => 196,
			'auml'     => 228,
			'bdquo'    => 8222,
			'Beta'     => 914,
			'beta'     => 946,
			'brvbar'   => 166,
			'bull'     => 8226,
			'cap'      => 8745,
			'Ccedil'   => 199,
			'ccedil'   => 231,
			'cedil'    => 184,
			'cent'     => 162,
			'Chi'      => 935,
			'chi'      => 967,
			'circ'     => 710,
			'clubs'    => 9827,
			'cong'     => 8773,
			'copy'     => 169,
			'crarr'    => 8629,
			'cup'      => 8746,
			'curren'   => 164,
			'dagger'   => 8224,
			'Dagger'   => 8225,
			'darr'     => 8595,
			'dArr'     => 8659,
			'deg'      => 176,
			'Delta'    => 916,
			'delta'    => 948,
			'diams'    => 9830,
			'divide'   => 247,
			'Eacute'   => 201,
			'eacute'   => 233,
			'Ecirc'    => 202,
			'ecirc'    => 234,
			'Egrave'   => 200,
			'egrave'   => 232,
			'empty'    => 8709,
			'emsp'     => 8195,
			'ensp'     => 8194,
			'Epsilon'  => 917,
			'epsilon'  => 949,
			'equiv'    => 8801,
			'Eta'      => 919,
			'eta'      => 951,
			'ETH'      => 208,
			'eth'      => 240,
			'Euml'     => 203,
			'euml'     => 235,
			'euro'     => 8364,
			'exist'    => 8707,
			'fnof'     => 402,
			'forall'   => 8704,
			'frac12'   => 189,
			'frac14'   => 188,
			'frac34'   => 190,
			'frasl'    => 8260,
			'Gamma'    => 915,
			'gamma'    => 947,
			'ge'       => 8805,
			'gt'       => 62,
			'harr'     => 8596,
			'hArr'     => 8660,
			'hearts'   => 9829,
			'hellip'   => 8230,
			'Iacute'   => 205,
			'iacute'   => 237,
			'Icirc'    => 206,
			'icirc'    => 238,
			'iexcl'    => 161,
			'Igrave'   => 204,
			'igrave'   => 236,
			'image'    => 8465,
			'infin'    => 8734,
			'int'      => 8747,
			'Iota'     => 921,
			'iota'     => 953,
			'iquest'   => 191,
			'isin'     => 8712,
			'Iuml'     => 207,
			'iuml'     => 239,
			'Kappa'    => 922,
			'kappa'    => 954,
			'Lambda'   => 923,
			'lambda'   => 955,
			'lang'     => 9001,
			'laquo'    => 171,
			'larr'     => 8592,
			'lArr'     => 8656,
			'lceil'    => 8968,
			'ldquo'    => 8220,
			'le'       => 8804,
			'lfloor'   => 8970,
			'lowast'   => 8727,
			'loz'      => 9674,
			'lrm'      => 8206,
			'lsaquo'   => 8249,
			'lsquo'    => 8216,
			'lt'       => 60,
			'macr'     => 175,
			'mdash'    => 8212,
			'micro'    => 181,
			'middot'   => 183,
			'minus'    => 8722,
			'Mu'       => 924,
			'mu'       => 956,
			'nabla'    => 8711,
			'nbsp'     => 160,
			'ndash'    => 8211,
			'ne'       => 8800,
			'ni'       => 8715,
			'not'      => 172,
			'notin'    => 8713,
			'nsub'     => 8836,
			'Ntilde'   => 209,
			'ntilde'   => 241,
			'Nu'       => 925,
			'nu'       => 957,
			'Oacute'   => 211,
			'oacute'   => 243,
			'Ocirc'    => 212,
			'ocirc'    => 244,
			'OElig'    => 338,
			'oelig'    => 339,
			'Ograve'   => 210,
			'ograve'   => 242,
			'oline'    => 8254,
			'Omega'    => 937,
			'omega'    => 969,
			'Omicron'  => 927,
			'omicron'  => 959,
			'oplus'    => 8853,
			'or'       => 8744,
			'ordf'     => 170,
			'ordm'     => 186,
			'Oslash'   => 216,
			'oslash'   => 248,
			'Otilde'   => 213,
			'otilde'   => 245,
			'otimes'   => 8855,
			'Ouml'     => 214,
			'ouml'     => 246,
			'para'     => 182,
			'part'     => 8706,
			'permil'   => 8240,
			'perp'     => 8869,
			'Phi'      => 934,
			'phi'      => 966,
			'Pi'       => 928,
			'pi'       => 960,
			'piv'      => 982,
			'plusmn'   => 177,
			'pound'    => 163,
			'prime'    => 8242,
			'Prime'    => 8243,
			'prod'     => 8719,
			'prop'     => 8733,
			'Psi'      => 936,
			'psi'      => 968,
			'quot'     => 34,
			'radic'    => 8730,
			'rang'     => 9002,
			'raquo'    => 187,
			'rarr'     => 8594,
			'rArr'     => 8658,
			'rceil'    => 8969,
			'rdquo'    => 8221,
			'real'     => 8476,
			'reg'      => 174,
			'rfloor'   => 8971,
			'Rho'      => 929,
			'rho'      => 961,
			'rlm'      => 8207,
			'rsaquo'   => 8250,
			'rsquo'    => 8217,
			'sbquo'    => 8218,
			'Scaron'   => 352,
			'scaron'   => 353,
			'sdot'     => 8901,
			'sect'     => 167,
			'shy'      => 173,
			'Sigma'    => 931,
			'sigma'    => 963,
			'sigmaf'   => 962,
			'sim'      => 8764,
			'spades'   => 9824,
			'sub'      => 8834,
			'sube'     => 8838,
			'sum'      => 8721,
			'sup'      => 8835,
			'sup1'     => 185,
			'sup2'     => 178,
			'sup3'     => 179,
			'supe'     => 8839,
			'szlig'    => 223,
			'Tau'      => 932,
			'tau'      => 964,
			'there4'   => 8756,
			'Theta'    => 920,
			'theta'    => 952,
			'thetasym' => 977,
			'thinsp'   => 8201,
			'THORN'    => 222,
			'thorn'    => 254,
			'tilde'    => 732,
			'times'    => 215,
			'trade'    => 8482,
			'Uacute'   => 218,
			'uacute'   => 250,
			'uarr'     => 8593,
			'uArr'     => 8657,
			'Ucirc'    => 219,
			'ucirc'    => 251,
			'Ugrave'   => 217,
			'ugrave'   => 249,
			'uml'      => 168,
			'upsih'    => 978,
			'Upsilon'  => 933,
			'upsilon'  => 965,
			'Uuml'     => 220,
			'uuml'     => 252,
			'weierp'   => 8472,
			'Xi'       => 926,
			'xi'       => 958,
			'Yacute'   => 221,
			'yacute'   => 253,
			'yen'      => 165,
			'Yuml'     => 376,
			'yuml'     => 255,
			'Zeta'     => 918,
			'zeta'     => 950,
			'zwj'      => 8205,
			'zwnj'     => 8204
		);
		$out = "<!DOCTYPE html [\n";
		foreach( $wgHtmlEntities as $entity => $codepoint ){
			$out .= "<!ENTITY $entity \"&#$codepoint;\">";
		}
		$out .= "]>\n";
		return $out;
	}

	/**
	 * Replace " > and < with their respective HTML entities ( &quot;,
	 * &gt;, &lt;)
	 *
	 * @param string $in text that might contain HTML tags.
	 * @return string Escaped string
	 */
	public static function escapeTagsOnly( $in ) {
		return str_replace(
			array( '"', '>', '<' ),
			array( '&quot;', '&gt;', '&lt;' ),
			$in
		);
	}

	/**
	 * Build a table of data
	 * @param array $rows An array of arrays of strings, each to be a row in a table
	 * @param array $attribs An array of attributes to apply to the table tag [optional]
	 * @param array $headers An array of strings to use as table headers [optional]
	 * @return string
	 */
	public static function buildTable( $rows, $attribs = array(), $headers = null ) {
		$s = self::openElement( 'table', $attribs );

		if( is_array( $headers ) ) {
			$s .= self::openElement( 'thead', $attribs );

			foreach( $headers as $id => $header ){
				$attribs = array();

				if( is_string( $id ) ) {
					$attribs['id'] = $id;
				}

				$s .= self::element( 'th', $attribs, $header );
			}
			$s .= self::closeElement( 'thead' );
		}

		foreach( $rows as $id => $row ){
			$attribs = array();

			if( is_string( $id ) ) {
				$attribs['id'] = $id;
			}

			$s .= self::buildTableRow( $attribs, $row );
		}

		$s .= self::closeElement( 'table' );

		return $s;
	}

	/**
	 * Build a row for a table
	 * @param array $attribs An array of attributes to apply to the tr tag
	 * @param array $cells An array of strings to put in <td>
	 * @return string
	 */
	public static function buildTableRow( $attribs, $cells ) {
		$s = self::openElement( 'tr', $attribs );

		foreach( $cells as $id => $cell ){
			$attribs = array();

			if( is_string( $id ) ) {
				$attribs['id'] = $id;
			}

			$s .= self::element( 'td', $attribs, $cell );
		}

		$s .= self::closeElement( 'tr' );

		return $s;
	}
}

class XmlSelect {
	protected $options = array();
	protected $default = false;
	protected $attributes = array();

	public function __construct( $name = false, $id = false, $default = false ) {
		if( $name ) {
			$this->setAttribute( 'name', $name );
		}

		if( $id ) {
			$this->setAttribute( 'id', $id );
		}

		if( $default !== false ) {
			$this->default = $default;
		}
	}

	/**
	 * @param $default
	 */
	public function setDefault( $default ) {
		$this->default = $default;
	}

	/**
	 * @param $name string
	 * @param $value
	 */
	public function setAttribute( $name, $value ) {
		$this->attributes[$name] = $value;
	}

	/**
	 * @param $name
	 * @return array|null
	 */
	public function getAttribute( $name ) {
		if( isset( $this->attributes[$name] ) ) {
			return $this->attributes[$name];
		} else {
			return null;
		}
	}

	/**
	 * @param $name
	 * @param $value bool
	 */
	public function addOption( $name, $value = false ) {
		// Stab stab stab
		$value = $value !== false ? $value : $name;

		$this->options[] = array( $name => $value );
	}

	/**
	 * This accepts an array of form
	 * label => value
	 * label => ( label => value, label => value )
	 *
	 * @param  $options
	 */
	public function addOptions( $options ) {
		$this->options[] = $options;
	}

	/**
	 * This accepts an array of form
	 * label => value
	 * label => ( label => value, label => value )
	 *
	 * @param  $options
	 * @param bool $default
	 * @return string
	 */
	static function formatOptions( $options, $default = false ) {
		$data = '';

		foreach( $options as $label => $value ){
			if( is_array( $value ) ) {
				$contents = self::formatOptions( $value, $default );
				$data .= self::tags( 'optgroup', array( 'label' => $label ), $contents ) . "\n";
			} else {
				$data .= self::option( $label, $value, $value === $default ) . "\n";
			}
		}

		return $data;
	}

	/**
	 * @return string
	 */
	public function getHTML() {
		$contents = '';

		foreach( $this->options as $options ){
			$contents .= self::formatOptions( $options, $this->default );
		}

		return self::tags( 'select', $this->attributes, rtrim( $contents ) );
	}
}