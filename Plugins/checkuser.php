<?php

//For now, this is an example of how a plugin would work with more than one function to a class.

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

class CheckUser {

    function __construct( &$wikiclass = null ) {
    
        $extensions = $wikiClass->get_extensions();
        if( !array_key_exists( 'CheckUser', $extensions ) ) {
            throw new DependancyError( "CheckUser", "http://www.mediawiki.org/wiki/Extension:CheckUser" );
        }
        elseif( $extensions['CheckUser'] < 3 ) {
            throw new DependancyError( "CheckUser version 3.0 or up", "http://www.mediawiki.org/wiki/Extension:CheckUser" );
        }
    }
}