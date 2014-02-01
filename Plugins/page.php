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
 * Page object
 */

/**
 * Page class, defines methods that all get/modify page info
 */
class Page {
	
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access protected
	 */
	protected $wiki;
	
	/**
	 * Title of the page
	 * 
	 * @var string
	 * @access protected
	 */
	protected $title;
	
	/**
	 * The ID of the page
	 * 
	 * @var int
	 * @access protected
	 */
	protected $pageid;
	
	/**
	 * If the page exists or not
	 * 
	 * (default value: true)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $exists = true;
	
	/**
	 * Whether or not the page is a special page
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $special = false;
	
	/**
	 * When retriving the page information was a redirect followed
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $redirectFollowed = false;
	
	/**
	 * The page title without the namespace bit
	 * 
	 * @var string
	 * @access protected
	 */
	protected $title_wo_namespace;
	
	/**
	 * The ID of the namespace
	 * 
	 * @var int
	 * @access protected
	 */
	protected $namespace_id;
	
	/**
	 * Page text
	 * 
	 * @var string
	 * @access protected
	 */
	protected $content;
	
	/**
	 * Templates used in the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $templates = array();
	
	/**
	 * Protection information for the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $protection = array();
	
	/**
	 * Cateogories that the page is in
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $categories = array();
	
	/**
	 * Images used in the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $images = array();
	
	/**
	 * Internal links in the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $links = array();
	
	/**
	 * Timestamp of the last edit
	 * 
	 * @var string
	 * @access protected
	 */
	protected $lastedit;
	
	/**
	 * Length of the page in bytes
	 * 
	 * @var int
	 * @access protected
	 */
	protected $length;
	
	/**
	 * Amount of hits (views) the page has
	 * 
	 * @var int
	 * @access protected
	 */
	protected $hits;
	
	/**
	 * Language links on the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $langlinks = array();
	
	/**
	 * External links on the page
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $extlinks = array();
    
    /**
     * Interwiki links on the page
     * 
     * (default value: array())
     * 
     * @var array
     * @access protected
     */
    protected $iwlinks = array();
	
	/**
	 * Time of script start.  Must be set manually.
	 *
	 * (default null)
	 * 
	 * @var mixed
	 * @access protected
	 */
	protected $starttimestamp;
    
    /**
     * Page ID of the talk page.
     * 
     * (default null)
     * 
     * @var int
     * @access protected
     */
	protected $talkid;
    
    /**
     * Whether the page is watched by the user
     * 
     * (default false)
     * 
     * @var bool
     * @access protected
     */
    protected $watched = false;
    
    /**
     * Number of watchers
     * 
     * (default 0)
     * 
     * @var int
     * @access protected
     */
    protected $watchers = 0;
    
    /**
     * Watchlist notification timestamp
     * 
     * (default null)
     * 
     * @var string
     * @access protected
     */
    protected $watchlisttimestamp;
    
    /**
     * Page ID of parent page
     * 
     * (default null)
     * 
     * @var int
     * @access protected
     */
    protected $subjectid;
    
    /**
     * Full urls of the page
     * 
     * (default array())
     * 
     * @var array
     * @access protected
     */
    protected $urls = array();
    
    /**
     * Whether the page can be read by a user
     * 
     * (default false)
     * 
     * @var bool
     * @access protected
     */
    protected $readable = false;
    
    /**
     * EditFormPreloadText
     * 
     * (default null)
     * 
     * @var string
     * @access protected
     */
    protected $preload;
    
    /**
     * Page's title formatting 
     * 
     * (default null)
     * 
     * @var string
     * @access protected
     */
    protected $displaytitle;
    
    /**
     * Page properties 
     * 
     * (default array())
     * 
     * @var array
     * @access protected
     */
    protected $properties = array();
	
	/**
	 * Construction method for the Page class
	 * 
	 * @access public
	 * @param Wiki $wikiClass The Wiki class object
	 * @param mixed $title Title of the page (default: null)
	 * @param mixed $pageid ID of the page (default: null)
	 * @param bool $followRedir Should it follow a redirect when retrieving the page (default: true)
	 * @param bool $normalize Should the class automatically normalize the title (default: true)
	 * @param string $timestamp Set the start of a program or start reference to avoid edit conflicts.
	 * @return void
	 */
	function __construct( $wikiClass, $title = null, $pageid = null, $followRedir = true, $normalize = true, $timestamp = null ) {
		$this->wiki = $wikiClass;
		
		if( is_null( $title ) && is_null( $pageid ) ) {
			throw new NoTitle();
		}
		
		if( !is_null( $title ) && $normalize ) {
			$title = str_replace( '_', ' ', $title );
			$title = str_replace( '%20', ' ', $title );
			if( $title[0] == ":" ){
				$title = substr($title, 1);
			}
			$chunks = explode( ':', $title, 2 );
			if(count($chunks) != 1){
				$namespace = strtolower( trim( $chunks[0] ) );
				$namespaces = $this->wiki->get_namespaces();
				if( $namespace == $namespaces[-2] || $namespace == "media" ){
					// Media or local variant, translate to File:
					$title = $namespaces[6] . ":" . $chunks[1];
				}
			}
		}
		
		$this->title = $title;
		
		$pageInfoArray = array();
		
		if( !is_null( $pageid ) ) {
			$pageInfoArray['pageids'] = $pageid;
			$peachout = "page ID $pageid";
		}
		else {
			$pageInfoArray['titles'] = $title;
			$peachout = $title;
		}
		
		if( $followRedir ) $pageInfoArray['redirects'] = '';
		
		pecho( "Getting page info for {$peachout}..\n\n", PECHO_NORMAL );
		
		$info = $this->get_metadata( $pageInfoArray );
		if( !is_null( $timestamp ) ) $this->starttimestamp = $timestamp;
		
		if( isset( $info['query']['redirects'][0] ) ) {
			$this->redirectFollowed = true;
		}

	}
	
	/**
	 * Returns page history. Can be specified to return content as well
	 * 
	 * @access public
	 * @param int $count Revisions to return (default: 1)
	 * @param string $dir Direction to return revisions (default: "older")
	 * @param bool $content Should content of that revision be returned as well (default: false)
	 * @param int $revid Revision ID to start from (default: null)
	 * @param bool $rollback_token Should a rollback token be returned (default: false)
	 * @return array Revision data
	 */
	public function history( $count = 1, $dir = "older", $content = false, $revid = null, $rollback_token = false, $recurse = false ) {
		if( !$this->exists ) return array();
		
		$historyArray = array(
			'action' => 'query',
			'prop' => 'revisions',
			'titles' => $this->title, 
			'rvprop' => 'timestamp|ids|user|comment',
			'rvdir' => $dir,
			
		);
		
		if( $content ) $historyArray['rvprop'] .= "|content";
		
		if( !is_null( $revid ) ) $historyArray['rvstartid'] = $revid;
		if( !is_null( $count ) ) $historyArray['rvlimit'] = $count;
		
		if( $rollback_token ) $historyArray['rvtoken'] = 'rollback';
		
		if( !$recurse ) pecho( "Getting page history for {$this->title}..\n\n", PECHO_NORMAL );
		
		if( is_null( $count ) ) {
			$history = $ei = $this->history( $this->wiki->get_api_limit() + 1, $dir, $content, $revid, $rollback_token, true );
			while( !is_null( $ei[1] ) ) {
				$ei = $this->history( $this->wiki->get_api_limit() + 1, $dir, $content, $ei[1], $rollback_token, true );
				foreach( $ei[0] as $eg ) {
					$history[0][] = $eg;
				}
			}
			
			return $history[0];
			
		}
		else {
			$historyResult = $this->wiki->apiQuery($historyArray);

			if( $recurse ) {
				if( isset( $historyResult['query-continue'] ) ) return array( $historyResult['query']['pages'][$this->pageid]['revisions'], $historyResult['query-continue']['revisions']['rvcontinue'] );
				return array( $historyResult['query']['pages'][$this->pageid]['revisions'], null );
			}
			else {
				return $historyResult['query']['pages'][$this->pageid]['revisions'];
			}
		}

	}
	
