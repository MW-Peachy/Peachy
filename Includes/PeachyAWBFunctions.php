<?php

/*

	This program is free software=> you can redistribute it and/or modify
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
	along with this program. If not, see <http=>//gnu.org/licenses/>.

*/

/**
 * PeachyAWBFunctions class.
 *
 * It consists of various static functions used for the PeachyAWB script
 * Much of the code is derived from Pywikipedia and AWB, both under the GPL
 *
 */
class PeachyAWBFunctions {

	public static $html_tags = array(
		# Tags that must be closed
		'b', 'del', 'i', 'ins', 'u', 'font', 'big', 'small', 'sub', 'sup', 'h1',
		'h2', 'h3', 'h4', 'h5', 'h6', 'cite', 'code', 'em', 's',
		'strike', 'strong', 'tt', 'var', 'div', 'center',
		'blockquote', 'ol', 'ul', 'dl', 'table', 'caption', 'pre',
		'ruby', 'rt', 'rb', 'rp', 'p', 'span', 'u', 'abbr',
		# Single
		'br', 'hr', 'li', 'dt', 'dd',
		# Elements that cannot have close tags
		'br', 'hr',
		# Tags that can be nested--??
		'table', 'tr', 'td', 'th', 'div', 'blockquote', 'ol', 'ul',
		'dl', 'font', 'big', 'small', 'sub', 'sup', 'span',
		# Can only appear inside table, we will close them
		'td', 'th', 'tr',
		# Tags used by list
		'ul', 'ol',
		# Tags that can appear in a list
		'li',
		## pairs
		#			"b", "i", "u", "font", "big", "small", "sub", "sup", "h1",
		#			"h2", "h3", "h4", "h5", "h6", "cite", "code", "em", "s", "span",
		#			"strike", "strong", "tt", "var", "div", "center",
		#			"blockquote", "ol", "ul", "dl", "table", "caption", "pre",
		#			"ruby", "rt" , "rb" , "rp",
		## single
		#			"br", "p", "hr", "li", "dt", "dd",
		## nest
		#			"table", "tr", "td", "th", "div", "blockquote", "ol", "ul",
		#			"dl", "font", "big", "small", "sub", "sup",
		## table tags
		#			"td", "th", "tr",

	);

	public static $html_attrs = array(
		"title", "align", "lang", "dir", "width", "height",
		"bgcolor", "clear", "noshade",
		"cite", "size", "face", "color",
		"type", "start", "value", "compact",
		#/* For various lists, mostly deprecated but safe */
		"summary", "width", "border", "frame", "rules",
		"cellspacing", "cellpadding", "valign", "char",
		"charoff", "colgroup", "col", "span", "abbr", "axis",
		"headers", "scope", "rowspan", "colspan",
		"id", "class", "name", "style"
	);

	public static $html_colors = array(
		'#F0FFFF' => 'azure', '#F5F5DC' => 'beige', '#FFE4C4' => 'bisque', '#000000' => 'black', '#0000FF' => 'blue',
		'#A52A2A' => 'brown', '#FF7F50' => 'coral', '#FFF8DC' => 'cornsilk', '#DC143C' => 'crimson',
		'#00FFFF' => 'cyan', '#00008B' => 'darkBlue', '#008B8B' => 'darkCyan', '#A9A9A9' => 'darkGray',
		'#8B0000' => 'darkRed', '#FF1493' => 'deepPink', '#696969' => 'dimGray', '#FF00FF' => 'fuchsia',
		'#FFD700' => 'gold', '#808080' => 'gray', '#008000' => 'green', '#F0FFF0' => 'honeyDew', '#FF69B4' => 'hotPink',
		'#4B0082' => 'indigo', '#FFFFF0' => 'ivory', '#F0E68C' => 'khaki', '#E6E6FA' => 'lavender', '#00FF00' => 'lime',
		'#FAF0E6' => 'linen', '#800000' => 'maroon', '#FFE4B5' => 'moccasin', '#000080' => 'navy',
		'#FDF5E6' => 'oldLace', '#808000' => 'olive', '#FFA500' => 'orange', '#DA70D6' => 'orchid', '#CD853F' => 'peru',
		'#FFC0CB' => 'pink', '#DDA0DD' => 'plum', '#800080' => 'purple', '#FF0000' => 'red', '#FA8072' => 'salmon',
		'#2E8B57' => 'seaGreen', '#FFF5EE' => 'seaShell', '#A0522D' => 'sienna', '#C0C0C0' => 'silver',
		'#87CEEB' => 'skyBlue', '#FFFAFA' => 'snow', '#D2B48C' => 'tan', '#008080' => 'teal', '#D8BFD8' => 'thistle',
		'#FF6347' => 'tomato', '#EE82EE' => 'violet', '#F5DEB3' => 'wheat', '#FFFFFF' => 'white', '#FFFF00' => 'yellow',
	);

