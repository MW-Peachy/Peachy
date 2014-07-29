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
	 * @param string $title The title of the image
	 * @param int $pageid The ID of the image page (optional)
	 * @return Image
	 */
	public function __construct( Wiki &$wikiClass, $title = null, $pageid = null ) {

		$this->wiki = & $wikiClass;
		$this->title = $title;

		if( $this->wiki->removeNamespace( $title ) == $title ) {
			$namespaces = $this->wiki->get_namespaces();
			$this->title = $namespaces[6] . ':' . $title;
		}

		$ii = $this->imageinfo();

		if( is_array( $ii ) ) {

			$this->title = $ii[0]['canonicaltitle'];
			$this->rawtitle = $this->wiki->removeNamespace( $this->title );
			$this->localname = str_replace( array( ' ', '+' ), array( '_', '_' ), urlencode( $this->rawtitle ) );
			$this->page = & $this->wiki->initPage( $this->title, $pageid );
			$this->mime = $ii[0]['mime'];
			$this->bitdepth = $ii[0]['bitdepth'];
			$this->hash = $ii[0]['sha1'];
			$this->size = $ii[0]['size'];
			$this->width = $ii[0]['width'];
			$this->height = $ii[0]['height'];
			$this->url = $ii[0]['url'];
			$this->timestamp = $ii[0]['timestamp'];
			$this->user = $ii[0]['user'];

			if( is_array( $ii[0]['metadata'] ) ) {
				foreach( $ii[0]['metadata'] as $metadata ){
					$this->metadata[$metadata['name']] = $metadata['value'];
				}

			} else {
				$this->exists = false;
			}
		}

	}

	/**
	 *
	 * @access public
	 * @link https://www.mediawiki.org/wiki/API:Properties#imageinfo_.2F_ii
	 * @param int $limit Number of results to limit to. Default 50.
	 * @param array $prop What image information to get.  Default all values.
	 * @return array|bool False if not found, otherwise array of info indexed by revision
	 */
	public function get_stashinfo( $limit = 50, $prop = array(
        'timestamp', 'user', 'comment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'metadata', 'archivename',
        'bitdepth'
    ) ) {

		$stasharray = array(
			'prop'     => 'stashimageinfo',
			'siiprop'  => implode( '|', $prop ),
			'titles'   => $this->title,
			'_code'    => 'sii',
			'_limit'   => $limit,
			'_lhtitle' => 'stashimageinfo'
		);

		$stashinfo = $this->wiki->listHandler( $stasharray );
		if( is_array( $stashinfo ) ) {
			return $stashinfo[0];
		} else {
			return false;
		}
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
	 * @param string[] $prop Properties to retrieve. Default array( 'timestamp', 'user', 'comment', 'url', 'size', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' )
	 * @param string $version Version of metadata to use. Default 'latest'
	 * @param string $urlparam A handler specific parameter string. Default null
	 * @param bool $localonly Look only for files in the local repository. Default false
	 * @return array|bool False if file does not exist, otherwise array of info indexed by revision
	 */
	public function imageinfo( $limit = 1, $width = -1, $height = -1, $start = null, $end = null, $prop = array(
		'canonicaltitle', 'timestamp', 'userid', 'user', 'comment', 'parsedcomment', 'url', 'size', 'dimensions', 'sha1', 'mime',
		'thumbmime', 'mediatype', 'metadata', 'archivename', 'bitdepth'
	), $version = 'latest', $urlparam = null, $localonly = false ) {

		$imageInfoArray = array(
			'prop'              => 'imageinfo',
			'_code'             => 'ii',
			'_limit'            => $limit,
			'iiprop'            => implode( '|', $prop ),
			'iiurlwidth'        => $width,
			'iiurlheight'       => $height,
			'titles'            => $this->title,
			'iimetadataversion' => $version,
			'_lhtitle'          => 'imageinfo'
		);

		if( !is_null( $start ) ) $imageInfoArray['iistart'] = $start;
		if( !is_null( $end ) ) $imageInfoArray['iiend'] = $end;
		if( !is_null( $urlparam ) ) $imageInfoArray['iiurlparam'] = $urlparam;
		if( $localonly ) $imageInfoArray['iilocalonly'] = 'yes';

		pecho( "Getting image info for {$this->title}...\n\n", PECHO_NORMAL );

		$imageInfo = $this->wiki->listHandler( $imageInfoArray );
		if( count( $imageInfo ) > 0 ) {
			return $imageInfo[0];
		} else {
			// Does not exist
			return false;
		}
	}

	/**
	 * Returns the upload history of the image. If function was already called earlier in the script, it will return the local cache unless $force is set to true.
	 *
	 * @access public
	 * @param string $dir Which direction to go. Default 'older'
	 * @param int $limit Number of revisions to get. Default null (all revisions)
	 * @param bool $force Force generation of the cache. Default false (use cache).
	 * @return array Upload history.
	 */
	public function get_history( $dir = 'older', $limit = null, $force = false ) {
		if( !count( $this->history ) || $force ) {
			$this->history = $this->page->history( $limit, $dir );
		}
		return $this->history;
	}

	/**
	 * Returns all pages where the image is used. If function was already called earlier in the script, it will return the local cache unless $force is set to true.
	 *
	 * @access public
	 * @param string|array $namespace Namespaces to look in. If set as a string, must be set in the syntax "0|1|2|...". If an array, simply the namespace IDs to look in. Default null.
	 * @param string $redirects How to filter for redirects. Options are "all", "redirects", or "nonredirects". Default "all".
	 * @param bool $followRedir If linking page is a redirect, find all pages that link to that redirect as well. Default false.
	 * @param int|null $limit
	 * @param bool $force Force regeneration of the cache. Default false (use cache).
	 * @return array
	 */
	public function get_usage( $namespace = null, $redirects = "all", $followRedir = false, $limit = null, $force = false ) {

		if( $force || !count( $this->usage ) ) {

			$iuArray = array(
				'list'          => 'imageusage',
				'_code'         => 'iu',
				'_lhtitle'      => 'title',
				'iutitle'       => $this->title,
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
	 * @param bool $force Force regeneration of the cache. Default false (use cache).
	 * @return array Duplicate files
	 */
	public function get_duplicates( $limit = null, $force = false ) {

		if( $force || !count( $this->duplicates ) ) {

			if( !$this->get_exists() ) {
				return $this->duplicates;
			}

			$dArray = array(
				'action'  => 'query',
				'prop'    => 'duplicatefiles',
				'dflimit' => ( ( is_null( $limit ) ? $this->wiki->get_api_limit() + 1 : $limit ) ),
				'titles'  => $this->title
			);

			$continue = null;

			pecho( "Getting duplicate images of {$this->title}..\n\n", PECHO_NORMAL );

			while( 1 ){
				if( !is_null( $continue ) ) $dArray['dfcontinue'] = $continue;

				$dRes = $this->wiki->apiQuery( $dArray );

				foreach( $dRes['query']['pages'] as $x ){
					if( isset( $x['duplicatefiles'] ) ) {
						foreach( $x['duplicatefiles'] as $y ){
							$this->duplicates[] = $y['name'];
						}
					}
				}

				if( isset( $dRes['query-continue'] ) ) {
					foreach( $dRes['query-continue'] as $z ){
						$continue = $z['dfcontinue'];
					}
				} else {
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
	 * @return boolean
	 */
	public function revert( $comment = '', $revertto = null ) {
		global $pgNotag, $pgTag;
		$tokens = $this->wiki->get_tokens();

		if( !$pgNotag ) $comment .= $pgTag;
		$apiArray = array(
			'action'   => 'filerevert',
			'token'    => $tokens['edit'],
			'comment'  => $comment,
			'filename' => $this->rawtitle
		);

		if( !is_null( $revertto ) ) {
			$apiArray['archivename'] = $revertto;
			pecho( "Reverting to $revertto" . "...\n\n", PECHO_NOTICE );
		} else {
			$ii = $this->imageinfo( 2, -1, -1, null, null, array( 'archivename' ) );
			if( is_array( $ii ) ) $apiArray['archivename'] = $ii[1]['archivename'];
			pecho( "Reverting to prior upload...\n\n", PECHO_NOTICE );
		}

		try{
			$this->preEditChecks( "Revert" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $apiArray, true );

		if( isset( $result['filerevert'] ) ) {
			if( isset( $result['filerevert']['result'] ) && $result['filerevert']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			} else {
				pecho( "Revert error...\n\n" . print_r( $result['filerevert'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Revert error...\n\n" . print_r( $result, true ), PECHO_FATAL );
			return false;
		}
	}

	/**
	 * Rotate the image clockwise a certain degree.
	 *
	 * @param integer $degree Degrees to rotate image clockwise
	 * @return boolean
	 */
	public function rotate( $degree = 90 ) {
		$tokens = $this->wiki->get_tokens();

		$apiArray = array(
			'action' => 'imagerotate',
			'token'  => $tokens['edit'],
			'titles' => $this->title
		);
		pecho( "Rotating image(s) $degree degrees...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Rotate" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $apiArray, true );

		if( isset( $result['imagerotate'] ) ) {
			if( isset( $result['imagerotate']['result'] ) && $result['imagerotate']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			} else {
				pecho( "Rotate error...\n\n" . print_r( $result['imagerotate'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Rotate error...\n\n" . print_r( $result, true ), PECHO_FATAL );
			return false;
		}
	}

	/**
	 * Upload an image to the wiki
	 *
	 * @access public
	 * @param string $file Identifier of a file. Flexible format (local path, URL)
	 * @param string $text Text on the image file page (default: '')
	 * @param string $comment Comment for inthe upload in logs (default: '')
	 * @param bool $watch Should the upload be added to the watchlist (default: false)
	 * @param bool $ignorewarnings Ignore warnings about the upload (default: true)
	 * @param bool $async Make potentially large file operations asynchronous when possible.  Default false.
	 * @throws BadEntryError
	 * @return boolean
	 */
	public function upload( $file, $text = '', $comment = '', $watch = null, $ignorewarnings = true, $async = false ) {
		global $pgIP, $pgNotag, $pgTag;

		if( !$pgNotag ) $comment .= $pgTag;
		if( !is_array( $file ) ) {
			if( !preg_match( '@((http(s)?:\/\/)?([-\w]+\.[-\w\.]+)+\w(:\d+)?(/([-\w/_\.]*(\?\S+)?)?)*)@', $file ) ) {
				if( !is_file( $file ) ) {

					if( is_file( $pgIP . 'Images/' . $file ) ) {
						$file = $pgIP . 'Images/' . $file;
					}

					if( !is_file( $file ) ) {
						throw new BadEntryError( "FileNotFound", "The given file, or file chunks, was not found or an invalid URL was specified." );
					}
				}
			}
			pecho( "Uploading $file to {$this->title}..\n\n", PECHO_NOTICE );
		} else {
			$i = 0;
			foreach( $file as $chunk ){
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

		try{
			$this->preEditChecks( "Uploads" );
		} catch( EditError $e ){
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
			'action'         => 'upload',
			'filename'       => $this->rawtitle,
			'comment'        => $comment,
			'text'           => $text,
			'token'          => $tokens['edit'],
			'ignorewarnings' => intval( $ignorewarnings )
		);

		if( !is_null( $filekey ) ) $apiArr['filekey'] = $filekey;

		if( !is_null( $watch ) ) {
			if( $watch ) {
				$apiArr['watchlist'] = 'watch';
			} elseif( !$watch ) $apiArr['watchlist'] = 'nochange';
			elseif( in_array(
				$watch, array(
					'watch', 'unwatch', 'preferences', 'nochange'
				)
			) ) {
				$apiArr['watchlist'] = $watch;
			} else pecho( "Watch parameter set incorrectly.  Omitting...\n\n", PECHO_WARN );
		}

		if( !is_array( $file ) ) {
			if( is_file( $file ) ) {
				if( $async ) $apiArr['async'] = 'yes';
				$localfile = $file;
				$apiArr['file'] = "@$localfile";
			} else {
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
			foreach( $file as $chunk ){
				$apiArr['filesize'] = $apiArr['filesize'] + filesize( $chunk );
			}
			foreach( $file as $chunk ){
				$apiArr['chunk'] = "@$chunk";
				pecho( "Uploading $chunk\n\n", PECHO_NOTICE );
				$result = $this->wiki->apiQuery( $apiArr, true );
				if( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Continue" ) {
					$apiArr['filekey'] = $result['upload']['filekey'];
					$apiArr['offset'] = $result['upload']['offset'];
				} elseif( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Success" ) {
					$apiArr['filekey'] = $result['upload']['filekey'];
					unset( $apiArr['offset'] );
					unset( $apiArr['chunk'] );
					unset( $apiArr['stash'] );
					unset( $apiArr['filesize'] );
					pecho( "Chunks uploaded successfully!\n\n", PECHO_NORMAL );
					break;
				} else {
					pecho( "Upload error...\n\n" . print_r( $result, true ) . "\n\n", PECHO_FATAL );
					return false;
				}
			}
		}

		Hooks::runHook( 'APIUpload', array( &$apiArr ) );

		$result = $this->wiki->apiQuery( $apiArr, true );

		if( isset( $result['upload'] ) ) {
			if( isset( $result['upload']['result'] ) && $result['upload']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->title );
				return true;
			} else {
				pecho( "Upload error...\n\n" . print_r( $result['upload'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Upload error...\n\n" . print_r( $result, true ), PECHO_FATAL );
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

		if( !$this->get_exists() ) {
			pecho( "Attempted to download a non-existant file.", PECHO_NOTICE );
		}

		$ii = $this->imageinfo( 1, $width, $height );

		if( is_array( $ii ) ) {
			$ii = $ii[0];

			if( $width != -1 ) {
				$url = $ii['thumburl'];
			} else {
				$url = $ii['url'];
			}

			if( is_null( $localname ) ) {
				$localname = $pgIP . 'Images/' . $this->localname;
			}

			Hooks::runHook( 'DownloadImage', array( &$url, &$localname ) );

			pecho( "Downloading {$this->title} to $localname..\n\n", PECHO_NOTICE );

			$this->wiki->get_http()->download( $url, $localname );
		} else {
			pecho( "Error in getting image URL.\n\n" . print_r( $ii ) . "\n\n", PECHO_FATAL );
		}
	}

	/**
	 * Resize an image
	 *
	 * @access public
	 * @param int $width Width of resized image. Default null
	 * @param int $height Height of resized image. Default null.
	 * @param bool $reupload Whether or not to automatically upload the image again. Default false
	 * @param string $text Text to use for the image name
	 * @param string $comment Upload comment.
	 * @param bool $watch Whether or not to watch the image on uploading
	 * @param bool $ignorewarnings Whether or not to ignore upload warnings
	 * @throws DependencyError Relies on GD PHP plugin
	 * @throws BadEntryError
	 * @return boolean|void False on failure
	 */
	public function resize( $width = null, $height = null, $reupload = false, $text = '', $comment = '', $watch = null, $ignorewarnings = true ) {
		global $pgIP, $pgNotag, $pgTag;

		try{
			$this->preEditChecks( "Resize" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		if( !$pgNotag ) $comment .= $pgTag;
		if( !function_exists( 'ImageCreateTrueColor' ) ) {
			throw new DependencyError( "GD", "http://us2.php.net/manual/en/book.image.php" );
		}
		echo "1\n";
		if( !is_null( $width ) && !is_null( $height ) ) {
			$this->download();

			$type = substr( strrchr( $this->mime, '/' ), 1 );

			switch( $type ){
				case 'jpeg':
					$image_create_func = 'ImageCreateFromJPEG';
					$image_save_func = 'ImageJPEG';
					break;

				case 'png':
					$image_create_func = 'ImageCreateFromPNG';
					$image_save_func = 'ImagePNG';
					break;

				case 'bmp':
					$image_create_func = 'ImageCreateFromBMP';
					$image_save_func = 'ImageBMP';
					break;

				case 'gif':
					$image_create_func = 'ImageCreateFromGIF';
					$image_save_func = 'ImageGIF';
					break;

				case 'vnd.wap.wbmp':
					$image_create_func = 'ImageCreateFromWBMP';
					$image_save_func = 'ImageWBMP';
					break;

				case 'xbm':
					$image_create_func = 'ImageCreateFromXBM';
					$image_save_func = 'ImageXBM';
					break;

				default:
					$image_create_func = 'ImageCreateFromJPEG';
					$image_save_func = 'ImageJPEG';
			}
			echo "2\n";
			$image = imagecreatetruecolor( $width, $height );
			echo "3\n";
			$new_image = $image_create_func( $pgIP . 'Images/' . $this->localname );

			$info = getimagesize( $pgIP . 'Images/' . $this->localname );

			imagecopyresampled( $image, $new_image, 0, 0, 0, 0, $width, $height, $info[0], $info[1] );

			$image_save_func( $image, $pgIP . 'Images/' . $this->localname );

		} elseif( !is_null( $width ) ) {
			$this->download( null, $width );
		} elseif( !is_null( $height ) ) {
			$this->download( null, $height + 100000, $height );
		} else {
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
		} else {
			return $this->rawtitle;
		}
	}

	/**
	 * Deletes the image and page.
	 *
	 * @param string $reason A reason for the deletion. Defaults to null (blank).
	 * @param string|bool $watch Unconditionally add or remove the page from your watchlist, use preferences or do not change watch. Default preferences.
	 * @param string $oldimage The name of the old image to delete as provided by iiprop=archivename
	 * @return bool True on success
	 */
	public function delete( $reason = null, $watch = null, $oldimage = null ) {
		return $this->page->delete( $reason, $watch, $oldimage );
	}

	/*
	 * Performs new message checking, etc
	 *
	 * @access public
	 * @return void
	 */
	protected function preEditChecks( $action = "Edit" ) {
		$this->wiki->preEditChecks( $action );
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