	/**
	 * Retrieves text from a page, or a cached copy unless $force is true
	 * 
	 * @access public
	 * @param bool $force Grab text from the API, don't use the cached copy (default: false)
	 * @param string|int $section Section title or ID to retrieve
	 * @return string Page content
	 */
	public function get_text( $force = false, $section = null ) {
		pecho( "Getting page content for {$this->title}..\n\n", PECHO_NOTICE );
		
		if( !$this->exists ) return null;
			
		if( !is_null( $section ) ) {
			if( empty($this->content) ) {
				$this->content = $this->history( 1, "older", true );
				$this->content = $this->content[0]['*'];
			}
			
			$sections = $this->wiki->apiQuery(array(
				'action' => 'parse',
				'page' => $this->title,
				'prop' => 'sections'
			));
			
			if( !is_numeric( $section ) ) {
				foreach( $sections['parse']['sections'] as $section3 ) {
					if( $section3['line'] == $section ) {
						$section = $section3['number'];
					}
				}
			}
			
			if( !is_numeric( $section ) ) {
				pecho( "Warning: Section not found.\n\n", PECHO_WARN );
				return false;
			}
			
			$offsets = array( '0' => '0' );
			
			if( $this->wiki->get_mw_version() < '1.16' ) {
					
				//FIXME: Implement proper notice suppression
				
				$ids = array();
				
				foreach( $sections['parse']['sections'] as $section3 ) {
					$ids[$section3['line']] = $section3['number'];
				}
				
				$regex = '/^(=+)\s*(.*?)\s*(\1)\s*/m';

				preg_match_all($regex, $this->content, $m, PREG_OFFSET_CAPTURE|PREG_SET_ORDER );
				
				foreach( $m as $id => $match ) {
					$offsets[$id + 1] = $match[0][1];
				}
				
			}
			else {
				foreach( $sections['parse']['sections'] as $section2 ) {
					$offsets[$section2['number']] = $section2['byteoffset'];
				}
			}
			
			if( intval( $section ) != count( $offsets ) - 1 ) {
				$length = $offsets[$section + 1] - $offsets[$section];
			}
			
			if( isset( $length ) ) {
				$substr = mb_substr( $this->content, $offsets[$section], $length );
			}
			else {
				$substr = mb_substr( $this->content, $offsets[$section] );
			}
			
			return $substr;
		}
		else {
			if( !$force && !empty($this->content) ) {
				return $this->content;
			}
			
			$this->content = $this->history( 1, "older", true );
			$this->content = $this->content[0]['*'];
			
			return $this->content;
		}
	}
	
	/**
	 * Returns the pageid of the page.
	 * 
	 * @return int Pageid
	 */
	public function get_id() {
		return $this->pageid;
	}
	
	/**
	 * Returns if the page exists
	 * 
	 * @access public
	 * @return bool Exists
	 * @deprecated since 18 June 2013
	 */
	public function exists() {
		Peachy::deprecatedWarn( 'Page::exists()', 'Page::get_exists()' );
		return $this->exists;
	}
	
	/**
	 * Returns if the page exists
	 * 
	 * @access public
	 * @return bool Exists
	 */
	public function get_exists() {
		return $this->exists;
	}
	
	/**
	 * Returns links on the page.
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#links_.2F_pl
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @param array $namespace Show links in this namespace(s) only.  Default array()
     * @param array $titles Only list links to these titles.  Default array()
     * @return bool|array False on error, array of link titles
	 */
	public function get_links( $force = false, $namespace = array(), $titles = array() ) {

		if( !$force && count( $this->links ) > 0 ) {
			return $this->links;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'links',
			'titles' => $this->title,
			'_code' => 'pl',
			'_lhtitle' => 'links'
		);
        
        if( !empty( $namespace ) ) $tArray['plnamespace'] = implode( '|', $namespace );
        if( !empty( $titles ) ) $tArray['pltitles'] = implode( '|', $titles );
		
		$this->links = array();
		
		pecho( "Getting internal links on {$this->title}..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $link){
				$this->links[] = $link['title'];
			}
		}
		