	public static $stub_search = '[Ss]tub';

	public static $interwiki_map = array();

	public static $typo_list = array();

	public static function fixVars( Wiki $wiki ) {
		$interwiki = $wiki->siteinfo( array( 'interwikimap' ) );
		self::$interwiki_map = $interwiki['query']['interwikimap'];
	}

	public static function fixCitations( $text ) {

		//merge all variant of cite web
		$text = preg_replace( '/\{\{\s*(cite[_ \-]*(url|web|website)|Web[_ \-]*(citation|cite|reference|reference[_ ]4))(?=\s*\|)/i', '{{cite web', $text );

		//Remove formatting on certian parameters
		$text = preg_replace( "/(\|\s*(?:agency|author|first|format|language|last|location|month|publisher|work|year)\s*=\s*)(''|'''|''''')((?:\[\[[^][|]+|\[\[|)[][\w\s,.~!`\"]+)(''+)(?=\s*\|[\w\s]+=|\s*\}\})/", '$1$3', $text );

		//Unlink PDF in format parameters
		$text = preg_replace( '/(\|\s*format\s*=\s*)\[\[(adobe|portable|document|file|format|pdf|\.|\s|\(|\)|\|)+\]\]/i', '$1PDF', $text );
		$text = preg_replace( '/(\|\s*format\s*=\s*)(\s*\.?(adobe|portable|document|file|format|pdf|\(|\)))+?(\s*[|}])/i', '$1PDF$4', $text );

		//No |format=HTML says {{cite web/doc}}
		$text = preg_replace( '/(\{\{cite[^{}]+)\|\s*format\s*=\s*(\[\[[^][|]+\||\[\[|)(\]\]| |html?|world|wide|web)+\s*(?=\||\}\})/i', '$1', $text );

		//Fix accessdate tags [[WP:AWB/FR#Fix accessdate tags]]
		$text = preg_replace(
			array(
				'/(\|\s*)a[ces]{3,8}date(\s*=\s*)(?=[^{|}]*20\d\d|\}\})/',
				'/accessdate(\s*=\s*)\[*(200\d)[/_\-](\d{2})[/_\-](\d{2})\]*/',
				'/(\|\s*)a[cs]*es*mou*nthday(\s*=\s*)/',
				'/(\|\s*)a[cs]*es*daymou*nth(\s*=\s*)/',
				'/(\|\s*)accessdate(\s*=\s*[0-3]?[0-9] +(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\w*)([^][<>}{]*accessyear[\s=]+20\d\d)/',
				'/(\|\s*)accessdate(\s*=\s*(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Oct|Nov|Dec)\w* +[0-3]?[0-9])([^][<>}{]*accessyear[\s=]+20\d\d)/',
				'/(\|\s*)accessdaymonth(\s*=\s*)\s*([^{|}<>]+?)\s*(\|[^][<>}{]*accessyear[\s=]+)(20\d\d)/',
				'/(\|\s*)accessmonthday(\s*=\s*)\s*([^{|}<>]+?)\s*(\|[^][<>}{]*accessyear[\s=]+)(20\d\d)/',
			),
			array(
				'$1accessdate$2',
				'accessdate$1$2-$3-$4',
				'$1accessmonthday$2',
				'$1accessdaymonth$2',
				'$1accessdaymonth$2$3',
				'$1accessmonthday$2$3',
				'$1accessdate$2$3 $5',
				'$1accessdate$2$3, $5',
			),
			$text
		);

		//Fix improper dates
		$text = preg_replace(
			array(
				'/(\{\{cit[ea][^{}]+\|\s*date\s*=\s*\d{2}[/\-.]\d{2}[/\-.])([5-9]\d)(?=\s*[|}])/i',
				'/(\{\{cit[ea][^{}]+\|\s*date\s*=\s*)(0[1-9]|1[012])[/\-.](1[3-9]|2\d|3[01])[/\-.](19\d\d|20\d\d)(?=\s*[|}])/i',
				'/(\{\{cit[ea][^{}]+\|\s*date\s*=\s*)(1[3-9]|2\d|3[01])[/\-.](0[1-9]|1[012])[/\-.](19\d\d|20\d\d)(?=\s*[|}])/i',
			),
			array(
				'${1}19$2',
				'$1$4-$2-$3',
				'$1$4-$3-$2',
			),

			$text
		);

		//Fix URLS lacking http://
		$text = preg_replace( '/(\|\s*url\s*=\s*)([0-9a-z.\-]+\.[a-z]{2,4}/[^][{|}:\s"]\s*[|}])/', '$1http://$2', $text );

		//Fix {{citation|title=[url title]}}
		$text = preg_replace( '/(\{\{cit[ea][^{}]*?)(\s*\|\s*)(?:url|title)(\s*=\s*)\[([^][<>\s"]*) +([^]\n]+)\](?=[|}])/i', '$1$2url$3$4$2title$3$5', $text );

		return $text;

	}

