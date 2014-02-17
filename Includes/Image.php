<?php

class Image {
	
	/**
	 * Wiki class
	 * 
	 * @var Wiki
	 * @access protected
	 */
	protected $wiki;
	
	/**
	 * Page class
	 * 
	 * @var Page
	 * @access protected
	 */
	protected $page;
	
	/**
	 * MIME type of image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $mime;
	
	/**
	 * Bitdepth of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $bitdepth;
	
	/**
	 * SHA1 hash of image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $hash;
	
	/**
	 * Size of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $size;
	
	/**
	 * Metadata stored in the image
	 * 
	 * @var array
	 * @access protected
	 */
	protected $metadata = array();
	
	/**
	 * URL to direct image
	 * 
	 * @var string
	 * @access protected
	 */
	protected $url;
	
	/**
	 * Timestamp that of the most recent upload
	 * 
	 * @var string
	 * @access protected
	 */
	protected $timestamp;
	
	/**
	 * Username of the most recent uploader
	 * 
	 * @var string
	 * @access protected
	 */
	protected $user;
	
	/**
	 * Width of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $width;
	
	/**
	 * Height of image
	 * 
	 * @var int
	 * @access protected
	 */
	protected $height;
	
	/**
	 * Whether or not the image is hosted locally
	 * This is not whether or not the page exists, use Image::get_exists() for that
	 * 
	 * @var bool 
	 * @access protected
	 */
	protected $local = true;
	
	/**
	 * Sanitized name for local storage (namespace, colons, etc all removed)
	 * 
	 * @var string
	 * @access protected
	 */
	protected $localname;
	
	/**
	 * Image name, with namespace
	 * 
	 * @var string
	 * @access protected
	 */
	protected $title;
	
	/**
	 * Image name, without namespace
	 * 
	 * @var string
	 * @access protected
	 */
	protected $rawtitle;
	
	/**
	 * List of pages where the image is used
	 * 
	 * @var array
	 * @access protected
	 */
	protected $usage = array();
	
	/**
	 * List of previous uploads
	 * 
	 * @var array
	 * @access protected
	 */
	protected $history = array();
	
	/**
	 * Other images with identical SHA1 hashes
	 * 
	 * @var array
	 * @access protected
	 */
	protected $duplicates = array();
	
	/**
	 * Whether image itself exists or not
	 *
	 * @var bool
	 * @access protected
	 */
    protected $exists = true;
	
	/**
	 * Construction method for the Image class
	 * 
	 * @access public
	 * @param Wiki &$wikiClass The Wiki class object
	 * @param string $filename Filename
	 * @return void
	 */
	function __construct( &$wikiClass, $title = null ) {
		
		$this->wiki = &$wikiClass;
		
		$this->title = $title;
		
		if( $this->wiki->removeNamespace( $title ) == $title ) {
			$namespaces = $this->wiki->get_namespaces();
			$this->title = $namespaces[6] . ':' . $title;
		}
		
		$ii = $this->imageinfo();
		
		foreach( $ii as $x ) {
			
			$this->title = $x['title'];
			$this->rawtitle = $this->wiki->removeNamespace( $x['title'] );
			$this->localname = str_replace( array( ' ', '+' ), array( '_', '_' ), urlencode( $this->rawtitle ) );
			
			$this->page = &$this->wiki->initPage( $this->title );
			
			if( $x['imagerepository'] == "shared" ) $this->local = false;
			if( isset( $x['imageinfo'] ) ) {
				
				$this->mime = $x['imageinfo'][0]['mime'];
				$this->bitdepth = $x['imageinfo'][0]['bitdepth'];
				$this->hash = $x['imageinfo'][0]['sha1'];
				$this->size = $x['imageinfo'][0]['size'];
				$this->width = $x['imageinfo'][0]['width'];
				$this->height = $x['imageinfo'][0]['height'];
				$this->url = $x['imageinfo'][0]['url'];
				$this->timestamp = $x['imageinfo'][0]['timestamp'];
				$this->user = $x['imageinfo'][0]['user'];
				
				if( is_array( $x['imageinfo'][0]['metadata'] ) ) {
					foreach( $x['imageinfo'][0]['metadata'] as $metadata ) {
						$this->metadata[$metadata['name']] = $metadata['value'];
					}
				}
				
			} else $this->exists = false;
		}
		
	}
    
