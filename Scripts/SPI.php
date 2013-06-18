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

This script deletes a bunch of pages using a list of page titles contained
in a file. The filename is argument #1.
*/

require_once( dirname( dirname(__FILE__) ) . '/Init.php' );

$script = new Script();

$wiki = $script->getWiki();

$reason = 'Updating SPI table';
if( $script->getArg( 'reason' ) ) {
	$reason = $script->getArg( 'reason' );
}

$page = 'User:X!/SPI';
if( $script->getArg( 'page' ) ) {
	$page = $script->getArg( 'page' );
}

$output = $wiki->initPage( $page );
if( $script->getArg( 'output' ) ) {
	$output = $wiki->initPage( $script->getArg( 'output' ) );
}

$checkusers = $wiki->allusers( null, array( 'checkuser' ) );

foreach( $checkusers as $key => $cu ) {
	$checkusers[$key] = $cu['name'];
}

$clerks = $wiki->initPage( "Wikipedia:Sockpuppet investigations/SPI/Clerks" )->get_text();
preg_match_all( '/\{\{User6b\|(.*?)\}\}/i', $clerks, $m );
$clerks = $m[1];

$list = $wiki->categorymembers( "Category:Open SPI cases", false, 4 );

$cases = array( 'open' => array(), 'curequest' => array(), 'endorsed' => array(), 'declined' => array(), 'cudeclined' => array(), 'inprogress' => array(), 'checked' => array(), 'hold' => array(), 'moreinfo' => array(), 'relisted' => array(), 'closed' => array() );

foreach( $list as $case ) {
	$case = $case['title'];
	
	if( !preg_match( '/^Wikipedia:Sockpuppet investigations\//i', $case ) ) continue;
	
	$case = $wiki->initPage( $case );
	
	$template = new Template( $case->get_text(), "SPI case status" );
	
	$status = strtolower( $template->fieldValue(1) );
	if( !$status ) $status = "open";
	
	$retArray = array( 'case' => str_replace( 'Wikipedia:Sockpuppet investigations/', '', $case->get_title() ) );
	
	
	preg_match( '/\d{2}:\d{2}, \d{2} \D* \d{4} \(UTC\)/', $case->get_text(), $date );
	$retArray['filed'] = date( 'Y-m-d H:m \U\T\C', strtotime($date[0]));
	
	
	$history = $ei = $case->history( $wiki->get_api_limit() + 1 );
	
	while( isset( $ei[ $wiki->get_api_limit() ] ) ) {
		$ei = $case->history( $wiki->get_api_limit() + 1, 'older', false, $ei[9]['revid']);
		foreach( $ei as $eg ) {
			$history[] = $eg;
		}
	}
	
	foreach( $history as $rev ) {
		if( ( in_array( $rev['user'], $checkusers ) || in_array( $rev['user'], $clerks ) ) && !isset( $retArray['cuedit'] ) ) {
			if( strtotime($rev['timestamp']) >= strtotime($date[0]) ) {
				$retArray['cuuser'] = $rev['user'];
				$retArray['cuedit'] = date( 'Y-m-d H:m \U\T\C', strtotime($rev['timestamp']));
			}
		}
		
		if( !isset( $retArray['lastuser'] ) ) {
			$retArray['lastedit'] = date( 'Y-m-d H:m \U\T\C', strtotime($rev['timestamp']));
			$retArray['lastuser'] = $rev['user'];
		}
	}
	
	if( !isset( $retArray['cuuser'] ) ) {
		$retArray['cuuser'] = $retArray['cuedit'] = "''none''";
	}
	
	switch( $status ) {
		case 'endorse': $status = 'endorsed';break;
		case 'decline': $status = 'declined';break;
		case 'cudecline': $status = 'cudeclined';break;
		case 'relist': $status = 'relisted';break;
		case 'completed': $status = 'checked';break;
		case 'checkuser': $status = 'curequest';break;
		case 'cu': $status = 'curequest';break;
		case 'request': $status = 'curequest';break;
	}
	
	$cases[$status][] = $retArray;
	
}

foreach( $cases as $style => $var ) usort( $cases[$style], 'sortThem' );

print_r($cases);

$out = <<<WIKI
{|class="wikitable sortable" width="100%"
! Investigation !! Status !! Date filed !! Last user to edit case !! timestamp !! Last clerk/checkuser to edit case !! timestamp
|-

WIKI;

foreach( $cases as $status => $caselist ) {
	foreach( $caselist as $case ) {
		$out .= "{{SPIstatusentry|{$case['case']}|$status|{$case['filed']}|{$case['lastuser']}|{$case['lastedit']}|{$case['cuuser']}|{$case['cuedit']}}}\n";
	}
}

$out .= "|}";

echo $out;

$output->edit( $out, $reason );

function sortThem( $case1, $case2 ) {
	return strcmp( strtotime( $case1['lastedit'] ), strtotime( $case2['lastedit'] ) );
}