	public static function fixDateTags( $text ) {

		$text = preg_replace( '/\{\{\s*(?:template:)?\s*(?:wikify(?:-date)?|wfy|wiki)(\s*\|\s*section)?\s*\}\}/iS', "{{Wikify$1|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Clean( ?up)?|CU|Tidy)\}\}/iS', "{{Cleanup|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Linkless|Orphan)\}\}/iS', "{{Orphan|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Unreferenced(sect)?|add references|cite[ -]sources?|cleanup-sources?|needs? references|no sources|no references?|not referenced|references|unref|unsourced)\}\}/iS', "{{Unreferenced|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Uncategori[sz]ed|Uncat|Classify|Category needed|Catneeded|categori[zs]e|nocats?)\}\}/iS', "{{Uncategorized|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Trivia2?|Too ?much ?trivia|Trivia section|Cleanup-trivia)\}\}/iS', "{{Trivia|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(deadend|DEP)\}\}/iS', "{{Deadend|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(copyedit|g(rammar )?check|copy-edit|cleanup-copyedit|cleanup-english)\}\}/iS', "{{Copyedit|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(sources|refimprove|not verified)\}\}/iS', "{{Refimprove|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Expand)\}\}/iS', "{{Expand|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		//$text = preg_replace( '/\{\{(?:\s*[Tt]emplate:)?(\s*(?:[Cc]n|[Ff]act|[Pp]roveit|[Cc]iteneeded|[Uu]ncited|[Cc]itation needed)\s*(?:\|[^{}]+(?\<!\|\s*date\s*=[^{}]+))?)\}\}/iS', "{{$1|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(COI|Conflict of interest|Selfpromotion)\}\}/iS', "{{COI|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?(Intro( |-)?missing|Nointro(duction)?|Lead missing|No ?lead|Missingintro|Opening|No-intro|Leadsection|No lead section)\}\}/iS', "{{Intro missing|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );
		$text = preg_replace( '/\{\{(template:)?([Pp]rimary ?[Ss]ources?|[Rr]eliable ?sources)\}\}/iS', "{{Primary sources|date={{subst:CURRENTMONTHNAME}} {{subst:CURRENTYEAR}}}}", $text );