    /**
     * 
     * @access public
     * @link https://www.mediawiki.org/wiki/API:Properties#imageinfo_.2F_ii
     * @param $limit Number of results to limit to. Default 50.
     * @param $prop What image information to get.  Default all values.
     * @return unknown 
     */
    public function get_stashinfo( $limit = 50, $prop = array( 'timestamp', 'url', 'size', 'dimensions', 'sha1', 'mime', 'thumbmime', 'metadata', 'bitdepth' ) ) {
        
        $stasharray = array(
            'prop' => 'stashimageinfo',
            'siiprop' => implode( '|', $prop ),
            'titles' => $this->title,
            '_code' => 'sii',
            '_limit' => $limit,
            '_lhtitle' => 'stashimageinfo'
        );
        
        return $this->wiki->listHandler( $stasharray );
    
    }
	
	/**
	 * Returns various information about the image
	 * 
	 * @access public
	 * @param int $limit Number of revisions to get info about. Default 1
	 * @param int $width Width of image. Default -1 (no width)
	 * @param int $height Height of image. Default -1 (no height)
	 * @param string $start Timestamp to start at. Default null
	 * @param string $end Timestamp to end at. Default null
	 * @param array $prop Properties to retrieve. Default array( 'timestamp', 'user', 'comment', 'url', 'size', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' )
	 * @param string $version Version of metadata to use. Default 'latest'
     * @param string $urlparam A handler specific parameter string. Default null
     * @param bool $localonly Look only for files in the local repository. Default false
     * @return array
	 */
	public function imageinfo( $limit = 1, $width = -1, $height = -1, $start = null, $end = null, $prop = array( 'timestamp', 'userid', 'user', 'comment', 'parsedcomment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'thumbmime', 'mediatype', 'metadata', 'archivename', 'bitdepth' ), $version = 'latest', $urlparam = null, $localonly = false ) {
	
		$imageInfoArray = array(
			'prop' => 'imageinfo',
            '_code'=>'ii',
			'_limit' => $limit,
			'iiprop' => implode('|',$prop),
			'iiurlwidth' => $width,
			'iiurlheight' => $height,
			'titles' => $this->title,
            'iimetadataversion' => $version,
            '_lhtitle' => 'imageinfo'
		);
		
		if( !is_null( $start ) ) $imageInfoArray['iistart'] = $start;
		if( !is_null( $end ) ) $imageInfoArray['iiend'] = $end;
		if( !is_null( $urlparam ) ) $imageInfoArray['iiurlparam'] = $urlparam;
        if( $localonly ) $imageInfoArray['iilocalonly'] = 'yes';
		
		pecho( "Getting image info for {$this->title}...\n\n", PECHO_NORMAL );
		
		return $this->wiki->listHandler( $imageInfoArray );
	}
	
	/**
	 * Returns the upload history of the image. If function was already called earlier in the script, it will return the local cache unless $force is set to true. 
	 * 
	 * @access public
	 * @param bool $force Whether or not to always refresh. Default false
	 * @param string $dir Which direction to go. Default 'older'
	 * @param int $limit Number of revisions to get. Default null (all revisions)
	 * @return void
	 */
	public function get_history( $dir = 'older', $limit = null ) {
		
		$this->history = $this->page->history( $limit, $dir );
		return $this->history;
	}
	
	/**
	 * Returns all pages where the image is used. If function was already called earlier in the script, it will return the local cache unless $force is set to true. 
	 * 
	 * @access public
	 * @param bool $force Whether or not to regenerate list, even if there is a local cache. Default false, set to true to regenerate list.
	 * @param string|array $namespace Namespaces to look in. If set as a string, must be set in the syntax "0|1|2|...". If an array, simply the namespace IDs to look in. Default null.
	 * @param string $redirects How to filter for redirects. Options are "all", "redirects", or "nonredirects". Default "all".
	 * @param bool $followRedir If linking page is a redirect, find all pages that link to that redirect as well. Default false.
	 * @return array
	 */
	public function get_usage( $namespace = null, $redirects = "all", $followRedir = false, $limit = null ) {
		
		if( $force || !count( $this->usage ) ) {
		
			$iuArray = array(
				'list' => 'imageusage',
				'_code' => 'iu',
				'_lhtitle' => 'title',
				'iutitle' => $this->title,
				'iufilterredir' => $redirects,
			);
			
			if( !is_null( $namespace ) ) {
			
				if( is_array( $namespace ) ) {
					$namespace = implode( '|', $namespace );
				}
				$iuArray['iunamespace'] = $namespace;
			}
			
			if( !is_null( $limit ) ) $iuArray['iulimit'] = $limit;
			
			if( $followRedir ) $iuArray['iuredirect'] = 'yes';
			
			pecho( "Getting image usage for {$this->title}..\n\n", PECHO_NORMAL );
			
			$this->usage = $this->wiki->listHandler( $iuArray );
			
		}
		
		return $this->usage;
	}
	
	/**
	 * Returns an array of all files with identical sha1 hashes
	 *
	 * @param int $limit Number of duplicates to get. Default null (all)
	 * @return array Duplicate files
	 */
	public function get_duplicates( $limit = null ) {
		
		if( $force || !count( $this->duplicates ) ) {
			
			if( !$this->page->get_exists() ) {
				return $this->duplicates;
			}
		
			$dArray = array(
				'action' => 'query',
				'prop' => 'duplicatefiles',
				'dflimit' => ( $this->wiki->get_api_limit() + 1 ),
				'titles' => $this->title
			);
			
			$continue = null;
			
			pecho( "Getting duplicate images of {$this->title}..\n\n", PECHO_NORMAL );
			
			while( 1 ) {
				if( !is_null( $continue ) ) $tArray['dfcontinue'] = $continue;
				
				$dRes = $this->wiki->apiQuery( $dArray );
				
				foreach( $dRes['query']['pages'] as $x ) {
					if( isset( $x['duplicatefiles'] ) ) {
						foreach( $x['duplicatefiles'] as $y ) {
							$this->duplicates[] = $y['name'];
						}
					}
				}
				
				if( isset( $dRes['query-continue'] ) ) {
					foreach( $dRes['query-continue'] as $z ) {
						$continue = $z['dfcontinue'];
					}
				}
				else {
					break;
				}
				
				
			}
			
		}
		
		return $this->duplicates;
	}	
	
	/**
	 * Revert a file to an old version
	 *
	 * @access public
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param string $revertto Archive name of the revision to revert to.  Default null.
	 * @return bool|void
	 */
	 public function revert( $comment = '', $revertto = null ) {
	    global $notag, $tag;
		$tokens = $this->wiki->get_tokens();
		
        if( !$notag ) $comment .= $tag;
		$apiArray = array(
			'action' => 'filerevert',
			'token' => $tokens['edit'],
			'comment' => $comment,
			'filename' => $this->rawtitle
		);
		
		if( !is_null( $revertto ) ) {
			$apiArray['archivename'] = $revertto;
			pecho( "Reverting to $revertto"."...\n\n", PECHO_NOTICE );
		} else {
			$ii = $this->imageinfo( 2, -1, -1, null, null, array( 'archivename' ) ) ;
			foreach( $ii as $x ) if( isset( $x['imageinfo'] ) ) $apiArray['archivename'] = $x['imageinfo'][1]['archivename'];
			pecho( "Reverting to prior upload...\n\n", PECHO_NOTICE );
		}
        
        try {
            $this->preEditChecks( "Revert" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		
		$result = $this->wiki->apiQuery( $apiArray, true );
		
		if( isset( $result['filerevert'] ) ) {
			if( isset( $result['filerevert']['result'] ) && $result['filerevert']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Revert error...\n\n" . print_r($result['filerevert'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Revert error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
    
    /**
     * Rotate the image clockwise a certain degree.
     *
     * @param int|string $degree Degrees to rotate image clockwise
     * @return bool|void
     */
     public function rotate ( $degree = 90 ) {
        $tokens = $this->get_tokens();
        
        $apiArray = array(
            'action' => 'imagerotate',
            'token' => $tokens['edit'],
            'titles' => $this->title
        );
        pecho( "Rotating image(s) $degree degrees...\n\n", PECHO_NOTICE );
        
        try {
            $this->preEditChecks( "Rotate" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
        
        $result = $this->apiQuery( $apiArray, true );
        
        if( isset( $result['imagerotate'] ) ) {
            if( isset( $result['imagerotate']['result'] ) && $result['imagerotate']['result'] == "Success" ) {
                $this->__construct( $this->wiki, $this->title );
                return true;
            }
            else {
                pecho( "Rotate error...\n\n" . print_r($result['imagerotate'], true) . "\n\n", PECHO_FATAL );
                return false;
            }
        }
        else {
            pecho( "Rotate error...\n\n" . print_r($result, true), PECHO_FATAL );
            return false;
        }
    }
	
	/**
	 * Upload an image to the wiki
	 * 
	 * @access public
	 * @param mixed $localname Location of the file to upload. Either an absolute path, or the name of an image in the Images/ directory will work. Default null (/path/to/peachy/Images/<$this->localname>)
	 * @param string $text Text on the image file page (default: '')
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param bool $watch Should the upload be added to the watchlist (default: false)
	 * @param bool $ignorewarnings Ignore warnings about the upload (default: true)
	 * @param bool $async Make potentially large file operations asynchronous when possible.  Default false.
	 * @return bool|void
	 */
	public function upload( $file = null, $text = '', $comment = '', $watch = null, $ignorewarnings = true, $async = false, $tboverride = false ) {
		global $pgIP, $notag, $tag;
		
        if( !$notag ) $comment .= $tag;
		if( !is_array( $file ) ) {
			if( !preg_match( '@((http(s)?:\/\/)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', $file ) ) { 
				if( !is_file( $file ) ) {
		
					if( is_file( $pgIP . 'Images/' . $file ) ) {
						$file = $pgIP . 'Images/' . $file;
					}
					else {
						$file = $pgIP . 'Images/' . $this->file;
					}

					if( !is_file( $file ) ) {
						throw new BadEntryError( "FileNotFound", "The given file, or file chunks, was not found or an invalid URL was specified." );
					}
				}
			}
			pecho( "Uploading $file to {$this->title}..\n\n", PECHO_NOTICE );
		} else {
			$i = 0;
			foreach( $file as $chunk ) {
				if( !is_file( $chunk ) ) {
		
					if( is_file( $pgIP . 'Images/' . $chunk ) ) {
						$file[$i] = $pgIP . 'Images/' . $chunk;
					}
                    
					if( !is_file( $file[$i] ) ) {
						throw new BadEntryError( "FileNotFound", "The given chunk file was not found." );
					}
				}
                $i++;
			}
			pecho( "Uploading chunk files to {$this->title}..\n\n", PECHO_NOTICE );
		}
        
        try {
            $this->preEditChecks( "Uploads" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }

		return $this->api_upload( $file, $text, $comment, $watch, $ignorewarnings, $async );
		
	}
	
	/**
	 * Upload an image to the wiki using api.php
	 * 
	 * @access public
	 * @param mixed $file Absolute path to the image, a URL, or an array containing file chunks for a chunk upload.
	 * @param string $text Text on the image file page (default: '')
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param bool $watch Should the upload be added to the watchlist (default: false)
	 * @param bool $ignorewarnings Ignore warnings about the upload (default: true)
	 * @param bool $async Make potentially large file operations asynchronous when possible.  Default false.
	 * @param string $filekey Key that identifies a previous upload that was stashed temporarily. Default null.
	 * @notice This feature is not yet fully developed.  Manual stashing is not allowed at this time.  This will be corrected during the final release of Peachy 2.
	 * @return bool
	 */
	public function api_upload( $file, $text = '', $comment = '', $watch = null, $ignorewarnings = true, $async = false, $filekey = null ) {
		
		$tokens = $this->wiki->get_tokens();
		
		$apiArr = array(
			'action' => 'upload',
			'filename' => $this->rawtitle,
			'comment' => $comment,
			'text' => $text,
			'token' => $tokens['edit'],
			'ignorewarnings' => intval( $ignorewarnings )
		);
		
		if( !is_null( $filekey ) ) $apiArr['filekey'] = $filekey;
		
		if( !is_null( $watch ) ) {
			if( $watch ) $aprArr['watchlist'] = 'watch';
			elseif( !$watch ) $apiArr['watchlist'] = 'nochange';
			elseif( in_array( $watch, array( 'watch', 'unwatch', 'preferences', 'nochange' ) ) ) $apiArr['watchlist'] = $watch;
			else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}
		
		if( !is_array( $file ) ) {
			if( is_file( $file ) ) {
				if( $async ) $apiArr['async'] = 'yes';
				$localfile = $file;
				$apiArr['file'] = "@$localfile";
			}
			else {
				$apiArr['url'] = $file;
				if( $async ) {
					$apiArr['asyncdownload'] = 'yes';
					$apiArr['leavemessage'] = 'yes';
				}
			}
		} else {
			$apiArr['stash'] = 'yes';
			$apiArr['offset'] = 0;
			$apiArr['filesize'] = 0;
			foreach( $file as $chunk ) $apiArr['filesize'] = $apiArr['filesize'] + filesize( $chunk );
			foreach( $file as $chunk ) {
				$apiArr['chunk'] = "@$chunk";
				pecho( "Uploading $chunk\n\n", PECHO_NOTICE );
				$result = $this->wiki->apiQuery( $apiArr, true );
				if( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Continue" ) {
					$apiArr['filekey'] = $result['upload']['filekey'];
					$apiArr['offset'] = $result['upload']['offset'];
				}
				elseif( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Success" ) {
					$apiArr['filekey'] = $result['upload']['filekey'];
					unset($apiArr['offset']);
					unset($apiArr['chunk']);
					unset($apiArr['stash']);
					unset($apiArr['filesize']);
					pecho( "Chunks uploaded successfully!\n\n", PECHO_NORMAL );
					break;
				} else {
					pecho( "Upload error...\n\n".print_r( $result, true )."\n\n", PECHO_FATAL );
					return false;
				}
			}
		}
		
		Hooks::runHook( 'APIUpload', array( &$apiArr ) );
		
		$result = $this->wiki->apiQuery( $apiArr, true);
		
		if( isset( $result['upload'] ) ) {
			if( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			}
			else {
				pecho( "Upload error...\n\n" . print_r($result['upload'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Upload error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
		
	}
	
	/**
	 * Downloads an image to the local disk
	 * 
	 * @param string $localname Filename to store image as. Default null.
	 * @param int $width Width of image to download. Default -1.
	 * @param int $height Height of image to download. Default -1.
	 * @return void
	 */
	public function download( $localname = null, $width = -1, $height = -1 ) {
		global $pgIP;
		
		if( !$this->local ) {
			pecho( "Attempted to download a file on a shared respository instead of a local one", PECHO_NOTICE );
		}
		
		if( !$this->page->get_exists() ) {
			pecho( "Attempted to download a non-existant file.", PECHO_NOTICE );
		}
		
		$ii = $this->imageinfo( 1, $width, $height );
		
		if( isset( $ii[ $this->page->get_id() ]['imageinfo'] ) ) {
			$ii = $ii[ $this->page->get_id() ]['imageinfo'][0];
			
			if( $width != -1 ) {
				$url = $ii['thumburl'];
			}
			else {
				$url = $ii['url'];
			}
			
			if( is_null( $localname ) ) {
				$localname = $pgIP . 'Images/' . $this->localname;
			}
			
			Hooks::runHook( 'DownloadImage', array( &$url, &$localname ) );
			
			pecho( "Downloading {$this->title} to $localname..\n\n", PECHO_NOTICE );
			
			$this->wiki->get_http()->download( $url, $localname );
		}
		else {
			pecho( "Error in getting image URL.\n\n" . print_r($ii) . "\n\n", PECHO_FATAL );
		}
	}	
	
	/**
	 * Resize an image
	 * 
	 * @access public
	 * @param int $width Width of resized image. Default null
	 * @param int $height Height of resized image. Default null.
	 * @param bool $reupload Whether or not to automatically upload the image again. Default false
	 * @param string $newname New filename when reuploading. If not null, upload over the old file. Default null.
	 * @param string $text Text to use for the image name
	 * @param string $comment Upload comment. 
	 * @param bool $watch Whether or not to watch the image on uploading
	 * @param bool $ignorewarnings Whether or not to ignore upload warnings
	 * @return void
	 */
	public function resize( $width = null, $height = null, $reupload = false, $newname = null, $text = '', $comment = '', $watch = null, $ignorewarnings = true ) {
		global $pgIP, $notag, $tag;
        
        try {
            $this->preEditChecks( "Resize" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
		
        if( !$notag ) $comment .= $tag;
		if( !function_exists( 'ImageCreateTrueColor' ) ) {
			throw new DependencyError( "GD", "http://us2.php.net/manual/en/book.image.php" );
		}
		echo "1\n";
		if( !is_null( $width ) && !is_null( $height ) ) {	
			$this->download();
			
			$type = substr( strrchr( $this->mime, '/' ), 1 );

			switch ($type) {
				case 'jpeg':
				    $image_create_func = 'ImageCreateFromJPEG';
				    $image_save_func = 'ImageJPEG';
					$new_image_ext = 'jpg';
				    break;
				
				case 'png':
				    $image_create_func = 'ImageCreateFromPNG';
				    $image_save_func = 'ImagePNG';
					$new_image_ext = 'png';
				    break;
				
				case 'bmp':
				    $image_create_func = 'ImageCreateFromBMP';
				    $image_save_func = 'ImageBMP';
					$new_image_ext = 'bmp';
				    break;
				
				case 'gif':
				    $image_create_func = 'ImageCreateFromGIF';
				    $image_save_func = 'ImageGIF';
					$new_image_ext = 'gif';
				    break;
				
				case 'vnd.wap.wbmp':
				    $image_create_func = 'ImageCreateFromWBMP';
				    $image_save_func = 'ImageWBMP';
					$new_image_ext = 'bmp';
				    break;
				
				case 'xbm':
				    $image_create_func = 'ImageCreateFromXBM';
				    $image_save_func = 'ImageXBM';
					$new_image_ext = 'xbm';
				    break;
				
				default:
					$image_create_func = 'ImageCreateFromJPEG';
				    $image_save_func = 'ImageJPEG';
					$new_image_ext = 'jpg';
			}
			echo "2\n";
			$image = imagecreatetruecolor( $width, $height );
			echo "3\n";
			$new_image = $image_create_func( $pgIP . 'Images/' . $this->localname );
			
			$info = getimagesize( $pgIP . 'Images/' . $this->localname );

			imagecopyresampled( $image, $new_image, 0, 0, 0, 0, $width, $height, $info[0], $info[1] );

        	$image_save_func( $image, $pgIP . 'Images/' . $this->localname );

		}
		elseif( !is_null( $width ) ) {
			$this->download( null, $width );
		}
		elseif( !is_null( $height ) ) {
			$this->download( null, $height + 100000, $height );
		}
		else {
			throw new BadEntryError( "NoParams", "No parameters given" );
		}
		
		if( $reupload ) {
			return $this->upload( null, $text, $comment, $watch, $ignorewarnings );
		}
	}
	
	/**
	 * Returns the normalized image name
	 * 
	 * @param bool $namespace Whether or not to include the File: part of the name. Default true.
	 * @return string
	 */
	public function get_title( $namespace = true ) {
		if( $namespace ) {
			return $this->title;
		}
		else {
			return $this->rawtitle;
		}
	}
    
    /**
     * Deletes the image.
     * 
     * @param string $reason A reason for the deletion. Defaults to null (blank).
     * @param string|bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
     * @param string $oldimage The name of the old image to delete as provided by iiprop=archivename
     * @return bool True on success
     */
    public function delete( $reason = null, $watch = null, $oldimage = null ) {
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
        if( !is_null( $oldimage ) ) $editarray['oldimage'] = $oldimage;
        
        Hooks::runHook( 'StartDelete', array( &$editarray ) );
        
        pecho( "Deleting {$this->title}...\n\n", PECHO_NOTICE );
        
        try {
            $this->preEditChecks( "Delete" );
        }
        catch( EditError $e ) {
            pecho( "Error: $e\n\n", PECHO_FATAL );
            return false;
        }
        
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
    
    /*
     * Performs new message checking, etc
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
            'rvprop' => 'content'
        );
        
        if( !is_null( $this->wiki->get_runpage() ) ) {
            $preeditinfo['titles'] =  $this->wiki->get_runpage();
        }
        
        $preeditinfo = $this->wiki->apiQuery( $preeditinfo );
    
        $messages = false;
        $blocked = false;
        if( isset( $preeditinfo['query']['pages'] ) && !is_null( $this->wiki->get_runpage() ) ) {
            //$oldtext = $preeditinfo['query']['pages'][$this->pageid]['revisions'][0]['*'];
            foreach( $preeditinfo['query']['pages'] as $pageid => $page ) {
                if( $pageid == "-1" ) {
                    pecho("$action failed, enable page does not exist.\n\n", PECHO_WARN);
                    throw new EditError("Enablepage", "Enable page does not exist.");
                }
                else {
                    $runtext = $page['revisions'][0]['*'];
                }
            }
            if( isset( $preeditinfo['query']['userinfo']['messages']) ) $messages = true;
            if( isset( $preeditinfo['query']['userinfo']['blockedby']) ) $blocked = true;
        } 
        
        //Perform login checks, /Run checks
        
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
	 * Returns the sanitized local disk name
	 * 
	 * @return string
	 */
	public function get_localname() {
		return $this->localname;
	}
	
	/**
	 * Whether or not the image is on a shared repository. A true result means that it is stored locally.
	 * 
	 * @return bool
	 */
	public function is_local() {
		return $this->local;
	}
	
	/**
	 * Whether or not the image exists
	 * 
	 * @return bool
	 */
	public function get_exists() {
		return $this->exists;
	}
	
	/**
	 * Returns a page class for the image
	 * 
	 * @return Page
	 */
	public function &get_page() {
		return $this->page;
	}
	
	/**
	 * Returns the MIME type of the image
	 * 
	 * @return string
	 */
	public function get_mime() {
		return $this->mime;
	}
	
	/**
	 * Returns the bitdepth of the image
	 * 
	 * @return int
	 */
	public function get_bitdepth() {
		return $this->bitdepth;
	}
	
	/**
	 * Returns the SHA1 hash of the image
	 * 
	 * @return string
	 */
	public function get_hash() {
		return $this->hash;
	}
	
	/**
	 * Returns the size of the image, in bytes
	 * 
	 * @return int
	 */
	public function get_size() {
		return $this->size;
	}
	
	/**
	 * Returns the metadata of the image
	 * 
	 * @return array
	 */
	public function get_metadata() {
		return $this->metadata;
	}
	
	/**
	 * Returns the direct URL of the image
	 * 
	 * @return string
	 */
	public function get_url() {
		return $this->url;
	}
	
	/**
	 * Returns the timestamp that of the most recent upload
	 * 
	 * @return string
	 */
	public function get_timestamp() {
		return $this->timestamp;
	}
	
	/**
	 * Returns the username of the most recent uploader
	 * 
	 * @return string
	 */
	public function get_user() {
		return $this->user;
	}
	
	/**
	 * Returns the width of the image
	 * 
	 * @return int
	 */
	public function get_width() {
		return $this->width;
	}
	
	/**
	 * Returns the height of the image
	 * 
	 * @return int
	 */
	public function get_height() {
		return $this->height;
	}

}