		return $this->links;
	}
	
	/**
	 * Returns templates on the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#templates_.2F_tl
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @param array $namespace Show templates in this namespace(s) only. Default array().
     * @param array $templates Only list these templates. Default array()
     * @return bool|array False on error, array of template titles
	 */
	public function get_templates( $force = false, $namespace = array(), $template = array() ) {

		if( !$force && count( $this->templates ) > 0 && empty( $namespace ) && empty( $template ) ) {
			return $this->templates;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'templates',
			'titles' => $this->title,
			'_code' => 'tl',
			'_lhtitle' => 'templates'
		);
        if( !empty( $namespace ) ) $tArray['tlnamespace'] = implode( '|', $namespace );
		if( !empty( $template ) ) $tArray['tltemplates'] = implode( '|', $template );

		$this->templates = array();
		
		pecho( "Getting templates transcluded on {$this->title}..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $template){
				$this->templates[] = $template['title'];
			}
		}
		
		return $this->templates;
	}
    
    /**
     * Get various properties defined in the page content
     * 
     * @access public
     * @link https://www.mediawiki.org/wiki/API:Properties#pageprops_.2F_pp
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return bool|array False on error, array of template titles
     */
    public function get_properties( $force = false ) {

        if( !$force && count( $this->properties ) > 0 ) {
            return $this->properties;
        }

        if( !$this->exists ) return array();
        
        $tArray = array(
            'prop' => 'pageprops',
            'titles' => $this->title,
            '_code' => 'pp'
        );
        
        $this->properties = array();
        
        pecho( "Getting page properties on {$this->title}..\n\n", PECHO_NORMAL );
        
        $this->properties = $this->wiki->listHandler($tArray);
        
        return $this->properties;
    }
	
	/**
	 * Returns categories of page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#categories_.2F_cl
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @param array|string $prop Which additional properties to get for each category. Default all
     * @param bool $hidden Show hidden categories. Default false
     * @return bool|array False on error, returns array of categories
	 */
	public function get_categories( $force = false, $prop = array( 'sortkey', 'timestamp', 'hidden' ), $hidden = false ) {

		if( !$force && count( $this->categories ) > 0 ) {
			return $this->categories;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'categories',
			'titles' => $this->title,
			'_code' => 'cl',
			'_lhtitle' => 'categories',
            'clprop' => implode( '|', $prop )
		);
        
        if( $hidden ) $tArray['clshow'] = 'yes';
		
		$this->categories = array();
		
		pecho( "Getting categories {$this->title} is part of..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $category){
				$this->categories[] = $category['title'];
			}
		}
		
		return $this->categories;
			
	}
	
	/**
	 * Returns images used in the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#images_.2F_im
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @param string|array $images Only list these images. Default null.
     * @return bool|array False on error, returns array of image titles
	 */
	public function get_images( $force = false, $images = null ) {
		
		if( !$force && count( $this->images ) > 0 ) {
			return $this->images;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'images',
			'titles' => $this->title,
			'_code' => 'im',
			'_lhtitle' => 'images'
		);
		
		$this->images = array();
        
        if( !is_null($images) ) {
            if( is_array($images) ) $tArray['imimages'] = implode( '|', $images );
            else $tArray['imimage'] = $images;
        }
		
		pecho( "Getting images used on {$this->title}..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $image){
				$this->images[] = $image['title'];
			}
		}
		
		return $this->images;
			
	
	}
	
	/**
	 * Returns external links used in the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#extlinks_.2F_el
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @return bool|array False on error, returns array of URLs
	 */
	public function get_extlinks( $force = false ) {

		if( !$force && count( $this->extlinks ) > 0 ) {
			return $this->extlinks;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'extlinks',
			'titles' => $this->title,
			'_code' => 'el',
			'_lhtitle' => 'extlinks'
		);
		
		$this->extlinks = array();
		
		pecho( "Getting external links used on {$this->title}..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $extlink){
				$this->extlinks[] = $extlink['*'];
			}
		}
		
		return $this->extlinks;
	}
	
	/**
	 * Returns interlanguage links on the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#langlinks_.2F_ll
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @param bool $fullurl Include a list of full of URLs.  Output formatting changes.  Requires force parameter to be true to return a different result.
     * @param string $title Link to search for. Must be used with $lang.  Default null
     * @param string $lang Language code.  Default null
     * @return bool|array False on error, returns array of links in the form of lang:title
	 */
	public function get_langlinks( $force = false, $fullurl = false, $title = null, $lang = null ) {
		if( !$force && count( $this->langlinks ) > 0 ) {
			return $this->langlinks;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'prop' => 'langlinks',
			'titles' => $this->title,
			'_code' => 'll',
			'_lhtitle' => 'langlinks'
		);
        
        if( !is_null( $lang ) ) $tArray['lllang'] = $lang;
        if( !is_null( $title ) ) $tArray['lltitle'] = $title;
        if( $fullurl ) $tArray['llurl'] = 'yes';
		
		$this->langlinks = array();
		
		pecho( "Getting all interlanguage links for {$this->title}..\n\n", PECHO_NORMAL );
		
		$result = $this->wiki->listHandler($tArray);
		
		if( count( $result ) > 0 ) {
			foreach($result[0] as $langlink){
			    if( $fullurl ) $this->langlinks[] = array( 'link'=>$langlink['lang'] . ":" . $langlink['*'], 'url'=>$langlink['url'] ); 
                else $this->langlinks[] = $langlink['lang'] . ":" . $langlink['*'];
            }
		}
		
		return $this->langlinks;
	}
    
    /**
     * Returns interwiki links on the page
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#langlinks_.2F_ll
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @param bool $fullurl Include a list of full of URLs.  Output formatting changes.  Requires force parameter to be true to return a different result.
     * @param string $title Interwiki link to search for. Must be used with $prefix.  Default null
     * @param string $prefix Prefix for the interwiki.  Default null
     * @return bool|array False on error, returns array of links in the form of lang:title
     */
    public function get_interwikilinks( $force = false, $fullurl = false, $title = null, $prefix = null ) {
        if( !$force && count( $this->iwlinks ) > 0 ) {
            return $this->iwlinks;
        }

        if( !$this->exists ) return array();
        
        $tArray = array(
            'prop' => 'iwlinks',
            'titles' => $this->title,
            '_code' => 'iw',
            '_lhtitle' => 'iwlinks'
        );
        
        if( !is_null( $prefix ) ) $tArray['iwprefix'] = $prefix;
        if( !is_null( $title ) ) $tArray['iwtitle'] = $title;
        if( $fullurl ) $tArray['iwurl'] = 'yes';
        
        $this->iwlinks = array();
        
        pecho( "Getting all interwiki links for {$this->title}..\n\n", PECHO_NORMAL );
        
        $result = $this->wiki->listHandler($tArray);
        
        if( count( $result ) > 0 ) {
            foreach($result[0] as $iwlinks){
                if( $fullurl ) $this->iwlinks[] = array( 'link'=>$iwlinks['prefix'] . ":" . $iwlinks['*'], 'url'=>$iwlinks['url'] ); 
                else $this->iwlinks[] = $iwlinks['prefix'] . ":" . $iwlinks['*'];
            }
        }
        
        return $this->iwlinks;
    }
	
	/**
	 * Returns the protection level of the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
	 * @param bool $force Force use of API, won't use cached copy (default: false)
	 * @return bool|array False on error, returns array with protection levels
	 */
	public function get_protection( $force = false ) {

		if( !$force ) {
			return $this->protection;
		}

		if( !$this->exists ) return array();
		
		$tArray = array(
			'action' => 'query',
			'prop' => 'info',
			'inprop' => 'protection',
			'titles' => $this->title,
		);
		
		pecho( "Getting protection levels for {$this->title}..\n\n", PECHO_NORMAL );
			
		$tRes = $this->wiki->apiQuery( $tArray );
		
		$this->protection = $tRes['query']['pages'][$this->pageid]['protection'];
		
		return $this->protection;

	}
    
    /**
     * Returns the page ID of the talk page for each non-talk page
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return int Null or empty if no id exists.
     */
    public function get_talkID( $force = false ) {
        
        if( !$force ) {
            return $this->talkid;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'talkid',
            'titles' => $this->title,
        );
        
        pecho( "Getting talk page ID for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['talkid']) )$this->talkid = $tRes['query']['pages'][$this->pageid]['talkid'];
        else $this->talkid = null;
        
        return $this->talkid;    
    }
    
    /**
     * Returns the watch status of the page
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return bool
     */
    public function is_watched( $force = false ) {
        
        if( !$force ) {
            return $this->watched;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'watched',
            'titles' => $this->title,
        );
        
        pecho( "Getting watch status for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['watched']) )$this->watched = true;
        else $this->watched = false;
        
        return $this->watched;    
    }
    
    /**
     * Returns the count for the number of watchers of a page.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return int
     */
    public function get_watchcount( $force = false ) {
        
        if( !$force ) {
            return $this->watchers;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'watchers',
            'titles' => $this->title,
        );
        
        pecho( "Getting watch count for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['watchers']) )$this->watchers = $tRes['query']['pages'][$this->pageid]['watchers'];
        else $this->watchers = 0;
        
        return $this->watchers;    
    }
    
    /**
     * Returns the watchlist notification timestamp of each page.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return string
     */
    public function get_notificationtimestamp( $force = false ) {
        
        if( !$force ) {
            return $this->watchlisttimestamp;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'notificationtimestamp',
            'titles' => $this->title,
        );
        
        pecho( "Getting the notification timestamp for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['notificationtimestamp']) )$this->watchlisttimestamp = $tRes['query']['pages'][$this->pageid]['notificationtimestamp'];
        else $this->watchlisttimestamp = 0;
        
        return $this->watchlisttimestamp;    
    }
    
    /**
     * Returns the page ID of the parent page for each talk page.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return int Null if it doesn't exist.
     */
    public function get_subjectid( $force = false ) {
        
        if( !$force ) {
            return $this->subjectid;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'subjectid',
            'titles' => $this->title,
        );
        
        pecho( "Getting the parent page ID for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['subjectid']) )$this->subjectid = $tRes['query']['pages'][$this->pageid]['subjectid'];
        else $this->subjectid = null;
        
        return $this->subjectid;    
    }
    
    /**
     * Gives a full URL to the page, and also an edit URL.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return array
     */
    public function get_urls( $force = false ) {
        
        if( !$force ) {
            return $this->urls;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'url',
            'titles' => $this->title,
        );
        
        pecho( "Getting the URLs for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        $this->urls = array();
        
        if( isset( $tRes['query']['pages'][$this->pageid]['fullurl'] ) ) $this->urls['full'] = $info['fullurl'];
        if( isset( $tRes['query']['pages'][$this->pageid]['editurl'] ) ) $this->urls['edit'] = $info['editurl'];
        
        return $this->urls;    
    }
    
    /**
     * Returns whether the user can read this page.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return int Null if it doesn't exist.
     */
    public function get_readability( $force = false ) {
        
        if( !$force ) {
            return $this->readable;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'readable',
            'titles' => $this->title,
        );
        
        pecho( "Getting the readability status for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['readable']) )$this->readable = true;
        else $this->readable = false;
        
        return $this->readable;    
    }
    
    /**
     * Gives the text returned by EditFormPreloadText.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return string
     */
    public function get_preload( $force = false ) {
        
        if( !$force ) {
            return $this->preload;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'preload',
            'titles' => $this->title,
        );
        
        pecho( "Getting the preload text for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['preload']) )$this->preload = $tRes['query']['pages'][$this->pageid]['preload'];
        else $this->preload = null;
        
        return $this->preload;    
    }
    
    /**
     * Gives the way the page title is actually displayed.
     * 
     * @access public
     * @link http://www.mediawiki.org/wiki/API:Query_-_Properties#info_.2F_in
     * @param bool $force Force use of API, won't use cached copy (default: false)
     * @return string
     */
    public function get_displaytitle( $force = false ) {
        
        if( !$force ) {
            return $this->displaytitle;
        }
        
        $tArray = array(
            'action' => 'query',
            'prop' => 'info',
            'inprop' => 'displaytitle',
            'titles' => $this->title,
        );
        
        pecho( "Getting the title formatting for {$this->title}..\n\n", PECHO_NORMAL );
            
        $tRes = $this->wiki->apiQuery( $tArray );
        
        if( isset($tRes['query']['pages'][$this->pageid]['displaytitle']) )$this->displaytitle = $tRes['query']['pages'][$this->pageid]['displaytitle'];
        else $this->displaytitle = null;
        
        return $this->displaytitle;    
    }
	
	/**
	 * Edits the page
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
	 * @param string $text Text of the page that will be saved
	 * @param string $summary Summary of the edit (default: "")
	 * @param bool $minor Minor edit (default: false)
	 * @param bool $bot Mark as bot edit (default: true)
	 * @param bool $force Override nobots check (default: false)
	 * @param string $pend Set to 'pre' or 'ap' to prepend or append, respectively (default: null)
	 * @param bool $create Set to 'never', 'only', or 'recreate' to never create a new page, only create a new page, or override errors about the page having been deleted, respectively (default: false) 
	 * @param string $section Section number. 0 for the top section, 'new' for a new section.  Default null.
	 * @param string $sectiontitle The title for a new section. Default null.
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @return int|bool The revision id of the successful edit, false on failure.
	 */
	public function edit( 
		$text, 
		$summary = "", 
		$minor = false, 
		$bot = true, 
		$force = false,
		$pend = false, 
		$create = false,
		$section = null,
		$sectiontitle = null,
		$watch = null
	)  {
        global $notag, $tag;
		
		$tokens = $this->wiki->get_tokens();
		
		if( !$notag ) $summary .= $tag;
		
		if( $tokens['edit'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		elseif( $tokens['edit'] == '' ) {
			pecho( "User is not allowed to edit {$this->title}\n\n", PECHO_FATAL );
			return false;
		}
		
		if( mb_strlen( $summary, '8bit' ) > 255 ) {
			pecho( "Summary is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
			return false;
		}
		
		pecho( "Making edit to {$this->title}...\n\n", PECHO_NORMAL );
		
		$editarray = array(
			'title' => $this->title,
			'action' => 'edit',
			'token' => $tokens['edit'],
			'basetimestamp' => $this->lastedit,
			'md5' => md5($text),
			'text' => $text,
			'assert' => 'user',
		);
		if( !is_null($this->starttimestamp) ) $editarray['starttimestamp'] = $this->starttimestamp;
		if( !is_null( $section ) ) {
			if( $section == 'new' ) {
				if( is_null( $sectiontitle ) ) {
					pecho( "Error: sectiontitle parameter must be specified.  Aborting...\n\n", PECHO_FATAL );
					return false;
				} else {
					$editarray['section'] = 'new';
					$editarray['sectiontitle'] = $sectiontitle;
				}
			} else $editarray['section'] = $section;
		}
		
		if( $pend == "pre" ) {
			$editarray['prependtext'] = $text;
		}
		elseif( $pend == "ap" ) {
			$editarray['appendtext'] = $text;
		}
		
		if( !is_null( $watch ) ) {
			if( $watch ) $editarray['watchlist'] = 'watch';
			elseif( !$watch ) $editarray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $editarray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
		if( $create == "never" ) $editarray['nocreate'] = 'yes';
		elseif( $create == "only" ) $editarray['createonly'] = 'yes';
		elseif( $create == "recreate" ) $editarray['recreate'] = 'yes';
		
		if( $this->wiki->get_maxlag() ) $editarray['maxlag'] = $this->wiki->get_maxlag();
		
		if( !empty( $summary ) ) $editarray['summary'] = $summary;
		
		if( $minor ) $editarray['minor'] = 'yes';
		else $editarray['notminor'] = 'yes';
		
		if( $bot ) $editarray['bot'] = 'yes';
		
		if( !$force ) {
			try {
				$this->preEditChecks( "Edit" );
			}
			catch( EditError $e ) {
				pecho( "Error: $e\n\n", PECHO_FATAL );
				return false;
			}
		}
		
		Hooks::runHook( 'StartEdit', array( &$editarray ) );
		
		pecho( "Making edit to {$this->title}..\n\n", PECHO_NOTICE );
		
		$result = $this->wiki->apiQuery( $editarray, true );
		
		if( isset( $result['edit'] ) ) {
			if( $result['edit']['result'] == "Success" ) {
				if( array_key_exists( 'nochange', $result['edit'] ) ) return $this->lastedit;
				
				$this->__construct( $this->wiki, null, $this->pageid );
				
				if( !is_null( $this->wiki->get_edit_rate() ) && $this->wiki->get_edit_rate() != 0 ) {
					sleep( intval( 60 / $this->wiki->get_edit_rate() ) - 1 );
				}
				
				return $result['edit']['newrevid'];
			}
			else {
				pecho( "Edit error...\n\n" . print_r($result['edit'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Edit error...\n\n" . print_r($result['edit'], true) . "\n\n", PECHO_FATAL );
			return false;
		}
	
	}
	
	/**
	 * Add text to the beginning of the page. Shortcut for Page::edit()
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
	 * @param string $text Text of the page that will be saved
	 * @param string $summary Summary of the edit (default: "")
	 * @param bool $minor Minor edit (default: false)
	 * @param bool $bot Mark as bot edit (default: true)
	 * @param bool $force Override nobots check (default: false)
	 * @param bool $create Set to 'never', 'only', or 'recreate' to never create a new page, only create a new page, or override errors about the page having been deleted, respectively (default: false) 
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @return int|bool The revision id of the successful edit, false on failure.
	 */
	public function prepend( $text, $summary = "", $minor = false, $bot = true, $force = false, $create = false, $watch = null )  {
		return $this->edit( $text, $summary, $minor, $bot, $force, 'pre', $create, null, null, $watch );
	}
	
	/**
	 * Add text to the end of the page. Shortcut for Page::edit()
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
	 * @param string $text Text of the page that will be saved
	 * @param string $summary Summary of the edit (default: "")
	 * @param bool $minor Minor edit (default: false)
	 * @param bool $bot Mark as bot edit (default: true)
	 * @param bool $force Override nobots check (default: false)
	 * @param bool $create Set to 'never', 'only', or 'recreate' to never create a new page, only create a new page, or override errors about the page having been deleted, respectively (default: false) 
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @return int|bool The revision id of the successful edit, false on failure.
	 */
	public function append( $text, $summary = "", $minor = false, $bot = true, $force = false, $create = false, $watch = null )  {
		return $this->edit( $text, $summary, $minor, $bot, $force, 'ap', $create, null, null, $watch );
	}
	
	/**
	 * Create a new section.  Shortcut for Page::edit()
	 *
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Edit_-_Create%26Edit_pages
	 * @param string $text Text of the page that will be saved
	 * @param string $sectiontitle The title for a new section. Default null.
	 * @param string $summary Summary of the edit (default: "")
	 * @param bool $minor Minor edit (default: false)
	 * @param bool $bot Mark as bot edit (default: true)
	 * @param bool $force Override nobots check (default: false)* @param bool $create Set to 'never', 'only', or 'recreate' to never create a new page, only create a new page, or override errors about the page having been deleted, respectively (default: false) 
	 * @param bool $create Set to 'never', 'only', or 'recreate' to never create a new page, only create a new page, or override errors about the page having been deleted, respectively (default: false) 
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @return int|bool The revision id of the successful edit, false on failure.
	 */
	public function newsection( $text, $sectiontitle, $summary = null, $minor = false, $bot = true, $force = false, $create = false, $watch = null ) {
		if( is_null( $summary ) ) $summary = "/* ".$sectiontitle." */ new section";
		return $this->edit( $text, $summary, $minor, $bot, $force, false, $create, 'new', $sectiontitle, $watch );
	}
	
	/*
	 * Undoes one or more edits. (Subject to standard editing restrictions.)
	 *
	 * @access public
	 * @param bool $force Force an undo, despite e.g. new messages (default false).
	 * @param string $summary Override the default edit summary (default null).
	 * @param int $revisions The number of revisions to undo (default 1).
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @return int The new revision id of the page edited.
	 */
	public function undo($summary = null, $revisions = 1, $force = false, $watch = null) {
		global $notag, $tag;
        $info = $this->history($revisions);
		$oldrev = $info[(count($info) - 1)]['revid'];
		$newrev = $info[0]['revid'];
		
		$tokens = $this->wiki->get_tokens();
		
		if( $tokens['edit'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		elseif( $tokens['edit'] == '' ) {
			pecho( "User is not allowed to edit {$this->title}\n\n", PECHO_FATAL );
			return false;
		}
		
		$params = array(
			'title' => $this->title,
			'action' => 'edit',
			'token' => $tokens['edit'],
			'basetimestamp' => $this->lastedit,
			'undo' => $oldrev,
			'undoafter' => $newrev,
			'assert' => 'user',
		);
		if( !is_null($this->starttimestamp) ) $params['starttimestamp'] = $this->starttimestamp;
		if(!is_null($summary)){
			if( mb_strlen( $summary, '8bit' ) > 255 ) {
				pecho( "Summary is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
				return false;
			}
            if( !$notag ) $summary .= $tag;
			
			$params['summary'] = $summary;
		}
		
		if( !is_null( $watch ) ) {
			if( $watch ) $editarray['watchlist'] = 'watch';
			elseif( !$watch ) $editarray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $editarray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
		if( !$force ) {
			try {
				$this->preEditChecks( "Undo" );
			}
			catch( EditError $e ) {
				pecho( "Error: $e\n\n", PECHO_FATAL );
				return false;
			}
		}
		
		pecho( "Undoing revision(s) on {$this->title}...\n\n", PECHO_NORMAL );
		$result = $this->wiki->apiQuery( $params, true );
		
		if( $result['edit']['result'] == "Success" ) {
			if( array_key_exists( 'nochange', $result['edit'] ) ) return $this->lastedit;
			
			$this->__construct( $this->wiki, null, $this->pageid );
			
			if( !is_null( $this->wiki->get_edit_rate() ) && $this->wiki->get_edit_rate() != 0 ) {
				sleep( intval( 60 / $this->wiki->get_edit_rate() ) - 1 );
			}
			
			return $result['edit']['newrevid'];
		} else {
			pecho( "Undo error...\n\n" . print_r($result['edit'], true) . "\n\n", PECHO_FATAL );
			return false;
		}
	}
	
	/**
	 * Returns a boolean depending on whether the page can have subpages or not.
	 * 
	 * @return bool True if subpages allowed
	 */		
	public function allow_subpages() {
		$allows = $this->wiki->get_allow_subpages();
		return (bool) $allows[$this->namespace_id];
	}
	
	/**
	 * Returns a boolean depending on whether the page is a discussion (talk) page or not.
	 * 
	 * @return bool True if discussion page, false if not
	 */	
	public function is_discussion() {
		if($this->namespace_id >= 0 && $this->namespace_id%2 == 1){
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Returns the title of the discussion (talk) page associated with a page, if it exists.
	 * 
	 * @return string Title of discussion page
	 */	
	public function get_discussion() {
		if($this->namespace_id < 0 || $this->namespace_id === "") {
			// No discussion page exists
			// Guessing we want to error
			throw new BadEntryError( "get_discussion", "Tried to find the discussion page of a page which could never have one" );
			return false;
		} else {
			$namespaces = $this->wiki->get_namespaces();
			if($this->is_discussion()){
				return $namespaces[($this->namespace_id - 1)] . ":" . $this->title_wo_namespace;
			} else {
				return $namespaces[($this->namespace_id + 1)] . ":" . $this->title_wo_namespace;
			}
		}
	}
	
	/**
	 * Moves a page to a new location.
	 * 
	 * @param string $newTitle The new title to which to move the page.
	 * @param string $reason A descriptive reason for the move.
	 * @param bool $movetalk Whether or not to move any associated talk (discussion) page.
	 * @param bool $movesubpages Whether or not to move any subpages.
	 * @param bool $noredirect Whether or not to suppress the leaving of a redirect to the new title at the old title.
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch.  Default preferences.
	 * @param bool $nowarnings Ignore any warnings. Default false.
	 * @return bool True on success
	 */	
	public function move( $newTitle, $reason = '', $movetalk = true, $movesubpages = true, $noredirect = false, $watch = null, $nowarnings = false ) {
		global $notag, $tag;
        $tokens = $this->wiki->get_tokens();
		
		if( $tokens['move'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		elseif( $tokens['move'] == '' ) {
			pecho( "User is not allowed to move {$this->title}\n\n", PECHO_FATAL );
			return false;
		}
		
		if( mb_strlen( $reason, '8bit' ) > 255 ) {
			pecho( "Reason is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
			return false;
		}
        
        try {
            $this->preEditChecks( "Move" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		
		pecho( "Moving {$this->title} to $newTitle...\n\n", PECHO_NOTICE );
		
		$editarray = array(
			'from' => $this->title,
			'to' => $newTitle,
			'action' => 'move',
			'token' => $tokens['move'],
		);
		
		if( !is_null( $watch ) ) {
			if( $watch ) $editarray['watchlist'] = 'watch';
			elseif( !$watch ) $editarray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $editarray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
		if( $nowarnings ) $editarray['ignorewarnings'] = 'yes';
		if( !$notag ) $reason .= $tag;
		if( !empty( $reason ) ) $editarray['reason'] = $reason;
	
		if( $movetalk ) $editarray['movetalk'] = 'yes';
		if( $movesubpages ) $editarray['movesubpages'] = 'yes';
		if( $noredirect ) $editarray['noredirect'] = 'yes';
		
		if( $this->wiki->get_maxlag() ) {
			$editarray['maxlag'] = $this->wiki->get_maxlag();
            
		}
		
		Hooks::runHook( 'StartMove', array( &$editarray ) );
		
		$result = $this->wiki->apiQuery( $editarray, true );
		
		if( isset( $result['move'] ) ) {
			if( isset( $result['move']['to'] ) ) {
				$this->__construct( $this->wiki, null, $this->pageid );
				return true;
			}
			else {
				pecho( "Move error...\n\n" . print_r($result['move'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Move error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	
	/**
	 * Protects the page.
	 * 
	 * @param array $levels Array of protections levels. The key is the type, the value is the level. Default: array( 'edit' => 'sysop', 'move' => 'sysop' )
	 * @param string $reason Reason for protection. Default null
	 * @param bool $cascade Whether or not to enable cascade protection. Default false
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @return bool True on success
	 */	
	public function protect( $levels = array( 'edit' => 'sysop', 'move' => 'sysop' ), $reason = null, $expiry = 'indefinite', $cascade = false, $watch = null ) {
	    global $notag, $tag;
		if( !in_array( 'protect', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to protect pages", PECHO_FATAL );
			return false;
		}
		
		$tokens = $this->wiki->get_tokens();
		if( !$notag ) $reason .= $tag;
		$editarray = array(
			'action' => 'protect',
			'title' => $this->title,
			'token' => $tokens['protect'],
			'reason' => $reason,
			'protections' => array(),
			'expiry' => $expiry
		);
		
		foreach( $levels as $type => $level ) {
			$editarray['protections'][] = "$type=$level";
		}
		
		$editarray['protections'] = implode( "|", $editarray['protections'] );
		
		if( $cascade ) $editarray['cascade'] = 'yes';
		
		if( !is_null( $watch ) ) {
			if( $watch ) $editarray['watchlist'] = 'watch';
			elseif( !$watch ) $editarray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $editarray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		try {
            $this->preEditChecks( "Protect" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		if( !$editarray['protections'] == array() ) pecho( "Protecting {$this->title}...\n\n", PECHO_NOTICE );
		else pecho( "Unprotecting {$this->title}...\n\n", PECHO_NOTICE );
		
		Hooks::runHook( 'StartProtect', array( &$editarray ) );
		
		$result = $this->wiki->apiQuery( $editarray, true);
		
		if( isset( $result['protect'] ) ) {
			if( isset( $result['protect']['title'] ) ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Protect error...\n\n" . print_r($result['protect'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Protect error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	
	/**
	 * Unprotects the page.
	 * 
	 * @param string $reason A reason for the unprotection. Defaults to null (blank).
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @return bool True on success
	 */	
	public function unprotect( $reason = null, $watch = null ) {
		return $this->protect( array(), $reason, 'indefinite', false, $watch);
	}
	
	/**
	 * Deletes the page.
	 * 
	 * @param string $reason A reason for the deletion. Defaults to null (blank).
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @return bool True on success
	 */	
	public function delete( $reason = null, $watch = null ) {
	    global $notag, $tag;
        
		if( !in_array( 'delete', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to delete pages", PECHO_FATAL );
			return false;
		}
		
		$tokens = $this->wiki->get_tokens();
		if( !$notag ) $reason .= $tag;
		$editarray = array(
			'action' => 'delete',
			'title' => $this->title,
			'token' => $tokens['delete'],
			'reason' => $reason
		);
		
		if( !is_null( $watch ) ) {
			if( $watch ) $editarray['watchlist'] = 'watch';
			elseif( !$watch ) $editarray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $editarray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
		Hooks::runHook( 'StartDelete', array( &$editarray ) );
		
        try {
            $this->preEditChecks( "Delete" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		pecho( "Deleting {$this->title}...\n\n", PECHO_NOTICE );
		
		$result = $this->wiki->apiQuery( $editarray, true);
		
		if( isset( $result['delete'] ) ) {
			if( isset( $result['delete']['title'] ) ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Delete error...\n\n" . print_r($result['delete'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Delete error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
		
	}
	
	/**
	 * Undeletes the page
	 * 
	 * @access public
	 * @param string $reason Reason for undeletion
	 * @param array $timestamps Array of timestamps to selectively restore
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @return bool
	 */
	public function undelete( $reason = null, $timestamps = null, $watch = null ) {
		global $notag, $tag;
		if( !in_array( 'undelete', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to undelete pages", PECHO_FATAL );
			return false;
		}
		
		$tokens = $this->wiki->get_tokens();
		if( !$notag ) $reason .= $tag;
		$undelArray = array(
			'action' => 'undelete',
			'title' => $this->title,
			'token' => $tokens['delete'], //Using the delete token, it's the exact same, and we don't have to do another API call
			'reason' => $reason
		);
		
		if( !is_null( $timestamps ) ) {
			$undelArray['timestamps'] = $timestamps;
			if( is_array( $timestamps ) ) {
				$undelArray['timestamps'] = implode('|',$timestamps);
			}
		}
		
		if( !is_null( $watch ) ) {
			if( $watch ) $undelArray['watchlist'] = 'watch';
			elseif( !$watch ) $undelArray['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $undelArray['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
        try {
            $this->preEditChecks( "Undelete" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		pecho( "Undeleting {$this->title}...\n\n", PECHO_NOTICE );
		
		Hooks::runHook( 'StartUndelete', array( &$undelArray ) );
		
		$result = $this->wiki->apiQuery( $undelArray, true);
		
		if( isset( $result['undelete'] ) ) {
			if( isset( $result['undelete']['title'] ) ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Undelete error...\n\n" . print_r($result['undelete'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Undelete error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	
	/**
	 * List deleted revisions of the page
	 * 
	 * @access public
	 * @param bool $content Whether or not to retrieve the content of each revision, Default false
	 * @param string $user Only list revisions by this user. Default null.
	 * @param string $excludeuser Don't list revisions by this user. Default null
	 * @param string $start Timestamp to start at. Default null
	 * @param string $end Timestamp to end at. Default null
	 * @param string $dir Direction to enumerate. Default 'older'
	 * @param array $prop Properties to retrieve. Default array( 'revid', 'user', 'parsedcomment', 'minor', 'len', 'content', 'token' )
	 * @return array List of deleted revisions
	 */
	public function deletedrevs( $content = false, $user = null, $excludeuser = null, $start = null, $end = null, $dir = 'older', $prop = array( 'revid', 'user', 'parsedcomment', 'minor', 'len', 'content', 'token' ) ) {
		if( !in_array( 'deletedhistory', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to view deleted revisions", PECHO_FATAL );
			return false;
		}
		
		if( $content ) $prop[] = 'content';
		
		$drArray = array(
			'_code' => 'dr',
			'list' => 'deletedrevs',
			'titles' => $this->title,
			'drprop' => implode( '|', $prop ),
			'drdir' => $dir
		);
		
		if( !is_null( $user ) ) $drArray['druser'] = $user;
		if( !is_null( $excludeuser ) ) $drArray['drexcludeuser'] = $excludeuser;
		if( !is_null( $start ) ) $drArray['drstart'] = $start;
		if( !is_null( $end ) ) $drArray['drend'] = $end;
		
		pecho( "Getting deleted revisions of {$this->title}...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler( $drArray );
	}
	
	/**
	 * Alias of embeddedin
	 * 
	 * @see Page::embeddedin()
	 * @deprecated since 18 June 2013
	 */
	public function get_transclusions( $namespace = null, $limit = null ) {	
		Peachy::deprecatedWarn( 'Page::get_transclusions()', 'Page::embeddedin()' );
		return $this->embeddedin($namespace, $limit);
		
    }

	/**
	 * Adds the page to the logged in user's watchlist
	 * 
	 * @param $lang Language to show the message in
	 * @return bool True on success
	 */		
	public function watch( $lang = null ) {
		
		Hooks::runHook( 'StartWatch' );
		
		pecho( "Watching {$this->title}...\n\n", PECHO_NOTICE );
		
		$tokens = $this->wiki->get_tokens();
		
		if( $tokens['watch'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		
		$apiArray = array(
			'action' => 'watch',
			'token' => $tokens['watch'],
			'title' => $this->title
		);
		
		if( !is_null( $lang ) ) $apiArray['uselang'] = $lang;

		$result = $this->wiki->apiQuery( $apiArray, true );
		
		if( isset( $result['watch'] ) ) {
			if( isset( $result['watch']['watched'] ) ) {
				return true;
			}
			else {
				pecho( "Watch error...\n\n" . print_r($result['watch'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Watch error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
		
	}
	
	/**
	 * Removes the page to the logged in user's watchlist
	 * 
	 * @param $lang Language to show the message in
	 * @return bool True on sucecess
	 */	
	public function unwatch( $lang = null ) {
		Hooks::runHook( 'StartUnwatch' );
		
		pecho( "Unwatching {$this->title}...\n\n", PECHO_NOTICE );
		
		$tokens = $this->wiki->get_tokens();
		
		if( $tokens['watch'] == '+\\' ) {
			pecho( "User has logged out.\n\n", PECHO_FATAL );
			return false;
		}
		
		$apiArray = array(
			'action' => 'watch',
			'token' => $tokens['watch'],
			'title' => $this->title,
			'unwatch' => 'yes'
		);
		
		if( !is_null( $lang ) ) $apiArray['uselang'] = $lang;

		$result = $this->wiki->apiQuery( $apiArray, true );
		
		if( isset( $result['watch'] ) ) {
			if( isset( $result['watch']['unwatched'] ) ) {
				return true;
			}
			else {
				pecho( "Unwatch error...\n\n" . print_r($result['watch'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Unwatch error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
		
	}
	
	/**
	 * Returns the page title
	 * 
	 * @param bool $namespace Set to true to return the title with namespace, false to return it without the namespace. Default true. 
	 * @return string Page title
	 */
	public function get_title( $namespace = true ) {
		if( !$namespace ) {
			return $this->title_wo_namespace;
		}
		return $this->title;
	}
	
	/**
	 * Returns whether or not a redirect was followed to get to the real page title
	 * 
	 * @return bool
	 */
	public function redirectFollowed() {
		return $this->redirectFollowed;
	}
	
	/**
	 * Returns whether or not the page is a special page
	 * 
	 * @return bool
	 */
	public function get_special() {
		return $this->special;
	}
	
	/**
	 * Gets ID or name of the namespace
	 * 
	 * @param bool $id Set to true to get namespace ID, set to false to get namespace name. Default true
	 * @return int|string
	 */
	public function get_namespace( $id = true ) {
		if( $id ) {
			return $this->namespace_id;
		}
		else {
			$namespaces = $this->wiki->get_namespaces();
			
			return $namespaces[$this->namespace_id];
		}
	}
	
	/**
	 * Returns the timestamp of the last edit
	 * 
	 * @return int
	 */
	public function get_lastedit( $force = false ) {
		if( $force ) $this->get_metadata();
		
		return $this->lastedit;
	}
	
	/**
	 * Returns length of the page
	 * 
	 * @return int
	 */
	public function get_length( $force = false ) {
		if( $force ) $this->get_metadata();
		
		return $this->length;
	}
	
	/**
	 * Returns number of hits the page has received
	 * 
	 * @return int
	 */
	public function get_hits( $force = false ) {
		if( $force ) $this->get_metadata();
		
		return $this->hits;
	}
	
	/**   
	 * Regenerates lastedit, length, and hits
	 * 
	 * @param array $pageInfoArray2 Array of values to merge with defaults (default: null)
	 * @return array Information gathered
	 * @access protected
	 */
	protected function get_metadata( $pageInfoArray2 = null ) {
		$pageInfoArray = array(
			'action' => 'query',
			'prop' => "info"
		);
        $pageInfoArray['inprop'] = 'protection|talkid|watched|watchers|notificationtimestamp|subjectid|url|readable|preload|displaytitle';
		
		if( $pageInfoArray2 != null ) {
			$pageInfoArray = array_merge($pageInfoArray, $pageInfoArray2);
		} else {
			$pageInfoArray['titles'] = $this->title;
		}
		
		$pageInfoRes = $this->wiki->apiQuery($pageInfoArray);

		if( isset( $pageInfoRes['warnings']['query']['*'] ) && in_string( 'special pages', $pageInfoRes['warnings']['query']['*'] ) ) {
			pecho( "Special pages are not currently supported by the API.\n\n", PECHO_ERROR );
			$this->exists = false;
			$this->special = true;
		}
		
		foreach( $pageInfoRes['query']['pages'] as $key => $info ) {
			$this->pageid = $key;
			if( $this->pageid > 0 ) {
				$this->exists = true;
				$this->lastedit = $info['touched'];
				$this->hits = $info['counter'];
				$this->length = $info['length'];
			}
			else {
				$this->pageid = 0;
				$this->lastedit = '';
				$this->hits = '';
				$this->length = '';
				$this->starttimestamp = '';
			}
			
			if( isset( $info['missing'] ) ) $this->exists = false;
			
			if( isset( $info['invalid'] ) ) throw new BadTitle( $this->title );
		
			$this->title = $info['title'];
			$this->namespace_id = $info['ns'];
			
			if( $this->namespace_id != 0 ) {
				$this->title_wo_namespace = explode( ':', $this->title, 2 );
				$this->title_wo_namespace = $this->title_wo_namespace[1];
			}
			else {
				$this->title_wo_namespace = $this->title;
			}
			
			if( isset( $info['special'] ) ) $this->special = true;
			
			if( isset( $info['protection'] ) ) $this->protection = $info['protection'];
            if( isset( $info['talkid'] ) ) $this->talkid = $info['talkid'];
            if( isset( $info['watched'] ) ) $this->watched = true;
            if( isset( $info['watchers'] ) ) $this->watchers = $info['watchers'];
            if( isset( $info['notificationtimestamp'] ) ) $this->watchlisttimestamp = $info['notificationtimestamp'];
            if( isset( $info['subjectid'] ) ) $this->subjectid = $info['subjectid'];
            if( isset( $info['fullurl'] ) ) $this->urls['full'] = $info['fullurl'];
            if( isset( $info['editurl'] ) ) $this->urls['edit'] = $info['editurl'];
            if( isset( $info['readable'] ) ) $this->readable = true;
            if( isset( $info['preload'] ) ) $this->preload = $info['preload'];
            if( isset( $info['displaytitle'] ) ) $this->displaytitle = $info['displaytitle'];
            
            return $pageInfoRes;
		}
	}
	
	/**
	 * Returns all links to the page
	 * 
	 * @access public
	 * @param array $namespaces Namespaces to get. Default array( 0 );
	 * @param string $redirects How to handle redirects. 'all' = List all pages. 'redirects' = Only list redirects. 'nonredirects' = Don't list redirects. Default 'all'
	 * @param bool $followredir List links through redirects to the page
	 * @return array List of backlinks
	 */
	public function get_backlinks( $namespaces = array( 0 ), $redirects = 'all', $followredir = true ) {
		$leArray = array(
			'list' => 'backlinks',
			'_code' => 'bl',
			'blnamespace' => $namespaces,
			'blfilterredir' => $redirects,
			'bltitle' => $this->title
		);
		
		if( $followredir ) $leArray['blredirect'] = 'yes';
		
		Hooks::runHook( 'PreQueryBacklinks', array( &$leArray ) );
		
		pecho( "Getting all links to {$this->title}...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler( $leArray );
	}
	
	/*
	 * Rollbacks the latest edit(s) to a page.
	 * 
	 * @access public
	 * @see http://www.mediawiki.org/wiki/API:Edit_-_Rollback
	 * @param bool $force Whether to force an (attempt at an) edit, regardless of news messages, etc.
	 * @param string $summary Override the default edit summary for this rollback. Default null.
	 * @param bool $markbot If set, both the rollback and the revisions being rolled back will be marked as bot edits.
	 * @param string or bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @return array Details of the rollback perform. ['revid']: The revision ID of the rollback. ['old_revid']: The revision ID of the first (most recent) revision that was rolled back. ['last_revid']: The revision ID of the last (oldest) revision that was rolled back.
	 */
	public function rollback($force = false, $summary = null, $markbot = false, $watch = null){
		global $notag, $tag;
        if( !in_array( 'rollback', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to rollback edits", PECHO_FATAL );
			return false;
		}
		
		if( !$force ) {
			try {
				$this->preEditChecks();
			}
			catch( EditError $e ) {
				pecho( "Error: $e\n\n", PECHO_FATAL );
				return false;
			}
		}
		
		$history = $this->history( 1, 'older', false, null, true );
		
		$params = array(
			'action' => 'rollback',
			'title' => $this->title,
			'user' => $history[0]['user'],
			'token' => $history[0]['rollbacktoken'],
		);
		
		if( !is_null( $summary ) ) {
			if( mb_strlen( $summary, '8bit' ) > 255 ) {
				pecho( "Summary is over 255 bytes, the maximum allowed.\n\n", PECHO_FATAL );
				return false;
			}
            if( !$notag ) $summary .= $tag;
			
			$params['summary'] = $summary;
		}
		if( $markbot ) $params['markbot'] = 'yes';
	
		if( !is_null( $watch ) ) {
			if( $watch ) $params['watchlist'] = 'watch';
			elseif( !$watch ) $params['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $params['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
        try {
            $this->preEditChecks( "Rollback" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		Hooks::runHook( 'PreRollback', array( &$params ) );
		
		pecho( "Rolling back {$this->title}...\n\n", PECHO_NOTICE );
		
		$result = $this->wiki->apiQuery($params, true);
		
		if( isset( $result['rollback'] ) ) {
			if( isset( $result['rollback']['title'] ) ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Rollback error...\n\n" . print_r($result['rollback'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Rollback error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	
	/*
	 * Performs nobots checking, new message checking, etc
	 * 
	 * @access public
	 * @return void
	 */
	protected function preEditChecks( $action = "Edit" ){
		global $disablechecks, $masterrunpage;
        if( $disablechecks ) return;
        $preeditinfo = array(
			'action' => 'query',
			'meta' => 'userinfo',
			'uiprop' => 'hasmsg|blockinfo',
			'prop' => 'revisions',
			'titles' => $this->title,
			'rvprop' => 'content'
		);
		
		if( !is_null( $this->wiki->get_runpage() ) ) {
			$preeditinfo['titles'] .=  "|" . $this->wiki->get_runpage();
		}
		
		$preeditinfo = $this->wiki->apiQuery( $preeditinfo );
	
		$messages = false;
		$blocked = false;
		if( isset( $preeditinfo['query']['pages'] ) ) {
			//$oldtext = $preeditinfo['query']['pages'][$this->pageid]['revisions'][0]['*'];
			foreach( $preeditinfo['query']['pages'] as $pageid => $page ) {
				if( $pageid == $this->pageid ) {
					$oldtext = $page['revisions'][0]['*'];
				}
				elseif( $pageid == "-1" ) {
					if( $page['title'] == $this->wiki->get_runpage() ) {
						pecho("$action failed, enable page does not exist.\n\n", PECHO_WARN);
						throw new EditError("Enablepage", "Enable  page does not exist.");
					}
					else {
						$oldtext = '';
					}
				}
				else {
					$runtext = $page['revisions'][0]['*'];
				}
			}
			if( isset( $preeditinfo['query']['userinfo']['messages']) ) $messages = true;
			if( isset( $preeditinfo['query']['userinfo']['blockedby']) ) $blocked = true;
		}
		else {
			$oldtext = '';
			$runtext = 'enable';
		}
		
		//Perform nobots checks, login checks, /Run checks
		if( checkExclusion( $this->wiki, $oldtext, $this->wiki->get_username(), $this->wiki->get_optout() ) && $this->wiki->get_nobots() ) {
			throw new EditError("Nobots", "The page has a nobots template");
		}
		
        if( !is_null( $masterrunpage ) && !preg_match( '/enable|yes|run|go|true/i', $this->wiki->initPage( $masterrunpage )->get_text() ) ) {
            throw new EditError("Enablepage", "Script was disabled by Master Run page");
        }
        
		if( !is_null( $this->wiki->get_runpage() ) && !preg_match( '/enable|yes|run|go|true/i', $runtext ) ) {
			throw new EditError("Enablepage", "Script was disabled by Run page");
		}
		
		if( $messages && $this->wiki->get_stoponnewmessages() ) {
			throw new EditError("NewMessages", "User has new messages");
		}
		
		if( $blocked ) {
			throw new EditError("Blocked", "User has been blocked");
		}
	}
	
	/**
	 * Returns array of pages that embed (transclude) the page given.
	 * 
	 * @access public
	 * @param array $namespace Which namespaces to search (default: null).
	 * @param int limit How many results to retrieve (default: null i.e. all).
	 * @return array A list of pages the title is transcluded in.
	 */
	public function embeddedin( $namespace = null, $limit = null ) {
		$eiArray = array(
			'list' => 'embeddedin',
			'_code' => 'ei',
			'eititle' => $this->title,
			'_lhtitle' => 'title',
			'_limit' => $limit
		);
		
		if(!is_null($namespace)){
			$eiArray['einamespace'] = $namespace;
		}
		
		Hooks::runHook( 'PreQueryEmbeddedin', array( &$eiArray ) );
		
		pecho( "Getting list of pages that include {$this->title}...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler( $eiArray );
	}
	
	/**
	 * Purges a list of pages. Shortcut for {@link Wiki::purge()}
	 * 
	 * @see Wiki::purge()
	 * @access public
	 * @return void|bool
	 */
	public function purge() {
		return $this->wiki->purge( $this->title );
	}
	
	/**
	 * Parses wikitext and returns parser output. Shortcut for Wiki::parse
	 * 
	 * @access public
	 * @param string $summary Summary to parse. Default null.
	 * @param string $id Parse the content of this revision
	 * @param array $prop Properties to retrieve. array( 'text', 'langlinks', 'categories', 'links', 'templates', 'images', 'externallinks', 'sections', 'revid', 'displaytitle', 'headitems', 'headhtml', 'iwlinks', 'wikitext', 'properties' )
	 * @param string $section Only retrieve the content of this section number.  Default null.
     * @return array
	 */
	public function parse( $summary = null, $id = null, $prop = array( 'text', 'langlinks', 'categories', 'links', 'templates', 'images', 'externallinks', 'sections', 'revid', 'displaytitle', 'headitems', 'headhtml', 'iwlinks', 'wikitext', 'properties' ), $uselang = 'en', $section = null ) {
		return $this->wiki->parse( null, null, $summary, false, false, $prop, $uselang, $this->title, $id );
	}

}