		//Other template fixes
		$text = preg_replace( '/\{\{(?:Template:)?(Dab|Disamb|Disambiguation)\}\}/iS', "{{Disambig}}", $text );
		$text = preg_replace( '/\{\{(?:Template:)?(Bio-dab|Hndisambig)/iS', "{{Hndis", $text );
		$text = preg_replace( '/\{\{(?:Template:)?(Prettytable|Prettytable100)\}\}/iS', "{{subst:Prettytable}}", $text );
		$text = preg_replace( '/\{\{(?:[Tt]emplate:)?((?:BASE)?PAGENAMEE?\}\}|[Ll]ived\||[Bb]io-cats\|)/iS', "{{subst:$1", $text );
		$text = preg_replace( '/({{\s*[Aa]rticle ?issues\s*(?:\|[^{}]*|\|)\s*[Dd]o-attempt\s*=\s*)[^{}\|]+\|\s*att\s*=\s*([^{}\|]+)(?=\||}})/iS', "$1$2", $text );
		$text = preg_replace( '/({{\s*[Aa]rticle ?issues\s*(?:\|[^{}]*|\|)\s*[Cc]opyedit\s*)for\s*=\s*[^{}\|]+\|\s*date(\s*=[^{}\|]+)(?=\||}})/iS', "$1$2", $text );
		$text = preg_replace( '/\{\{[Aa]rticle ?issues(?:\s*\|\s*(?:section|article)\s*=\s*[Yy])?\s*\}\}/iS', "", $text );
		$text = preg_replace( '/\{\{[Cc]ommons\|\s*[Cc]ategory:\s*([^{}]+?)\s*\}\}/iS', "{{Commons category|$1}}", $text );
		$text = preg_replace( '/(?!{{[Cc]ite wikisource)(\{\{\s*(?:[Cc]it[ae]|[Aa]rticle ?issues)[^{}]*)\|\s*(\}\}|\|)/iS', "$1$2", $text );
		$text = preg_replace( '/({{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)\s*=\s*([^\|}{]+?)\s*\|((?:[^{}]*?\|)?\s*)\2(\s*=\s*)\3(\s*(\||\}\}))/iS', "$1$4$2$5$3$6", $text );
		$text = preg_replace( '/(\{\{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)(\s*=\s*[^\|}{]+(?:\|[^{}]+?)?)\|\s*\2\s*=\s*(\||\}\})/iS', "$1$2$3$4", $text );
		$text = preg_replace( '/(\{\{\s*[Aa]rticle ?issues[^{}]*\|\s*)(\w+)\s*=\s*\|\s*((?:[^{}]+?\|)?\s*\2\s*=\s*[^\|}{\s])/iS', "$1$3", $text );
		$text = preg_replace( '/{{\s*(?:[Cc]n|[Ff]act|[Pp]roveit|[Cc]iteneeded|[Uu]ncited)(?=\s*[\|}])/S', "{{Citation needed", $text );

