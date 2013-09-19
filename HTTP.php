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
 * HTTP object
 * Stores all cURL functions
 */

/**
 * HTTP Class, stores cURL functions
 */
class HTTP {
	
	/**
	 * Curl object
	 * 
	 * @var cURL
	 * @access private
	 */
	private $curl_instance;
	
	/**
	 * Hash to use for cookies
	 * 
	 * @var string
	 * @access private
	 */
	private $cookie_hash;                                       
	
	/**
	 * Whether or not to enable GET:, POST:, and DLOAD: messages being sent to the terminal.
	 * 
	 * @var bool
	 * @access private
	 */
	private $echo;
	
	/**
	 * Useragent
	 * 
	 * @var mixed
	 * @access private
	 */
	private $user_agent;
	
	/**
	 * Temporary file where cookies are stored
	 * 
	 * @var mixed
	 * @access private
	 */
	private $cookie_jar;
	
	/**
	 * Construction method for the HTTP class
	 * 
	 * @access public
	 * @param bool $echo Whether or not to enable GET:, POST:, and DLOAD: messages being sent to the terminal. Default false;
	 * @return void
	 */	
	function __construct( $echo = false, $verifyssl = true ) {
		global $pgUA;
		
		if( !function_exists( 'curl_init' ) ) {
			throw new DependencyError( "cURL", "http://us2.php.net/manual/en/curl.requirements.php" );
		}
		
		$this->echo = $echo;
		$this->curl_instance = curl_init();
		$this->cookie_hash = md5( time() . '-' . rand( 0, 999 ) );
		$this->cookie_jar = sys_get_temp_dir() . 'peachy.cookies.'.$this->cookie_hash.'.dat';
		$this->user_agent = 'Peachy MediaWiki Bot API Version ' . PEACHYVERSION;
		
		Hooks::runHook( 'HTTPNewCURLInstance', array( &$this, &$echo ) );
		
		$this->setCookieJar( $this->cookie_jar );
		
		curl_setopt($this->curl_instance,CURLOPT_MAXCONNECTS,100);
		curl_setopt($this->curl_instance,CURLOPT_CLOSEPOLICY,CURLCLOSEPOLICY_LEAST_RECENTLY_USED);
		curl_setopt($this->curl_instance,CURLOPT_MAXREDIRS,10);
		curl_setopt($this->curl_instance,CURLOPT_HTTPHEADER, array('Expect:'));
		curl_setopt($this->curl_instance,CURLOPT_ENCODING, 'gzip');
		curl_setopt($this->curl_instance,CURLOPT_RETURNTRANSFER,1);
		curl_setopt($this->curl_instance,CURLOPT_TIMEOUT,100);
		curl_setopt($this->curl_instance,CURLOPT_CONNECTTIMEOUT,10);
        if( !$verifyssl ) {
            curl_setopt ($this->curl_instance, CURLOPT_SSL_VERIFYPEER, FALSE);
            curl_setopt ($this->curl_instance, CURLOPT_SSL_VERIFYHOST, FALSE);     
        }
		
		$this->setUserAgent( $pgUA );

	}
	
	function setCookieJar( $cookie_file ) {
		$this->cookie_jar = $cookie_file;
		
		Hooks::runHook( 'HTTPSetCookieJar', array( &$cookie_file ) );
		
		curl_setopt($this->curl_instance,CURLOPT_COOKIEJAR, $cookie_file);
		curl_setopt($this->curl_instance,CURLOPT_COOKIEFILE, $cookie_file);
	}
	
	function setUserAgent( $user_agent = null ) {
		$this->user_agent = $user_agent;
		
		Hooks::runHook( 'HTTPSetUserAgent', array( &$user_agent ) );
		
		curl_setopt($this->curl_instance,CURLOPT_USERAGENT, $user_agent);
	}
	
