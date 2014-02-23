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
 * Hooks object
 * Stores the runHook function, which runs all hook functions
 */

/**
 * Hooks class
 * Stores and runs {@link http://wiki.peachy.compwhizii.net/wiki/Manual/Hooks hooks}
 *
 */
class Hooks {

	/**
	 * Search for hook functions and run them if defined
	 *
	 * @param string $hook_name Name of hook to search for
	 * @param array $args Arguments to pass to the hook function
	 * @throws HookError
	 * @throws BadEntryError
	 * @return mixed Output of hook function
	 */
	public static function runHook( $hook_name, $args = array() ) {
		global $pgHooks;

		if( !isset( $pgHooks[$hook_name] ) ) return null;

		if( !is_array( $pgHooks[$hook_name] ) ) {
			throw new HookError( "Hook assignment for event `$hook_name` is not an array. Syntax is " . '$pgHooks[\'hookname\'][] = "hook_function";' );
		}

		$method = null;
		foreach( $pgHooks[$hook_name] as $function ){
			if( is_array( $function ) ) {
				if( count( $function ) < 2 ) {
					throw new HookError( "Not enough parameters in array specified for `$hook_name` hook" );
				} elseif( is_object( $function[0] ) ) {
					$object = $function[0];
					$method = $function[1];
					if( count( $function ) > 2 ) {
						$data = $function[2];
					}
				} elseif( is_string( $function[0] ) ) {
					$method = $function[0];
					if( count( $function ) > 1 ) {
						$data = $function[1];
					}
				}

			} elseif( is_string( $function ) ) {
				$method = $function;
			}

			if( isset( $data ) ) {
				$args = array_merge( array( $data ), $args );
			}

			if( isset( $object ) ) {
				$fncarr = array( $object, $method );
			} elseif( is_string( $method ) && in_string( "::", $method ) ) {
				$fncarr = explode( "::", $method );
			} else {
				$fncarr = $method;
			}

			//is_callable( $fncarr ); //Apparently this is a bug. Thanks, MW!

			if( !is_callable( $fncarr ) ) {
				throw new BadEntryError( "MissingFunction", "Hook function $fncarr was not defined" );
			}

			$hookRet = call_user_func_array( $fncarr, $args );

			if( !is_null( $hookRet ) ) return $hookRet;

		}

	}

}