		return $text;
	}

	public static function fixHTML( $text ) {

		$text = preg_replace( '/(\n\{\| class="wikitable[^\n]+\n\|-[^\n]*)(bgcolor\W+CCC+|background\W+ccc+)(?=\W+\n!)/mi', '$1', $text );

		$text = preg_replace( '/(\n([^<\n]|<(?!br[^>]*>))+\w+[^\w\s<>]*)<br[ /]*>(?=\n[*#:;]|\n?<div|\n?<blockquote)/mi', '$1', $text );

		$text = preg_replace(
			array(
				'/(<br[^</>]*>)\n?</br>/mi',
				'/<[/]?br([^{/}<>]*?/?)>/mi',
				'/<br\s\S*clear\S*(all|both)\S*[\s/]*>/i',
				'/<br\s\S*clear\S*(left|right)\S*[\s/]*>/',
			),
			array(
				'$1',
				'<br$1>',
				'{{-}}',
				'{{clear$1}}'
			),
			$text
		);

		$text = preg_replace( '/(<font\b[^<>]*)> *\n?<font\b([^<>]*>)((?:[^<]|<(?!/?font))*?</font> *\n?)</font>/mi', '$1$2$3', $text );

		$text = preg_replace( '/<font ([^<>]*)>\[\[([^[\]{|}]+)\|([^[\]\n]*?)\]\]</font>/mi', '[[$2|<font $1>$3</font>]]', $text );

		$text = preg_replace( '/<font(( +style="[^"]+")+)>(?!\[\[)((?:[^<]|<(?!/?font))*?)(?<!\]\])</font>/mi', '<span$1>$3</span>', $text );

		return $text;

	}

	public static function fixHyperlinking( $text ) {

		$text = preg_replace( '/(http:\/\/[^][<>\s"|])(&client=firefox-a|&lt=)(?=[][<>\s"|&])/', '$1', $text );

		$text = str_replace( '[{{SERVER}}{{localurl:', '[{{fullurl:', $text );

		$text = preg_replace( '/[(](?:see|) *(http:\/\/[^][<>"\s(|)]+[\w=\/&])\s?[)]/i', '<$1>', $text );

		$text = preg_replace( '/\[\[(https?:\/\/[^\]\n]+?)\]\]/', '[$1]', $text );
		$text = preg_replace( '/\[\[(https?:\/\/.+?)\]/', '[$1]', $text );

		$text = preg_replace( '/\[\[(:?)Image:([^][{|}]+\.(pdf|midi?|ogg|ogv|xcf))(?=\||\]\])/i', '[[$1File:$2', $text );

		$text = preg_replace(
			array(
				'/(http:\/* *){2,}(?=[a-z0-9:.\-]+\/)/i',
				"/(\[\w+:\/\/[^][<>\"\s]+?)''/i",
				'/\[\n*(\w+:\/\/[^][<>"\s]+ *(?:(?<= )[^\n\]<>]*?|))\n([^[\]<>{}\n=@\/]*?) *\n*\]/i',
				'/\[(\w+:\/\/[^][<>"\s]+) +([Cc]lick here|[Hh]ere|\W|→|[ -\/;-@]) *\]/i',
			),
			array(
				'http://',
				"$1 ''",
				'[$1 $2]',
				'$2 [$1]',
			),
			$text
		);

		$text = preg_replace( '/(\[\[(?:File|Image):[^][<>{|}]+)#(|filehistory|filelinks|file)(?=[\]|])/i', '$1', $text );

		$text = preg_replace( '/\[http://(www\.toolserver\.org|toolserver\.org|tools\.wikimedia\.org|tools\.wikimedia\.de)/([^][<>"\s;?]*)\?? ([^]\n]+)\]/', '[[tools:$2|$3]]', $text );

		return $text;

	}

	public static function fixTypos( $text, $title ) {

		if( !count( self::$typo_list ) ) {
			global $script;

			$str = $script->getWiki()->initPage( 'Wikipedia:AutoWikiBrowser/Typos' )->get_text();

			foreach( explode( "\n", $str ) as $line ){
				if( substr( $line, 0, 5 ) == "<Typo" ) {

					preg_match( '/\<Typo word=\"(.*)\" find=\"(.*)\" replace=\"(.*)\" \/\>/', $line, $m );

					if( !empty( $m[2] ) && !empty( $m[3] ) ) {
						self::$typo_list[] = array( 'word' => $m[1], 'find' => $m[2], 'replace' => $m[3] );
					}
					//<Typo word="the first time" find="\b(T|t)he\s+(very\s+)?fr?ist\s+time\b" replace="$1he $2first time" />
				}
			}

		}

		$run_times = array();

		shuffle( self::$typo_list ); //So that if it quits randomly, it will give equal prejudice to each typo. 

		if( !count( self::$typo_list ) || preg_match( '/133t|-ology|\\(sic\\)|\\[sic\\]|\\[\'\'sic\'\'\\]|\\{\\{sic\\}\\}|spellfixno/', $text ) ) return $text;

		foreach( self::$typo_list as $typo ){
			//Skip typos in links
			$time = microtime( 1 );

			if( @preg_match( '/' . $typo['find'] . '/S', $title ) ) continue; //Skip if matches title

			if( @preg_match( "/(\{\{|\[\[)[^\[\]\r\n\|\{\}]*?" . $typo['find'] . "[^\[\]\r\n\|\{\}]*?(\]\]|\}\})/S", $text ) ) continue;

			$text2 = @preg_replace( '/' . $typo['find'] . '/S', $typo['replace'], $text );
			if( !is_null( $text2 ) ) $text = $text2;
			$run_times[$typo['word']] = number_format( microtime( 1 ) - $time, 2 );
		}

		return $text;
	}


}