	/**
	 * Get an url with HTTP GET
	 * 
	 * @access public
	 * @param string $url URL to get
	 * @param array $data Array of data to pass. Gets transformed into the URL inside the function. Default null.
	 * @return bool|string Result
	 */
	function get( $url, $data = null ) {
		global $argv, $pgProxy;
		
		if( count( $pgProxy ) ) {
			curl_setopt($this->curl_instance,CURLOPT_PROXY, $pgProxy['addr']);
			if( isset( $pgProxy['type'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYTYPE, $pgProxy['type']);
			}
			if( isset( $pgProxy['userpass'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYUSERPWD, $pgProxy['userpass']);
			}
			if( isset( $pgProxy['port'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYPORT, $pgProxy['port']);
			}
		}
		
		curl_setopt($this->curl_instance,CURLOPT_FOLLOWLOCATION,1);
		curl_setopt($this->curl_instance,CURLOPT_HTTPGET,1);
		
		/*if( !is_null( $this->use_cookie ) ) {
			curl_setopt($this->curl_instance,CURLOPT_COOKIE, $this->use_cookie);
		}*/
		
		if( !is_null( $data ) && is_array( $data ) ) {
			$url .= '?' . http_build_query( $data );
		}
		
		curl_setopt($this->curl_instance,CURLOPT_URL,$url);
		
		if( (!is_null( $argv ) && in_array( 'peachyecho', $argv )) || $this->echo ) {
			pecho( "GET: $url\n", PECHO_NORMAL );
		}
		
		Hooks::runHook( 'HTTPGet', array( &$this, &$url, &$data ) );
        
        for( $i = 0; $i <= 20; $i++ ) {
            try {$data = curl_exec( $this->curl_instance );}
            catch( Exception $e ) {
                if( curl_errno( $this->curl_instance ) != 0 ) throw new CURLError( curl_errno( $this->curl_instance ), curl_error( $this->curl_instance ) );
                if( $i == 20 ) {
                    pecho( "Warning: A CURL error occured.  Attempted 20 times.  Terminating attempts.", PECHO_WARN);
                    return false;
                } else pecho( "Warning: A CURL error occured.  Details can be found in the PHP error log.  Retrying...", PECHO_WARN);
                continue; 
            }
            break;
        }
		
		return $data;
		
	}
	
	/**
	 * Returns the HTTP code of the last request
	 * 
	 * @access public
	 * @return int HTTP code
	 */
	function get_HTTP_code() {
		$ci = curl_getinfo( $this->curl_instance );
		return $ci['http_code'];
	}
	
	/**
	 * Sends data via HTTP POST
	 * 
	 * @access public
	 * @param string $url URL to send
	 * @param array $data Array of data to pass.
	 * @return bool|string Result
	 */
	function post( $url, $data ) {
		global $argv, $pgProxy;
		
		if( count( $pgProxy ) ) {
			curl_setopt($this->curl_instance,CURLOPT_PROXY, $pgProxy['addr']);
			if( isset( $pgProxy['type'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYTYPE, $pgProxy['type']);
			}
			if( isset( $pgProxy['userpass'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYUSERPWD, $pgProxy['userpass']);
			}
			if( isset( $pgProxy['port'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYPORT, $pgProxy['port']);
			}
		}
		
		curl_setopt($this->curl_instance,CURLOPT_FOLLOWLOCATION,0);
		curl_setopt($this->curl_instance,CURLOPT_POST,1);
		curl_setopt($this->curl_instance,CURLOPT_POSTFIELDS, $data);
		
		/*if( !is_null( $this->use_cookie ) ) {
			curl_setopt($this->curl_instance,CURLOPT_COOKIE, $this->use_cookie);
		}*/
		
		curl_setopt($this->curl_instance,CURLOPT_URL,$url);
		
		if( (!is_null( $argv ) && in_array( 'peachyecho', $argv )) || $this->echo ) {
			pecho( "POST: $url\n", PECHO_NORMAL );
		}
		
		Hooks::runHook( 'HTTPPost', array( &$this, &$url, &$data ) );
		
		for( $i = 0; $i <= 20; $i++ ) {
            try {$data = curl_exec( $this->curl_instance );}
            catch( Exception $e ) {
                if( curl_errno( $this->curl_instance ) != 0 ) throw new CURLError( curl_errno( $this->curl_instance ), curl_error( $this->curl_instance ) );
                if( $i == 20 ) {
                    pecho( "Warning: A CURL error occured.  Attempted 20 times.  Terminating attempts.", PECHO_WARN);
                    return false;
                } else pecho( "Warning: A CURL error occured.  Details can be found in the PHP error log.  Retrying...", PECHO_WARN);
                continue; 
            }
            break;
        }

		return $data;
	}
	
	/**
	 * Downloads an URL to the local disk
	 * 
	 * @access public
	 * @param string $url URL to get
	 * @param array $local Local filename to download to
	 * @return bool
	 */
	function download( $url, $local ) {
		global $argv, $pgProxy;
		
		$out = fopen($local, 'wb'); 
		
		if( count( $pgProxy ) ) {
			curl_setopt($this->curl_instance,CURLOPT_PROXY, $pgProxy['addr']);
			if( isset( $pgProxy['type'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYTYPE, $pgProxy['type']);
			}
			if( isset( $pgProxy['userpass'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYUSERPWD, $pgProxy['userpass']);
			}
			if( isset( $pgProxy['port'] ) ) {
				curl_setopt($this->curl_instance,CURLOPT_PROXYPORT, $pgProxy['port']);
			}
		}
		

		//curl_setopt($this->curl_instance, CURLOPT_FILE, $out);
		curl_setopt($this->curl_instance, CURLOPT_URL, $url);
		curl_setopt($this->curl_instance, CURLOPT_HEADER, 0);
		
		if( (!is_null( $argv ) && in_array( 'peachyecho', $argv )) || $this->echo ) {
			pecho( "DLOAD: $url\n", PECHO_NORMAL );
		}
		
		Hooks::runHook( 'HTTPDownload', array( &$this, &$url, &$local ) );
		
		for( $i = 0; $i <= 20; $i++ ) {
            try {$ret = curl_exec( $this->curl_instance );}
            catch( Exception $e ) {
                if( curl_errno( $this->curl_instance ) != 0 ) throw new CURLError( curl_errno( $this->curl_instance ), curl_error( $this->curl_instance ) );
                if( $i == 20 ) {
                    pecho( "Warning: A CURL error occured.  Attempted 20 times.  Terminating attempts.", PECHO_WARN);
                    return false;
                } else pecho( "Warning: A CURL error occured.  Details can be found in the PHP error log.  Retrying...", PECHO_WARN);
                continue; 
            }
            break;
        }
		
		fwrite( $out, $ret );
		
		fclose($out);
		return true;
		
	}
	
	/**
	 * Destructor, deletes cookies and closes cURL class
	 * 
	 * @access public
	 * @return void
	 */
	function __destruct () {
		Hooks::runHook( 'HTTPClose', array( &$this ) );
		
		curl_close($this->curl_instance);
		
		//@unlink($this->cookie_jar);
	}


}