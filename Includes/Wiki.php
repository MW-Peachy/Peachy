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
 * Wiki object
 */

/**
 * Wiki class
 * Stores and runs methods that don't fit in User, Page, or Image, etc.
 */
class Wiki {
	
	/**
	 * URL to the API for the wiki.
	 * 
	 * @var string
	 * @access protected
	 */
	protected $base_url;
	
	/**
	 * Username for the user editing the wiki.
	 * 
	 * @var string
	 * @access protected
	 */
	protected $username;
	
	/**
	 * Edit of editing for the wiki in EPM.
	 * 
	 * @var int
	 * @access protected
	 */
	protected $edit_rate;
	
	/**
	 * Maximum db lag that the bot will accept. False to disable.
	 * 
	 * (default value: false)
	 * 
	 * @var bool|int
	 * @access protected
	 */
	protected $maxlag = false;
	
	/**
	 * Limit of results that can be returned by the API at one time.
	 * 
	 * (default value: 49)
	 * 
	 * @var int
	 * @access protected
	 */
	protected $apiQueryLimit = 49;
	
	/**
	 * Does the user have a bot flag.
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $isFlagged = false;
	
	/**
	 * Array of extenstions on the Wiki in the form of name => version.
	 * 
	 * @var array
	 * @access protected
	 */
	protected $extensions;
	
	/**
	 * Array of tokens for editing.
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $tokens = array();
	
	/**
	 * Array of rights assigned to the user.
	 * 
	 * (default value: array())
	 * 
	 * @var array
	 * @access protected
	 */
	protected $userRights = array();
	
	/**
	 * Array of namespaces by ID.
	 * 
	 * (default value: null)
	 * 
	 * @var array
	 * @access protected
	 */
	protected $namespaces = null;
	
	/**
	 * array of namespaces that have subpages allowed, by namespace id.
	 * 
	 * (default value: null)
	 * 
	 * @var array
	 * @access protected
	 */
	protected $allowSubpages = null;
	
	/**
	 * Should the wiki follow nobots rules?
	 * 
	 * (default value: true)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $nobots = true;
	
	/**
	 * Text to search for in the optout= field of the {{nobots}} template
	 * 
	 * (default value: null)
	 * 
	 * @var null
	 * @access protected
	 */
	protected $optout = null;
	
	/**
	 * Whether or not to not edit if the user has new messages
	 * 
	 * (default value: false)
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $stoponnewmessages = false;
	
	/**
	 * Page title to use for enable page
	 * 
	 * @var string
	 * @access protected
	 */
	protected $runpage;
	
	/**
	 * Configuration (sans password)
	 * 
	 * @var array
	 * @access protected
	 */
	protected $configuration;
	
	/**
	 * HTTP Class
	 * 
	 * @var HTTP
	 * @access protected
	 */
	protected $http;
	
	/**
	 * Whether or not to log in. True restricts logging in, false lets it log in. Setting to true restricts the available functions.
	 * 
	 * @var bool
	 * @access protected
	 */
	protected $nologin;
	
	/**
	 * Server that handled the last API query
	 * 
	 * @var string
	 * @access protected
	 */
	protected $servedby;
	
	/**
	 * Generator values for the generator parameter
	 * 
	 * @var array
	 * @access protected
	 */
	protected $generatorvalues;
	
	/**
	 * Version of MediaWiki server is running. 
	 * The only reason this is public is so the Peachy class can set it. It should not be changed again.
	 * 
	 * @var string
	 * @access public
	 */
	public $mwversion;
	
	/**
	 * Contruct function for the wiki. Handles login and related functions.
	 * 
	 * @access public
	 * @see Peachy::newWiki()
	 * @param array $configuration Array with configuration data. At least needs username, password, and base_url.
	 * @param array $extensions Array of names of extensions installed on the wiki and their versions (default: array())
	 * @param int $recursed Is the function recursing itself? Used internally, don't use (default: 0)
	 * @param mixed $token Token if the wiki needs a token. Used internally, don't use (default: null)
	 * @return void|Wiki|bool
	 */
	function __construct( $configuration, $extensions = array(), $recursed = 0, $token = null ) {
		global $pgProxy, $pgVerbose;

		if( !array_key_exists( 'encodedparams', $configuration ) ) {
			$configuration['encodedparams'] = rawurlencode( serialize( $configuration ) );
		}
		
		$this->base_url = $configuration['baseurl'];
		$this->username = $configuration['username'];
		$this->extensions = $extensions;
		$this->generatorvalues = array( 'allcategories', 'allimages', 'alllinks', 'allpages', 'alltransclusions', 'backlinks', 'categories',
							'categorymembers', 'duplicatefiles', 'embeddedin', 'exturlusage', 'geosearch', 'images', 'imageusage',
                            'iwbacklinks', 'langbacklinks', 'links', 'oldreviewedpages', 'protectedtitles', 'querypage', 'random',
                            'recentchanges', 'search', 'templates', 'watchlist', 'watchlistraw' );
		
		if( isset( $configuration['editsperminute'] ) && $configuration['editsperminute'] != 0) {
			$this->edit_rate = $configuration['editsperminute'];
		}
		
		if( isset( $configuration['proxyaddr'] ) ) {
			$pgProxy['addr'] = $configuration['proxyaddr'];
			
			if( isset( $configuration['proxytype'] ) ) {
				$pgProxy['type'] = $configuration['proxytype'];
			}
			
			if( isset( $configuration['proxyport'] ) ) {
				$pgProxy['port'] = $configuration['proxyport'];
			}
			
			if( isset( $configuration['proxyuser'] ) && isset( $configuration['proxypass'] ) ) {
				$pgProxy['userpass'] = $configuration['proxyuser'] . ':' . $configuration['proxypass'];
			}
		}

		$http_echo = ( isset( $configuration['httpecho'] ) && $configuration['httpecho'] === "true" );
		if( is_null( $this->http ) ) $this->http = HTTP::getDefaultInstance( $http_echo );

		if( isset( $configuration['runpage'] ) ) {
			$this->runpage = $configuration['runpage'];
		}
		
		if( isset( $configuration['useragent'] ) ) {
			$this->http->setUserAgent( $configuration['useragent'] );
		}
		
		$use_cookie_login = false;
		if( isset( $configuration['cookiejar'] ) ) {
			$this->http->setCookieJar( $configuration['cookiejar'] );
		}
		else {
			
			$this->http->setCookieJar( sys_get_temp_dir() . 'PeachyCookieSite' . sha1( $configuration['encodedparams'] ) );
			
			if( $this->is_logged_in() ) $use_cookie_login = true;
		}
		
		if( isset( $configuration['optout'] ) ) {
			$this->optout = $configuration['optout'];
		}
		
		if( isset( $configuration['stoponnewmessages'] ) ) {
			$this->stoponnewmessages = true;
		}
		
		if( isset( $configuration['verbose'] ) ) {
			$pgVerbose = array();
			
			$tmp = explode('|',$configuration['verbose']);
			
			foreach( $tmp as $setting ) {
				if( $setting == "ALL" ) {
					$pgVerbose = array(
						PECHO_NORMAL,
						PECHO_NOTICE,
						PECHO_WARN,
						PECHO_ERROR,
						PECHO_FATAL
					);
					break;
				}
				else {
					switch( $setting ) {
						case 'NORMAL':
							$pgVerbose[] = PECHO_NORMAL;
							break;
						case 'NOTICE':
							$pgVerbose[] = PECHO_NOTICE;
							break;
						case 'WARN':
							$pgVerbose[] = PECHO_WARN;
							break;
						case 'ERROR':
							$pgVerbose[] = PECHO_ERROR;
							break;
						case 'FATAL':
							$pgVerbose[] = PECHO_FATAL;
							break;
						case 'VERBOSE':
							$pgVerbose[] = PECHO_VERBOSE;
							break;
					}
				}
			}
			
			unset( $tmp );
		}
		
		if( ( isset($configuration['nobots']) && $configuration['nobots'] == 'false' ) || strpos( $configuration['baseurl'], '//en.wikipedia.org/w/api.php' ) === false ) {
			$this->nobots = false;
		}
		
		$lgarray = array(
			'lgname' => $this->username,
			'lgpassword' => $configuration['password'],
			'action' => 'login',
		);
		
		if( isset( $configuration['maxlag'] ) && $configuration['maxlag'] != "0" ) {
			$this->maxlag = $configuration['maxlag'];
			$lgarray['maxlag'] = $this->maxlag;
		}
		
		if( !is_null( $token ) ) {
			$lgarray['lgtoken'] = $token;
		}
		
		if( isset( $configuration['nologin'] ) ) {
			$this->nologin = true;
			return;
		}
		
		if( $use_cookie_login ) {
			pecho( "Logging in to {$this->base_url} as {$this->username}, using a saved login cookie\n\n", PECHO_NORMAL );
					
			$this->runSuccess( $configuration );
		}
		elseif( !$this->nologin ) {
			Hooks::runHook( 'PreLogin', array( &$lgarray ) );
			
			if( !$recursed ) {
				pecho( "Logging in to {$this->base_url}...\n\n", PECHO_NOTICE );
			}
			
			$loginRes = $this->apiQuery( $lgarray, true );
			
			Hooks::runHook( 'PostLogin', array( &$loginRes ) );
		}
		
		if( isset( $loginRes['login']['result'] ) ) {
			switch( $loginRes['login']['result'] ) {
				case 'NoName':
					throw new LoginError( array( 'NoName', 'Username not specified' ) );
					break;
				case 'Illegal':
					throw new LoginError( array( 'Illegal', 'Username with illegal characters specified' ) );
					break;
				case 'NotExists':
					throw new LoginError( array( 'NotExists', 'Username specified does not exist' ) );
					break;
				case 'EmptyPass':
					throw new LoginError( array( 'EmptyPass', 'Password not specified' ) );
					break;
				case 'WrongPass':
					throw new LoginError( array( 'WrongPass', 'Incorrect password specified' ) );
					break;
				case 'WrongPluginPass':
					throw new LoginError( array( 'WrongPluginPass', 'Incorrect password specified' ) );
					break;
				case 'CreateBlocked':
					throw new LoginError( array( 'CreateBlocked', 'IP address has been blocked' ) );
					break;
				case 'Throttled':
					if( $recursed > 2 ) throw new LoginError( array( 'Throttled', 'Login attempts have been throttled' ) );
					
					$wait = $loginRes['login']['wait'];
					pecho( "Login throttled, waiting $wait seconds.\n\n", PECHO_NOTICE );
					sleep($wait);
					
					$recres = $this->__construct( $configuration, $this->extensions, $recursed + 1 );
					return $recres;
					break;
				case 'Blocked':
					throw new LoginError( array( 'Blocked', 'User specified has been blocked' ) );
					break;
				case 'NeedToken':
					if( $recursed > 2 ) throw new LoginError( array( 'NeedToken', 'Token was not specified' ) );
					
					$token = $loginRes['login']['token'];

					$recres = $this->__construct( $configuration, $this->extensions, $recursed + 1, $token );
					return $recres;
					break;
				case 'Success':
					pecho( "Successfully logged in to {$this->base_url} as {$this->username}\n\n", PECHO_NORMAL );
					
					$this->runSuccess( $configuration );
					

			}
		}
		
	}
	
	public function is_logged_in() {
		$cookieInfo = $this->apiQuery( array( 'action' => 'query', 'meta' => 'userinfo' ) );
		if( $cookieInfo['query']['userinfo']['id'] != 0 ) return true;
		return false;
	}
	
	
	/**
	 * runSuccess function.
	 * 
	 * @access protected
	 * @param mixed &$configuration
	 * @return void
	 */
	protected function runSuccess( &$configuration ) {
		$userInfoRes = $this->apiQuery(
			array(
				'action' => 'query',
				'meta' => 'userinfo',
				'uiprop' => 'blockinfo|rights|groups'
			)
		);
		
		if( in_array( 'apihighlimits', $userInfoRes['query']['userinfo']['rights'] ) ) {
			$this->apiQueryLimit = 4999;
		}
		else {
			$this->apiQueryLimit = 499;
		}
		
		$this->userRights = $userInfoRes['query']['userinfo']['rights'];
		
		if( in_array( 'bot', $userInfoRes['query']['userinfo']['groups'] ) ) {
			$this->isFlagged = true;
		}
		
		$this->get_tokens();
		
		$this->configuration = $configuration;
		unset($this->configuration['password']);
	}
	
	/**
	 * Logs the user out of the wiki.
	 *
	 * @access public
	 * @return void
	 */
	public function logout() {
		pecho( "Logging out of {$this->base_url}...\n\n", PECHO_NOTICE );
		
		$this->apiQuery( array( 'action' => 'logout' ), true );
		
	}
	
	/**
	 * Sets a specific runpage for a script.
	 *
	 * @param string $page Page to set as the runpage. Default null.
	 * @access public
	 * @return void
	 */
	public function set_runpage( $page = null ) {
		$this->runpage = $page;
	}
	
	/**
	 * Queries the API.
	 * 
	 * @access public
	 * @param array $arrayParams Parameters given to query with (default: array())
	 * @param bool $post Should it be a POST reqeust? (default: false)
	 * @param bool $recursed Is this a recursed reqest (default: false)
	 * @return array Returns an array with the API result
	 */
	public function apiQuery( $arrayParams = array(), $post = false, $errorcheck = true, $recursed = false, $forcenoassert = false ) {
		
		global $pgIP, $maxattempts, $killonfailure, $displayGetOutData, $logCommunicationData, $logFailedCommunicationData, $logGetCommunicationData, $logPostCommunicationData, $logSuccessfulCommunicationData;
        $requestid = mt_rand();
		$attempts = $maxattempts;
		$arrayParams['format'] = 'php';
		$arrayParams['servedby'] = '';
		$arrayParams['requestid'] = $requestid;
		$assert = false;
		
        if( !file_exists($pgIP.'Includes/Communication_Logs') ) mkdir($pgIP.'Includes/Communication_Logs', 2775);
		if( $post && $this->isFlagged && isset( $arrayParams['assert'] ) && $arrayParams['assert'] == 'user' ) {
			$arrayParams['assert'] = 'bot';
			$assert = true;
            Hooks::runHook( 'QueryAssert', array( &$arrayParams['assert'], &$assert ) );
		}
		
		pecho( "Running API query with params " . implode( ";", $arrayParams ) . "...\n\n", PECHO_VERBOSE );
		
		if( $post ) {
			$is_loggedin = $this->get_nologin();
			Hooks::runHook( 'APIQueryCheckLogin', array( &$is_loggedin ) );
			if( $is_loggedin && $errorcheck ) throw new LoggedOut();
			
			Hooks::runHook( 'PreAPIPostQuery', array( &$arrayParams ) );
			for( $i = 0; $i < $attempts; $i++ ) {
                $logdata = "Date/Time: ".date( 'r' )."\nMethod: POST\nURL: {$this->base_url} (Parameters masked for security)\nRaw Data: ";
                $data = $this->get_http()->post(
				    $this->base_url,
				    $arrayParams
			    );
                $logdata .= $data;
                $data2 = unserialize( $data );
                if( $data2 === FALSE && serialize( $data2 ) != $data ) {
                    $logdata .= "\nUNSERIALIZATION FAILED\n\n";
                    if( $logFailedCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Faileddata.log', $logdata, FILE_APPEND );
                }
                else {
                    $logdata .= "\nUNSERIALIZATION SUCCEEDED\n\n";
                    if( $logSuccessfulCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Succeededdata.log', $logdata, FILE_APPEND );
                }
                
                if( $logPostCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Postdata.log', $logdata, FILE_APPEND );
                if( $logCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Querydata.log', $logdata, FILE_APPEND );
                
                $data = $data2;
                unset( $data2 );
                if( $this->get_http()->get_HTTP_code() == 503 && $errorcheck ) {
                    pecho( "API Error...\n\nCode: error503\nText: HTTP Error 503\nThe webserver's service is currently unavailable", PECHO_WARN );
                    $tempSetting = $displayGetOutData;
                    $displayGetOutData = false;
                    $histemp = $this->initPage( $arrayParams['title'] )->history( 1 );
                    if( $arrayParams['action'] == 'edit' && $histemp[0]['user'] == $this->get_username() && $histemp[0]['comment'] == $arrayParams['summary'] && strtotime($histemp[0]['timestamp']) - time() < 120 ) {
                        pecho( ", however, the edit appears to have gone through.\n\n", PECHO_WARN );
                        $displayGetOutData = $tempSetting;
                        unset( $tempSetting );
                        return array( 'edit'=>array( 'result'=>'Success', 'newrevid'=>$histemp[0]['revid'] ) );
                    } else {
                        pecho( ", retrying...\n\n", PECHO_WARN );
                        $displayGetOutData = $tempSetting;
                        unset( $tempSetting );
                        continue;
                    }
                }
                
                if( !isset( $data['servedby'] ) && !isset( $data['requestid'] ) ) { 
                    pecho( "Warning: API is not responding, retrying...\n\n", PECHO_WARN );
                }
                else break;
            }
            if( $this->get_http()->get_HTTP_code() == 503 && $errorcheck ) {
                pecho( "API Error...\n\nCode: error503\nText: HTTP Error 503\nThe webserver's service is currently unavailable", PECHO_WARN );
                $tempSetting = $displayGetOutData;
                $displayGetOutData = false;
                $histemp = $this->initPage( $arrayParams['title'] )->history( 1 );
                if( $arrayParams['action'] == 'edit' && $histemp['user'] == $this->get_username() && $histemp['comment'] == $arrayParams['summary'] && strtotime($histemp['timestamp']) - time() < 120 ) {
                    pecho( ", however, the edit, finally, appears to have gone through.\n\n", PECHO_WARN );
                    $displayGetOutData = $tempSetting;
                    return array( 'edit'=>array( 'result'=>'Success', 'newrevid'=>$histemp['revid'] ) );
                } else {
                    $displayGetOutData = $tempSetting;
                    if( $killonfailure ) {
                        pecho( ".  Terminating program.\n\n", PECHO_FATAL );
                        exit(1);
                    } else {
                        pecho( ".  Aborting attempts.", PECHO_FATAL );
                        return false;
                    }
                }
            }
            
			if( !isset( $data['servedby'] ) && !isset( $data['requestid'] ) ) {
                if( $killonfailure ) {
                    pecho( "Fatal Error: API is not responding.  Terminating program.\n\n", PECHO_FATAL );
                    exit(1);
                } else {
                    pecho( "API Error: API is not responding.  Aborting attempts.\n\n", PECHO_FATAL );
                    return false;
                }
            }

			Hooks::runHook( 'PostAPIPostQuery', array( &$data ) );
			
			Hooks::runHook( 'APIQueryCheckAssertion', array( &$assert, &$data['edit']['assert'] ) );
			if( $assert && $data['edit']['assert'] == 'Failure' && $errorcheck ) {
				pecho( "Assertion has failed.\n\n" . print_r( $data['edit'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
			
			Hooks::runHook( 'APIQueryCheckError', array( &$data['error'] ) );
			if( isset( $data['error'] ) && $errorcheck ) {
				
				pecho( "API Error...\n\nCode: {$data['error']['code']}\nText: {$data['error']['info']}\n\n", PECHO_FATAL );
				return false;
			}
			
			if( isset( $data['servedby'] ) ) {
				$this->servedby = $data['servedby'];
			}
			
			if( isset( $data['requestid'] ) ) {
				if( $data['requestid'] != $requestid ) {
					if( $recursed ) {
						pecho( "API Error... requestid's didn't match twice.\n\n", PECHO_FATAL );
						return false;
					}
					return $this->apiQuery( $arrayParams, $post, $errorcheck, true );
				}
			}

			return $data;
		}
		else {
		
			Hooks::runHook( 'PreAPIGetQuery', array( &$arrayParams ) );
			
			for( $i = 0; $i < $attempts; $i++ ) {
                $logdata = "Date/Time: ".date( 'r' )."\nMethod: GET\nURL: {$this->base_url}\nParameters: ".print_r( $arrayParams, true )."\nRaw Data: ";
                $data = $this->get_http()->get(
                    $this->base_url,
                    $arrayParams
                );
                $logdata .= $data;
                $data2 = unserialize( $data );
                if( $data2 === FALSE && serialize( $data2 ) != $data ) {
                    $logdata .= "\nUNSERIALIZATION FAILED\n\n";
                    if( $logFailedCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Faileddata.log', $logdata, FILE_APPEND );
                }
                else {
                    $logdata .= "\nUNSERIALIZATION SUCCEEDED\n\n";
                    if( $logSuccessfulCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Succeededdata.log', $logdata, FILE_APPEND );
                }
                
                if( $logGetCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Getdata.log', $logdata, FILE_APPEND );
                if( $logCommunicationData ) file_put_contents( $pgIP.'Includes/Communication_Logs/Querydata.log', $logdata, FILE_APPEND );
                
                $data = $data2;
                unset( $data2 );
                if( $this->get_http()->get_HTTP_code() == 503 && $errorcheck ) {
                    pecho( "API Error...\n\nCode: error503\nText: HTTP Error 503\nThe webserver's service is currently unavailable, retrying...", PECHO_WARN );
                }
                
                if( !isset( $data['servedby'] ) && !isset( $data['requestid'] ) ) { 
                    pecho( "Warning: API is not responding, retrying...\n\n", PECHO_WARN );
                }
                else break;
            }
            
            if( $this->get_http()->get_HTTP_code() == 503 && $errorcheck ) {
                if( $killonfailure ) {
                    pecho( "Fatal Error: API Error...\n\nCode: error503\nText: HTTP Error 503\nThe webserver's service is still not available.  Terminating program.\n\n", PECHO_FATAL );
                    exit(1);   
                } else {
                    pecho( "API Error...\n\nCode: error503\nText: HTTP Error 503\nThe webserver's service is still not available.  Aborting attempts.\n\n", PECHO_FATAL );
                    return false;    
                }
                
            }
            
            if( !isset( $data['servedby'] ) && !isset( $data['requestid'] ) ) {
                if( $killonfailure ) {
                    pecho( "Fatal Error: API is not responding.  Terminating program.\n\n", PECHO_FATAL );
                    exit(1);    
                } else {
                    pecho( "API Error: API is not responding.  Aborting attempts.\n\n", PECHO_FATAL );
                    return false; 
                }
            }
            
			Hooks::runHook( 'APIQueryCheckError', array( &$data['error'] ) );
			if( isset( $data['error'] ) && $errorcheck ) {
				
				pecho( "API Error...\n\nCode: {$data['error']['code']}\nText: {$data['error']['info']}\n\n", PECHO_FATAL );
				return false;
			}
			
			if( isset( $data['servedby'] ) ) {
				$this->servedby = $data['servedby'];
			}
			
			if( isset( $data['requestid'] ) ) {
				if( $data['requestid'] != $requestid ) {
					if( $recursed ) {
						pecho( "API Error... requestid's didn't match twice.\n\n", PECHO_FATAL );
						return false;
					}
					return $this->apiQuery( $arrayParams, $post, $errorcheck, true );
				}
			}
			
			return $data;
		}
	}
	
	/**
	 * Returns the server that handled the previous request. Only works on MediaWiki versions 1.17 and up
	 *
	 * @return string
	 */
	public function get_servedby() {
		return $this->servedby;
	}
	
	/**
	 * Returns the version of MediaWiki that is on the server
	 *
	 * @return string
	 */
	public function get_mw_version() {
		return $this->mwversion;
	}
	
	/**
	 * Simplifies the running of API queries, especially with continues and other parameters.
	 * 
	 * @access public
	 * @link http://wiki.peachy.compwhizii.net/wiki/Manual/Wiki::listHandler
	 * @param array $tArray Parameters given to query with (default: array()). In addition to those recognised by the API, ['_code'] should be set to the first two characters of all the parameters in a list=XXX API call - for example, with allpages, the parameters start with 'ap', with recentchanges, the parameters start with 'rc' -  and is required; ['_limit'] imposes a hard limit on the number of results returned (optional) and ['_lhtitle'] simplifies a multidimensional result into a unidimensional result - lhtitle is the key of the sub-array to return. (optional)
	 * @throws BadEntryError
	 * @return array Returns an array with the API result
	 */
	public function listHandler( $tArray = array() ) {
		
		if( isset( $tArray['_code'] ) ){
			$code = $tArray['_code'];
			unset( $tArray['_code'] );
		} else {		
			throw new BadEntryError( "listHandler", "Parameter _code is required." );
		}
		if( isset( $tArray['_limit'] ) ) {
			$limit = $tArray['_limit'];
			unset( $tArray['_limit'] );
		} else {
			$limit = null;
		}
		if( isset( $tArray['_lhtitle'] ) ) {
			$lhtitle = $tArray['_lhtitle'];
			unset( $tArray['_lhtitle'] );
		} else {
			$lhtitle = null;
		}
		
		$tArray['action'] = 'query';
		$tArray[$code . 'limit'] = 'max';
		$retrieved = 0;
		
		if( isset($limit) && !is_null($limit) ){
			if(!is_numeric($limit)){
				throw new BadEntryError( "listHandler", "limit should be a number or null" );
			} else {
				$limit = intval($limit);
				if($limit < 0 || (floor($limit) != $limit)){
                    if( !$limit == -1 ) throw new BadEntryError( "listHandler", "limit should an integer greater than 0" );
                    else $limit = 'max';
				}
				$tArray[$code . 'limit'] = $limit;
			}
		} 
		if( isset($tArray[$code . 'namespace']) && !is_null($tArray[$code . 'namespace']) ){
			if( is_array( $tArray[$code . 'namespace'] )){
				$tArray[$code . 'namespace'] = implode('|', $tArray[$code . 'namespace'] );
			} elseif(strlen($tArray[$code . 'namespace']) === 0) {
				$tArray[$code . 'namespace'] = null;
			} else {
				$tArray[$code . 'namespace'] = (string)$tArray[$code . 'namespace'];
			}
		}
		
		$endArray = array();
		
		$continue = null;
		$offset = null;
		$start = null;
        $from = null;
		
		pecho( "Running list handler function with params " . implode( ";", $tArray ) . "...\n\n", PECHO_VERBOSE );
		
		while( 1 ) { 
			
			if( !is_null( $continue ) ) $tArray[$code . 'continue'] = $continue;
			if( !is_null( $offset ) ) $tArray[$code . 'offset'] = $offset;
			if( !is_null( $start ) ) $tArray[$code . 'start'] = $start;
            if( !is_null( $from ) ) $tArray[$code . 'from'] = $from;
			
			$tRes = $this->apiQuery( $tArray );
			if(!isset($tRes['query'])) break;
			
			foreach( $tRes['query'] as $x ) {
				foreach( $x as $y ) {
					if( !is_null( $lhtitle ) ) {
						if( isset( $y[ $lhtitle ] ) ) {
							$y = $y[ $lhtitle ];
						}
						else {
							continue;
						}
					}
					
					$endArray[] = $y;
				}
			}
			
			if(!is_null($limit) && $limit != 'max'){
				if(count($endArray) >= $limit){
					$endArray = array_slice($endArray,0,$limit);
                    break;
				}
			}
			
			if( isset( $tRes['query-continue'] ) ) {
				foreach( $tRes['query-continue'] as $z ) {
					if( isset( $z[$code . 'continue'] ) ){
						$continue = $z[$code . 'continue'];
					} elseif ( isset( $z[$code . 'offset'] ) ){
						$offset = $z[$code . 'offset'];
					} elseif ( isset( $z[$code . 'start'] ) ){
						$start = $z[$code . 'start'];
					} elseif ( isset( $z[$code . 'from'] ) ) {
                        $from = $z[$code . 'from'];
                    }
				}
			}
			else {
				break;
			}	
			
		}
		
		return $endArray;
	}
	
	/**
	 * Returns a reference to the HTTP Class
	 * 
	 * @access public
	 * @see Wiki::$http
	 * @return HTTP
	 */
	public function &get_http() {
		return $this->http;
	}
	
	/**
	 * Returns whether or not to log in
	 * 
	 * @access public
	 * @see Wiki::$nologin
	 * @return bool
	 */
	public function get_nologin() {
		return $this->nologin;
	}
	
	/**
	 * Returns the base URL for the wiki.
	 * 
	 * @access public
	 * @see Wiki::$base_url
	 * @return string base_url for the wiki
	 */
	public function get_base_url() {
		return $this->base_url;
	}
	
	/**
	 * Returns the api query limit for the wiki.
	 * 
	 * @access public
	 * @see Wiki::$apiQueryLimit
	 * @return int apiQueryLimit fot the wiki
	 */
	public function get_api_limit() {
		return $this->apiQueryLimit;
	}
	
	/**
	 * Returns the runpage.
	 * 
	 * @access public
	 * @see Wiki::$runpage
	 * @return string Runpage for the user
	 */
	public function get_runpage() {
		return $this->runpage;
	}
	
	/**
	 * Returns if maxlag is on or what it is set to for the wiki.
	 * 
	 * @access public
	 * @see Wiki:$maxlag
	 * @return bool|int Max lag for the wiki
	 */
	public function get_maxlag() {
		return $this->maxlag;
	}
	
	/**
	 * Returns the edit rate in EPM for the wiki.
	 * 
	 * @access public
	 * @see Wiki::$edit_rate
	 * @return int Edit rate in EPM for the wiki
	 */
	public function get_edit_rate() {
		return $this->edit_rate;
	}
	
	/**
	 * Returns the username.
	 * 
	 * @access public
	 * @see Wiki::$username
	 * @return string Username
	 */
	public function get_username() {
		return $this->username;
	}
	
	/**
	 * Returns if the Wiki should follow nobots rules.
	 * 
	 * @access public
	 * @see Wiki::$nobots
	 * @return bool True for following nobots
	 */
	public function get_nobots() {
		return $this->nobots;
	}
	
	/**
	 * Returns if the script should not edit if the user has new messages
	 * 
	 * @access public
	 * @see Wiki::$stoponnewmessages
	 * @return bool True for stopping on new messages
	 */
	public function get_stoponnewmessages() {
		return $this->stoponnewmessages;
	}
	
	/**
	 * Returns the text to search for in the optout= field of the {{nobots}} template
	 * 
	 * @access public
	 * @see Wiki::$optout
	 * @return null|string String to search for
	 */
	public function get_optout() {
		return $this->optout;
	}
	
	/**
	 * Returns the configuration of the wiki
	 * 
	 * @param string $conf_name Name of configuration setting to get. Default null, will return all configuration.
	 * @access public
	 * @see Wiki::$configuration
	 * @return array Configuration array
	 */
	public function get_configuration( $conf_name = null ) {
		if( is_null( $conf_name ) ) {
			return $this->configuration;
		}
		else {
			return $this->configuration[$conf_name];
		}
	}
	
	public function get_conf( $conf_name = null ) {
		return $this->get_configuration( $conf_name );
	}
	
	/**
	 * Purges a list of pages
	 * 
	 * @access public
	 * @param array|string $titles A list of titles to work on
	 * @param array|string $pageids A list of page IDs to work on
	 * @param array|string $revids A list of revision IDs to work on
	 * @param bool $redirects Automatically resolve redirects. Default false.
	 * @param bool $force Update the links tables. Default false.
	 * @param bool $converttitles Convert titles to other variants if necessary. Default false.
	 * @param string $generator Get the list of pages to work on by executing the specified query module. Default null.
	 * @return void|bool
	 */
	public function purge( $titles = null, $pageids = null, $revids = null, $force = false, $redirects = false, $convert = false, $generator = null ) {

		$apiArr = array(
			'action' => 'purge'
		);
	
		if( is_null( $titles ) && is_null( $pageids ) && is_null( $revids ) ) {
			pecho( "Error: Nothing to purge.\n\n", PECHO_WARN);
			return false;
		}
		Hooks::runHook( 'StartPurge', array( &$titles ) );
		if( !is_null( $titles ) ) {
			if( is_array( $titles ) ) $titles = implode( '|', $titles );
			$apiArr['titles'] = $titles;
		}
		if( !is_null( $pageids ) ) {
			if( is_array( $pageids ) ) $pageids = implode( '|', $pageids );
			$apiArr['pageids'] = $pageids;
		}
		if( !is_null( $revids ) ) {
			if( is_array( $revids ) ) $revids = implode( '|', $revids );
			$apiArr['revids'] = $revids;
		}
		if( $redirects ) $apiArr['redirects'] = 'yes';
		if( $force ) $apiArr['forcelinkupdate'] = 'yes';
		if( $convert ) $apiArr['converttitles'] = 'yes';
		
		$genparams = $this->generatorvalues;
		if( !is_null( $generator ) ) {
			if( in_array( $generator, $genparams ) ) $apiArr['generator'] = 'g'.$generator;
			else pecho( "Invalid generator value detected.  Omitting...\n\n", PECHO_WARN );
		}
		
		pecho( "Purging...\n\n", PECHO_NOTICE );
		
		Hooks::runHook( 'StartPurge', array( &$apiArr ) );
		
		$result = $this->apiQuery( $apiArr, true, true, false, true );
		
		if( isset( $result['purge'] ) ) {
			foreach( $result['purge'] as $page ) {
				if( !isset( $page['purged'] ) ) {
					pecho( "Purge error on {$page['title']}...\n\n" . print_r($page, true) . "\n\n", PECHO_FATAL );
					return false;
				}
			}
			return true;

		}
		else {
			pecho( "Purge error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
	}
	

	/**
	 * Returns a list of recent changes
	 *
	 * @access public
	 * @param int|array $namespace Namespace(s) to check
	 * @param string $tag Only list recent changes bearing this tag.
	 * @param int $start Only list changes after this timestamp.
	 * @param int $end Only list changes before this timestamp.
	 * @param string $user Only list changes by this user.
	 * @param string $excludeuser Only list changes not by this user.
	 * @param string $dir 'older' lists changes, most recent first; 'newer' least recent first.
	 * @param bool $minor Whether to only include minor edits (true), only non-minor edits (false) or both (null). Default null.
	 * @param bool $bot Whether to only include bot edits (true), only non-bot edits (false) or both (null). Default null.
	 * @param bool $anon Whether to only include anonymous edits (true), only non-anonymous edits (false) or both (null). Default null.
	 * @param bool $redirect Whether to only include edits to redirects (true), edits to non-redirects (false) or both (null). Default null.
	 * @param bool $patrolled Whether to only include patrolled edits (true), only non-patrolled edits (false) or both (null). Default null.
	 * @param array $prop What properties to retrieve. Default array( 'user', 'comment', 'flags', 'timestamp', 'title', 'ids', 'sizes', 'tags' ).
	 * @param int $limit A hard limit to impose on the number of results returned.
	 * @return array Recent changes matching the description.
	 */
	public function recentchanges( $namespace = 0, $tag = false, $start = false, $end = false, $user = false, $excludeuser = false, $dir = 'older', $minor = null, $bot = null, $anon = null, $redirect = null, $patrolled = null, $prop = array( 'user', 'comment', 'flags', 'timestamp', 'title', 'ids', 'sizes', 'tags' ), $limit = 50 ) {

		if( is_array( $namespace ) ) {
			$namespace = implode( '|', $namespace );
		}
		
		$rcArray = array(
			'list' => 'recentchanges',
			'_code' => 'rc',
			'rcnamespace' => $namespace,
			'rcdir' => $dir,
			'rcprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		if( $tag ) $rcArray['rctag'] = $tag;
		if( $start ) $rcArray['rcstart'] = $start;
		if( $end ) $rcArray['rcend'] = $end;
		if( $user ) $rcArray['rcuser'] = $user;
		if( $excludeuser ) $rcArray['rcexcludeuser'] = $excludeuser;
		
		$rcshow = array();
		
		if( !is_null( $minor ) ) { 
			if( $minor ) {
				$rcshow[] = 'minor';
			}
			else {
				$rcshow[] = '!minor';
			}
		}
		
		if( !is_null( $bot ) ) { 
			if( $bot ) {
				$rcshow[] = 'bot';
			}
			else {
				$rcshow[] = '!bot';
			}
		}
		
		if( !is_null( $anon ) ) { 
			if( $minor ) {
				$rcshow[] = 'anon';
			}
			else {
				$rcshow[] = '!anon';
			}
		}
		
		if( !is_null( $redirect ) ) { 
			if( $redirect ) {
				$rcshow[] = 'redirect';
			}
			else {
				$rcshow[] = '!redirect';
			}
		}
		
		if( !is_null( $patrolled ) ) { 
			if( $minor ) {
				$rcshow[] = 'patrolled';
			}
			else {
				$rcshow[] = '!patrolled';
			}
		}
		
		if( count( $rcshow ) ) $rcArray['rcshow'] = implode( '|', $rcshow );
		
		$rcArray['limit'] = $this->apiQueryLimit;
		
		Hooks::runHook( 'PreQueryRecentchanges', array( &$rcArray ) );
		
		pecho( "Getting recent changes...\n\n", PECHO_NORMAL );
		
		return $this->listHandler( $rcArray );
		
	}
	
	/**
	 * Performs a search and retrieves the results
	 *
	 * @access public
	 * @param string $search What to search for
	 * @param bool $fulltext Whether to search the full text of pages (default, true) or just titles (false; may not be enabled on all wikis).
	 * @param array $namespaces The namespaces to search in (default: array( 0 )).
	 * @param array $prop What properties to retrieve (default: array('size', 'wordcount', 'timestamp', 'snippet') ).
	 * @param bool $includeredirects Whether to include redirects or not (default: true).
	 * @param int $limit A hard limit on the number of results to retrieve (default: null i.e. all).
	 */
	public function search($search, $fulltext = true, $namespaces = array(0), $prop = array('size', 'wordcount', 'timestamp', 'snippet'), $includeredirects = true, $limit = 50) {
	
		$srArray = array(
			'_code' => 'sr',
			'list' => 'search',
			'_limit' => $limit,
			'srsearch' => $search,
			'srnamespace' => $namespaces,
			'srwhat' => ($fulltext) ? "text" : "title",
			'srinfo' => '', ##FIXME: find a meaningful way of passing back 'totalhits' and 'suggestion' as required.
			'srprop' => implode( '|', $prop ),
			'srredirects' => $includeredirects
		);
		
		pecho( "Searching for $search...\n\n", PECHO_NORMAL );
		
		return $this->listHandler($srArray);
	}
	
	/**
	 * Retrieves log entries from the wiki.
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Lists#logevents_.2F_le
	 * @param bool|array $type Type of log to retrieve from the wiki (default: false)
	 * @param bool|string $user Restrict the log to a certain user (default: false)
	 * @param bool|string $title Restrict the log to a certain page (default: false)
	 * @param bool|string $start Timestamp for the start of the log (default: false)
	 * @param bool|string $end Timestamp for the end of the log (default: false)
	 * @param string $dir Direction for retieving log entries (default: 'older')
	 * @param bool $tag Restrict the log to entries with a certain tag (default: false)
	 * @param array $prop Information to retieve from the log (default: array( 'ids', 'title', 'type', 'user', 'timestamp', 'comment', 'details' ))
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array Log entries
	 */
	public function logs( $type = false, $user = false, $title = false, $start = false, $end = false, $dir = 'older', $tag = false, $prop = array( 'ids', 'title', 'type', 'user', 'timestamp', 'comment', 'details' ), $limit = 50 ) {
		
		$leArray = array(
			'list' => 'logevents',
			'_code' => 'le',
			'ledir' => $dir,
			'leprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		if( is_array( $type ) ) $leArray['letype'] = implode( '|', $type );
		if( $start ) $leArray['lestart'] = $start;
		if( $end ) $leArray['leend'] = $end;
		if( $user ) $leArray['leuser'] = $user;
		if( $title ) $leArray['letitle'] = $title;
		if( $tag ) $leArray['letag'] = $tag;
		
		Hooks::runHook( 'PreQueryLog', array( &$leArray ) );
		
		if( $type ) {
			if( $title || $user ) $title = ' for ' . $title;
			pecho( "Getting $type logs{$title}{$user}...\n\n", PECHO_NORMAL );
		}
		else {
			pecho( "Getting logs...\n\n", PECHO_NORMAL );
		}
		
		return $this->listHandler( $leArray );
	}
    
    /**
     * Enumerate all categories
     * 
     * @access public
     * @link https://www.mediawiki.org/wiki/API:Allcategories
     * @param string $prefix Search for all category titles that begin with this value. (default: null)
     * @param string $from The category to start enumerating from. (default: null)
     * @param string $min Minimum number of category members. (default: null)
     * @param string $max Maximum number of category members. (default: null)
     * @param string $dir Direction to sort in. (default: 'ascending')
     * @param array $prop Information to retieve (default: array( 'size', 'hidden' ))
     * @param int $limit How many categories to return. (default: null i.e. all).
     * @return array List of categories
     */
    public function allcategories( $prefix = null, $from = null, $min = null, $max = null, $dir = 'ascending', $prop = array( 'size', 'hidden' ), $limit = 50 ) {
        $leArray = array(
            'list' => 'allcategories',
            '_code' => 'ac',
            'acdir' => $dir,
            'acprop' => implode( '|', $prop ),
            '_limit' => $limit
        );
        
        if( !is_null( $from ) ) $leArray['acfrom'] = $from;
        if( !is_null( $prefix ) ) $leArray['acprefix'] = $prefix;
        if( !is_null( $min ) ) $leArray['acmin'] = $min;
        if( !is_null( $max ) ) $leArray['acmax'] = $max;
                
        Hooks::runHook( 'PreQueryAllimages', array( &$leArray ) );
        
        pecho( "Getting list of all categories...\n\n", PECHO_NORMAL );
        
        return $this->listHandler( $leArray );
    
    }
	
	/**
	 * Enumerate all images sequentially
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Lists#allimages_.2F_le
	 * @param string $prefix Search for all image titles that begin with this value. (default: null)
	 * @param string $sha1 SHA1 hash of image (default: null)
	 * @param string $base36 SHA1 hash of image in base 36 (default: null)
	 * @param string $from The image title to start enumerating from. (default: null)
	 * @param string $minsize Limit to images with at least this many bytes (default: null)
	 * @param string $maxsize Limit to images with at most this many bytes (default: null)
	 * @param string $dir Direction in which to list (default: 'ascending')
	 * @param array $prop Information to retieve (default: array( 'timestamp', 'user', 'comment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' ))
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array List of images
	 */
	public function allimages( $prefix = null, $sha1 = null, $base36 = null, $from = null, $minsize = null, $maxsize = null, $dir = 'ascending', $prop = array( 'timestamp', 'user', 'comment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' ), $limit = 50 ) {
		$leArray = array(
			'list' => 'allimages',
			'_code' => 'ai',
			'aidir' => $dir,
			'aiprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		if( !is_null( $from ) ) $leArray['aifrom'] = $from;
		if( !is_null( $prefix ) ) $leArray['aiprefix'] = $prefix;
		if( !is_null( $minsize ) ) $leArray['aiminsize'] = $minsize;
		if( !is_null( $maxsize ) ) $leArray['aimaxsize'] = $maxsize;
		if( !is_null( $sha1 ) ) $leArray['aisha1'] = $sha1;
		if( !is_null( $base36 ) ) $leArray['aisha1base36'] = $base36;
		
		Hooks::runHook( 'PreQueryAllimages', array( &$leArray ) );
		
		pecho( "Getting list of all images...\n\n", PECHO_NORMAL );
		
		return $this->listHandler( $leArray );
	
	}
	
	/**
	 * Enumerate all pages sequentially
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Lists#allpages_.2F_le
	 * @param array $namespace The namespace to enumerate. (default: array( 0 ))
	 * @param string $prefix Search for all page titles that begin with this value. (default: null)
	 * @param string $from The page title to start enumerating from. (default: null)
	 * @param string $redirects Which pages to list: all, redirects, or nonredirects (default: all)
	 * @param string $minsize Limit to pages with at least this many bytes (default: null)
	 * @param string $maxsize Limit to pages with at most this many bytes (default: null)
	 * @param array $protectiontypes Limit to protected pages. Examples: array( 'edit' ), array( 'move' ), array( 'edit', 'move' ). (default: array())
	 * @param array $protectionlevels Limit to protected pages. Examples: array( 'autoconfirmed' ), array( 'sysop' ), array( 'autoconfirmed', 'sysop' ). (default: array())
	 * @param string $dir Direction in which to list (default: 'ascending')
	 * @param string $interwiki Filter based on whether a page has langlinks (either withlanglinks, withoutlanglinks, or all (default))
	 * @param int $limit How many results to retrieve (default: null i.e. all)
	 * @return array List of pages
	 */
	public function allpages( $namespace = array( 0 ), $prefix = null, $from = null, $redirects = 'all', $minsize = null, $maxsize = null, $protectiontypes = array(), $protectionlevels = array(), $dir = 'ascending', $interwiki = 'all', $limit = 50 ) {
		$leArray = array(
			'list' => 'allpages',
			'_code' => 'ap',
			'apdir' => $dir,
			'apnamespace' => $namespace,
			'apfilterredir' => $redirects,
			'apfilterlanglinks' => $interwiki,
			'_limit' => $limit
		);
		
		if( count( $protectiontypes ) ) {
			// Trying to filter by protection status
			$leArray['apprtype'] = implode( '|', $protectiontypes );
			if( count( $protectionlevels ) ) $leArray['apprlevel'] = implode( '|', $protectionlevels );
		} elseif( count( $protectionlevels ) ) {
			pecho( 'If $protectionlevels is specified, $protectiontypes must also be specified.', PECHO_FATAL );
			return false;
		}
		
		if( !is_null( $from ) ) $leArray['apfrom'] = $from;//
		if( !is_null( $prefix ) ) $leArray['apprefix'] = $prefix; //
		if( !is_null( $minsize ) ) $leArray['apminsize'] = $minsize; //
		if( !is_null( $maxsize ) ) $leArray['apmaxsize'] = $maxsize; // 
		
		Hooks::runHook( 'PreQueryAllpages', array( &$leArray ) );
		
		pecho( "Getting list of all pages...\n\n", PECHO_NORMAL );
		
		return $this->listHandler( $leArray );
	}
	
	/**
	 * Enumerate all internal links that point to a given namespace
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Lists#alllinks_.2F_le
	 * @param array $namespace The namespace to enumerate. (default: array( 0 ))
	 * @param string $prefix Search for all page titles that begin with this value. (default: null)
	 * @param string $from The page title to start enumerating from. (default: null)
	 * @param string $continue When more results are available, use this to continue. (default: null)
	 * @param bool $unique Set to true in order to only show unique links (default: true)
	 * @param array $prop What pieces of information to include: ids and/or title. (default: array( 'ids', 'title' ))
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array List of links
	 */
	public function alllinks( $namespace = array( 0 ), $prefix = null, $from = null, $continue = null, $unique = false, $prop = array( 'ids', 'title' ), $limit = 50 ) {
		$leArray = array(
			'list' => 'alllinks',
			'_code' => 'al',
			'alnamespace' => $namespace,
			'alprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		if( !is_null( $from ) ) $leArray['alfrom'] = $from;
		if( !is_null( $prefix ) ) $leArray['alprefix'] = $prefix;
		if( !is_null( $continue ) ) $leArray['alcontinue'] = $continue;
		if( $unique ) $leArray['alunique'] = 'yes';
		$leArray['limit'] = $this->apiQueryLimit;
		
		Hooks::runHook( 'PreQueryAlllinks', array( &$leArray ) );
		
		pecho( "Getting list of all internal links...\n\n", PECHO_NORMAL );
		
		return $this->listHandler( $leArray );
	}
	
	/**
	 * Enumerate all registered users
	 * 
	 * @access public
	 * @link http://www.mediawiki.org/wiki/API:Query_-_Lists#alllinks_.2F_le
	 * @param string $prefix Search for all usernames that begin with this value. (default: null)
	 * @param array $groups Limit users to a given group name (default: array())
	 * @param string $from The username to start enumerating from. (default: null)
	 * @param bool $editsonly Set to true in order to only show users with edits (default: false)
	 * @param array $prop What pieces of information to include (default: array( 'blockinfo', 'groups', 'editcount', 'registration' ))
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array List of users
	 */
	public function allusers( $prefix = null, $groups = array(), $from = null, $editsonly = false, $prop = array( 'blockinfo', 'groups', 'editcount', 'registration' ), $limit = 50 ) {
		$leArray = array(
			'list' => 'allusers',
			'_code' => 'au',
			'auprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		if( !is_null( $from ) ) $leArray['aufrom'] = $from;
		if( !is_null( $prefix ) ) $leArray['auprefix'] = $prefix;
		if( count( $groups ) ) $leArray['augroup'] = implode( '|', $groups );
		if( $editsonly ) $leArray['auwitheditsonly'] = 'yes';
		
		Hooks::runHook( 'PreQueryAllusers', array( &$leArray ) );
		
		pecho( "Getting list of all users...\n\n", PECHO_NORMAL );
		
		return $this->listHandler( $leArray );
	}
	
	public function listblocks() {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	/**
	 * Retrieves the titles of member pages of the given category
	 * 
	 * @access public
	 * @param string $category Category to retieve
	 * @param bool $subcat Should subcategories be checked (default: false)
	 * @param string|array $namespace Restrict results to the given namespace (default: null i.e. all)
	 * @param int $limit How many results to retrieve (default: null i.e. all)
	 * @return array Array of titles
	 */
	public function categorymembers( $category, $subcat = false, $namespace = null, $limit = 50) {
		$cmArray = array(
			'list' => 'categorymembers',
			'_code' => 'cm',
			'cmtitle' => $category,
			'cmtype' => 'page',
			'_limit' => $limit
		);
		
		$strip_categories = false;
		
		if( $subcat ) $cmArray['cmtype'] = 'page|subcat';
		
		Hooks::runHook( 'PreQueryCategorymembers', array( &$cmArray ) );
		
		pecho( "Getting list of pages in the $category category...\n\n", PECHO_NORMAL );
		
		$top_category = $this->listHandler( $cmArray );
		
		return $top_category;
		
	}
	
	/**
	 * Returns array of pages that embed (transclude) the page given.
	 * 
	 * @see Page::embeddedin()
	 * @access public
	 * @param string $title The title of the page being embedded.
	 * @param array $namespace Which namespaces to search (default: null).
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array A list of pages the title is transcluded in.
	 */
	public function embeddedin( $title, $namespace = null, $limit = 50 ) {
		Peachy::deprecatedWarn( 'Wiki::embeddedin()', 'Page::embeddedin()' );
		$page = $this->initPage( $title );
		return $page->embeddedin( $namespace, $limit );
	}
	
	/**
	 * List change tags enabled on the wiki.
	 *
	 * @access public
	 * @param array $prop Which properties to retrieve (default: array( 'name', 'displayname', 'description', 'hitcount' ) i.e. all).
	 * @param int $limit How many results to retrieve (default: null i.e. all).
	 * @return array The tags retrieved.
	 */
	public function tags( $prop = array( 'name', 'displayname', 'description', 'hitcount' ), $limit = 50 ) {
		$tgArray = array(
			'list' => 'tags',
			'_code' => 'tg',
			'tgprop' => implode( '|', $prop ),
			'_limit' => $limit
		);
		
		Hooks::runHook( 'PreQueryTags', array( &$tgArray ) );
		
		pecho( "Getting list of all tags...\n\n", PECHO_NORMAL );

		return $this->listHandler( $tgArray );
	}
	
	public function get_watchlist( $minor = null, $bot = null, $anon = null, $patrolled = null,$namespace = null, $user = null, $excludeuser = null, $start = null, $end = null, $prop = array( 'ids', 'title', 'flags', 'user', 'comment', 'parsedcomment', 'timestamp', 'patrol', 'sizes', 'notificationtimestamp' ), $limit = 50) {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	public function get_watchlistraw( $namespace = null, $changed = null ) {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	/** 
	 * Returns details of usage of an external URL on the wiki.
	 *
	 * @access public
	 * @param string $url The url to search for links to, without a protocol. * can be used as a wildcard.
	 * @param string $protocol The protocol to accompany the URL. Only certain values are allowed, depending on how $wgUrlProtocols is set on the wiki; by default the allowed values are 'http://', 'https://', 'ftp://', 'irc://', 'gopher://', 'telnet://', 'nntp://', 'worldwind://', 'mailto:' and 'news:'. Default 'http://'.
	 * @param array $prop Properties to return in array form; the options are 'ids', 'title' and 'url'. Default null (all).
	 * @param string $namespace A pipe '|' separated list of namespace numbers to check. Default null (all).
	 * @param int $limit A hard limit on the number of transclusions to fetch. Default null (all).
	 * @return array Details about the usage of that external link on the wiki.
	 */
	public function exturlusage( $url, $protocol = 'http', $prop = array( 'title' ), $namespace = null, $limit = 50 ) {
		$tArray = array(
			'list' => 'exturlusage',
			'_code' => 'eu',
			'euquery' => $url,
			'euprotocol' => $protocol,
			'_limit' => $limit,
			'euprop' => implode( '|', $prop )
		);
		
		if(!is_null($namespace)){
			$tArray['eunamespace'] = $namespace;
		}
		
		Hooks::runHook( 'PreQueryExturlusage', array( &$tArray ) );
		
		pecho( "Getting list of all pages that $url is used in...\n\n", PECHO_NORMAL );
		
		return $this->listHandler($tArray);

	}
	
	public function users( $users = array(), $prop = array( 'blockinfo', 'groups', 'editcount', 'registration', 'emailable', 'gender' ) ) {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	/**
	 * Returns the titles of some random pages.
	 * 
	 * @access public
	 * @param array|string $namespaces Namespaces to select from (default:  array( 0 ) ).
	 * @param int $limit The number of titles to return (default: 1).
	 * @param bool $onlyredirects Only include redirects (true) or only include non-redirects (default; false).
	 * @return array A series of random titles.
	 */
	public function random( $namespaces = array( 0 ), $limit = 1, $onlyredirects = false) {
		$rnArray = array(
			'_code' => 'rn',
			'list' => 'random',
			'rnnamespace' => $namespaces,
			'_limit' => $limit,
			'rnredirect' => (is_null($onlyredirects) || !$onlyredirects) ? null : "true",
			'_lhtitle' => 'title'
		);
		
		Hooks::runHook( 'PreQueryRandom', array( &$rnArray ) );
		
		pecho( "Getting random page...\n\n", PECHO_NORMAL );
		
		return $this->listHandler($rnArray);
	}
	
	public function protectedtitles( $namespace = array( 0 ) ) {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	/**
	 * Returns meta information about the wiki itself
	 * 
	 * @access public
	 * @param array $prop Information to retrieve. Default: array( 'general', 'namespaces', 'namespacealiases', 'specialpagealiases', 'magicwords', 'interwikimap', 'dbrepllag', 'statistics', 'usergroups', 'extensions', 'fileextensions', 'rightsinfo', 'languages' )
	 * @param bool $iwfilter When used with prop 'interwikimap', returns only local or only nonlocal entries of the interwiki map. True = local, false = nonlocal. Default null
	 * @return array
	 */
	public function siteinfo( $prop = array( 'general', 'namespaces', 'namespacealiases', 'specialpagealiases', 'magicwords', 'interwikimap', 'dbrepllag', 'statistics', 'usergroups', 'extensions', 'fileextensions', 'rightsinfo', 'languages' ), $iwfilter = null ) {
		
		$siArray = array(
			'action' => 'query',
			'meta' => 'siteinfo',
			'siprop' => implode( '|', $prop ),
		);
		
		if( in_array( 'interwikimap', $prop ) && $iwfilter ) $siArray['sifilteriw'] = 'yes';
		elseif( in_array( 'interwikimap', $prop ) && $iwfilter ) $siArray['sifilteriw'] = 'no';
		
		if( in_array( 'dbrepllag', $prop ) ) $siArray['sishowalldb'] = 'yes';
		if( in_array( 'usergroups', $prop ) ) $siArray['sinumberingroup'] = 'yes';
		
		Hooks::runHook( 'PreQuerySiteInfo', array( &$siArray ) );
		
		pecho( "Getting site information...\n\n", PECHO_NORMAL );
		
		return $this->apiQuery($siArray);
	}
	
	/**
	 * Returns a list of system messages (MediaWiki:... pages)
	 * 
	 * @access public
	 * @param string $filter Return only messages that contain this string. Default null
	 * @param array $messages Which messages to output. Default array(), which means all.
	 * @param bool $parse Set to true to enable parser, will preprocess the wikitext of message. (substitutes magic words, handle templates etc.) Default false
	 * @param array $args Arguments to be substituted into message. Default array(). 
	 * @param string $lang Return messages in this language. Default null
	 * @return array
	 */
	public function allmessages( $filter = null, $messages = array(), $parse = false, $args = array(), $lang = null ) {
		$amArray = array(
			'action' => 'query',
			'meta' => 'allmessages',
		);
		
		if( !is_null( $filter ) ) $amArray['amfilter'] = $filter;
		if( count( $messages ) ) $amArray['ammessages'] = implode( '|', $messages );
		if( $parse ) $amArray['amenableparser'] = 'yes';
		if( count( $args ) ) $amArray['amargs'] = implode( '|', $args );
		if( !is_null( $lang ) ) $amArray['amlang'] = $lang;
		
		Hooks::runHook( 'PreQueryAllMessages', array( &$amArray ) );
		
		pecho( "Getting list of system messages...\n\n", PECHO_NORMAL );
		
		return $this->apiQuery($amArray);
	}
	
	/**
	 * Expand and parse all templates in wikitext
	 * 
	 * @access public
	 * @param string $text Text to parse
	 * @param string $title Title to use for expanding magic words, etc. (e.g. {{PAGENAME}}). Default 'API'.
	 * @param bool $generatexml Generate XML parse tree. Default false
	 * @return string
	 */
	public function expandtemplates( $text, $title = null, $generatexml = false ) {
		$etArray = array(
			'action' => 'expandtemplates',
			'text' => $text
		);
		
		if( $generatexml ) $etArray['generatexml'] = 'yes';
		if( !is_null( $title ) ) $etArray['title'] = $title;
		
		Hooks::runHook( 'PreQueryExpandtemplates', array( &$etArray ) );
		
		pecho( "Parsing templates...\n\n", PECHO_NORMAL );
		
		$ret = $this->apiQuery($etArray);
		return $ret['expandtemplates']['*'];
		
	}
	
	/**
	 * Parses wikitext and returns parser output
	 * 
	 * @access public
	 * @param string $text Wikitext to parse. Default null.
	 * @param string $title Title of page the text belongs to, used for {{PAGENAME}}. Default null.
	 * @param string $summary Summary to parse. Default null.
	 * @param bool $pst Run a pre-save transform, expanding {{subst:}} and ~~~~. Default false.
	 * @param bool $onlypst Run a pre-save transform, but don't parse it. Default false.
	 * @param string $uselang Language to parse in. Default 'en'.
	 * @param array $prop Properties to retrieve. Default array( 'text', 'langlinks', 'categories', 'links', 'templates', 'images', 'externallinks', 'sections', 'revid', 'displaytitle', 'headitems', 'headhtml' )
	 * @param string $page Parse the content of this page. Cannot be used together with $text and $title.
	 * @param string $oldid Parse the content of this revision. Overrides $page and $pageid.
	 * @param string $pageid Parse the content of this page. Overrides page.
	 * @param bool $redirect If the page or the pageid parameter is set to a redirect, resolve it. Default true.
	 * @param string $section Only retrieve the content of this section number. Default null.
	 * @param bool $disablepp Disable the PP Report from the parser output. Defaut false.
	 * @param bool $generatexml Generate XML parse tree (requires prop=wikitext). Default false.
	 * @param string $contentformat Content serialization format used for the input text. Default null.
     * @param string $contentmodel Content model of the new content. Default null.
	 * @param string $mobileformat Return parse output in a format suitable for mobile devices. Default null.
	 * @param bool $noimages Disable images in mobile output
	 * @param bool $mainpage Apply mobile main page transformations
	 * @return array
	 */
	public function parse( $text = null, $title = null, $summary = null, $pst = false, $onlypst = false, $prop = array( 'text', 'langlinks', 'categories', 'categorieshtml', 'languageshtml', 'links', 'templates', 'images', 'externallinks', 'sections', 'revid', 'displaytitle', 'headitems', 'headhtml', 'iwlinks', 'wikitext', 'properties' ), $uselang = 'en', $page = null, $oldid = null, $pageid = null, $redirects = false, $section = null, $disablepp = false, $generatexml = false, $contentformat = null, $contentmodel = null, $mobileformat = null, $noimages = false, $mainpage = false ) {
		
		if( $generatexml ) {
			if( !in_array( 'wikitext', $prop ) ) $prop[] = 'wikitext';
			$apiArray['generatexml'] = 'yes';
		}
		
		$apiArray = array(
			'action' => 'parse',
			'uselang' => $uselang,
			'prop' => implode( '|', $prop ),
		);
		
		if( !is_null( $text ) ) $apiArray['text'] = $text;
		if( !is_null( $title ) ) $apiArray['title'] = $title;
		if( !is_null( $summary ) ) $apiArray['summary'] = $summary;
		if( !is_null( $pageid ) ) $apiArray['pageid'] = $pageid;
		if( !is_null( $page ) ) $apiArray['page'] = $page;
		if( !is_null( $oldid ) ) $apiArray['oldid'] = $oldid;
		if( !is_null( $section ) ) $apiArray['section'] = $section;
		if( !is_null( $contentformat ) ) {
			if ( $contentformat == 'text/x-wiki' || $contentformat == 'text/javascript' || $contentformat == 'text/css' || $contentformat == 'text/plain' ) $apiArray['contentformat'] = $contentformat;
			else pecho( "Error: contentformat not specified correctly.  Omitting value...\n\n", PECHO_ERROR );
		}
		if( !is_null( $contentmodel ) ) {
			if ( $contentmodel == 'wikitext' || $contentmodel == 'javascript' || $contentmodel == 'css' || $contentmodel == 'text' || $contentmodel == 'Scribunto' ) $apiArray['contentmodel'] = $contentmodel;
			else pecho( "Error: contentmodel not specified correctly.  Omitting value...\n\n", PECHO_ERROR );
		}
		if( !is_null( $mobileformat ) ) {
			if ( $mobileformat == 'wml' || $mobileformat == 'html' ) $apiArray['mobileformat'] = $mobileformat;
			else pecho( "Error: mobileformat not specified correctly.  Omitting value...\n\n", PECHO_ERROR );
		}
		
		if( $pst ) $apiArray['pst'] = 'yes';
		if( $onlypst ) $apiArray['onlypst'] = 'yes';
		if( $redirect ) $apiArray['redirects'] = 'yes';
		if( $disablepp ) $apiArray['disablepp'] = 'yes';
		if( $noimages ) $apiArray['noimages'] = 'yes';
		if( $mainpage ) $apiArray['mainpage'] = 'yes';
		
		Hooks::runHook( 'PreParse', array( &$etArray ) );
		
		pecho( "Parsing...\n\n", PECHO_NORMAL );
		
		return $this->apiQuery($apiArray);
	}
	
	/**
	 * Patrols a page or revision
	 * 
	 * @access public
	 * @param int $rcid Recent changes ID to patrol
	 * @return array
	 */
	public function patrol( $rcid = 0 ) {
		Hooks::runHook( 'PrePatrol', array( &$rcid ) );
		
		pecho( "Patrolling $rcid...\n\n", PECHO_NORMAL );
		
		$this->get_tokens();
		
		return $this->apiQuery( array(
			'action' => 'patrol',
			'rcid' => $rcid,
			'token' => $this->tokens['patrol']
		));
	}
	/**
	 * Import a page from another wiki, or an XML file.
	 *
	 * @access public
	 * @param mixed|string $page local XML file or page to another wiki.
	 * @param string $summary Import summary. Default ''.
	 * @param string $site For interwiki imports: wiki to import from.  Default null.
	 * @param bool $fullhistory For interwiki imports: import the full history, not just the current version.  Default true.
	 * @param bool $templates For interwiki imports: import all included templates as well.  Default true.
	 * @param int $namespace For interwiki imports: import to this namespace
	 * @param bool $root Import as subpage of this page.  Default false.
	 * @return bool
	 */
	public function import( $page = null, $summary = '', $site = null, $fullhistory = true, $templates = true, $namespace = null, $root = false ) {
		
		$tokens = $this->get_tokens();
		
		$apiArray = array(
			'action' => 'import',
			'summary' => $summary,
			'token' => $tokens['import']
		);
		
		if( $root ) $apiArray['rootpage'] = 'yes';
		
		if( !is_null( $page ) ) {
			if( !is_file( $page ) ) {
				$apiArray['interwikipage'] = $page;
				if( !is_null( $site ) ) $apiArray['interwikisource'] = $site;
				else {
					pecho( "Error: You must specify a site to import from.\n\n", PECHO_FATAL );
					return false;
				}
				if( !is_null( $namespace ) ) $apiArray['namespace'] = $namespace;
				if( $fullhistory ) $apiArray['fullhistory'] = 'yes';
				if( $templates ) $apiArray['templates'] = 'yes';
				pecho( "Importing $page from $site...\n\n", PECHO_NOTICE );
			} else {
				$apiArray['xml'] = "@$page";
				pecho( "Uploading XML...\n\n", PECHO_NOTICE );
			}
		} else {
			pecho( "Error: You must specify an interwiki page or a local XML file to import.\n\n", PECHO_FATAL );
			return false;
		}
		
		$result = $this->apiQuery( $apiArray, true );
		
		if( isset( $result['import'] ) ) {
			if( isset( $result['import']['page'] ) ) {
				return true;
			}
			else {
				pecho( "Import error...\n\n" . print_r($result['import'], true) . "\n\n", PECHO_FATAL );
				return false;
			}
		}
		else {
			pecho( "Import error...\n\n" . print_r($result, true), PECHO_FATAL );
			return false;
		}
					
	}
	
	public function export() {
		pecho( "Error: " . __METHOD__ . " has not been programmed as of yet.\n\n", PECHO_ERROR );
	}
	
	
	/**
	 * Generate a diff between two or three revision IDs
	 * 
	 * @access public
	 * @param string $method Revision method. Options: unified, inline, context, threeway, raw (default: 'unified')
	 * @param mixed $rev1
	 * @param mixed $rev2
	 * @param mixed $rev3
	 * @return void
	 * @see Diff::load
	 */
	public function diff( $method = 'unified', $rev1, $rev2, $rev3 = null ) {
		$r1array = array(
			'action' => 'query',
			'prop' => 'revisions',
			'revids' => $rev1,
			'rvprop' => 'content'
		);
		$r2array = array(
			'action' => 'query',
			'prop' => 'revisions',
			'revids' => $rev2,
			'rvprop' => 'content'
		);
		$r3array = array(
			'action' => 'query',
			'prop' => 'revisions',
			'revids' => $rev3,
			'rvprop' => 'content'
		);
		
		Hooks::runHook( 'PreDiff', array( &$r1array, &$r2array, &$r3array, &$method ) );
		
		if( !is_null( $rev3 ) ) {
			pecho( "Getting $method diff of revisions $rev1, $rev2, and $rev3...\n\n", PECHO_NORMAL );
			$r3 = $this->apiQuery( $r3array );
			
			
			if( isset( $r3['query']['badrevids'] ) ) {
				pecho( "A bad third revision ID was passed.\n\n", PECHO_FATAL );
				return false;
			}
		
			foreach( $r3['query']['pages'] as $r3pages ) {
				$r3text = $r3pages['revisions'][0]['*'];
			}
		}
		else {
			pecho( "Getting $method diff of revisions $rev1 and $rev2...\n\n", PECHO_NORMAL );
			$r3text = null;
		}
		
		$r1 = $this->apiQuery( $r1array );
		$r2 = $this->apiQuery( $r2array );
		
		
		if( isset( $r1['query']['badrevids'] ) ) {
			pecho( "A bad first revision ID was passed.\n\n", PECHO_FATAL );
			return false;
		}
		elseif( isset( $r2['query']['badrevids'] ) ) {
			pecho( "A bad second revision ID was passed.\n\n", PECHO_FATAL );
			return false;
		}
		else {
			foreach( $r1['query']['pages'] as $r1pages ) {
				$r1text = $r1pages['revisions'][0]['*'];
			}
			foreach( $r2['query']['pages'] as $r2pages ) {
				$r2text = $r2pages['revisions'][0]['*'];
			}
			
			if( $method == "raw" ) return array( $r1text, $r2text, $r3text );
			return Diff::load($method, $r1text, $r2text, $r3text);
		}
		
		
	}
	
	/**
	 * Regenerate and return edit tokens
	 * 
	 * @access public
	 * @param bool $force Whether to force use of the API, not cache.
	 * @return array Edit tokens
	 */
	public function get_tokens( $force = false ) {
		Hooks::runHook( 'GetTokens', array( &$this->tokens ) );
		
		if( $force ) return $this->tokens;

		$tokens = $this->apiQuery( array(
			'action' => 'tokens',
			'type' => 'block|delete|deleteglobalaccount|edit|email|import|move|options|patrol|protect|setglobalaccountstatus|unblock|watch'
		));	
		
		foreach( $tokens['tokens'] as $y => $z ) {
			if( in_string( 'token', $y ) ) {
				$this->tokens[str_replace('token','',$y)] = $z;
			}
		}
		
		return $this->tokens;
		
	}
	
	/**
	 * Returns extensions.
	 * 
	 * @access public
	 * @see Wiki::$extensions
	 * @return array Extensions in format name => version
	 */
	public function get_extensions() {
		return $this->extensions;
	}
	
	/**
	 * Returns an array of the namespaces used on the current wiki.
	 * 
	 * @access public
	 * @param bool $force Whether or not to force an update of any cached values first.
	 * @return array The namespaces in use in the format index => local name. 
	 */
	public function get_namespaces( $force = false ) {
		if( is_null( $this->namespaces ) || $force ) {
			$tArray = array(
				'meta' => 'siteinfo',
				'action' => 'query',
				'siprop' => 'namespaces'
			);
			$tRes = $this->apiQuery( $tArray );
			
			foreach($tRes['query']['namespaces'] as $namespace){
				$this->namespaces[$namespace['id']] = $namespace['*'];
				$this->allowSubpages[$namespace['id']] = ((isset($namespace['subpages'])) ? true : false);
			}
		}
		return $this->namespaces;
	}
	
	/**
	 * Removes the namespace from a title
	 * 
	 * @access public
	 * @param string $title Title to remove namespace from
	 * @return string
	 */
	
	public function removeNamespace( $title ) {
		$this->get_namespaces();
		
		$exploded = explode( ':', $title, 2 );
		
		foreach( $this->namespaces as $namespace ) {
			if( $namespace == $exploded[0] ) {
				return $exploded[1];
			}
		}
		
		return $title;
	}

	/**
	 * Returns an array of subpage-allowing namespaces.
	 * 
	 * @access public
	 * @param bool $force Whether or not to force an update of any cached values first.
	 * @return array An array of namespaces that allow subpages.
	 */	
	public function get_allow_subpages( $force = false ) {
		if( is_null( $this->allowSubpages ) || $force ) {
			$this->get_namespaces( true );
		}
		return $this->allowSubpages;
	}
	
	/**
	 * Returns a boolean equal to whether or not the namespace with index given allows subpages.
	 * 
	 * @access public
	 * @param int $namespace The namespace that might allow subpages.
	 * @return bool Whether or not that namespace allows subpages.
	 */
	public function get_ns_allows_subpages( $namespace = 0 ) {
		$this->get_allow_subpages();
		
		return (bool) $this->allowSubpages[$namespace];
	}
	
	/**
	 * Returns user rights.
	 * 
	 * @access public
	 * @see Wiki::$userRights
	 * @return array Array of user rights
	 */					
	public function get_userrights() {
		return $this->userRights;
	}
	
	/**
	 * Returns all the pages which start with a certain prefix, shortcut for {@link Wiki}::{@link allpages}()
	 * 
	 * @param string $prefix Prefix to search for
	 * @param array $namespace Namespace IDs to search in. Default array( 0 )
	 * @param int $limit A hard limit on the number of pages to fetch. Default null (all). 
	 * @return array Titles of pages that transclude this page
	 */
	public function prefixindex( $prefix = null, $namespace = array( 0 ), $limit = 50 ) {
		return $this->allpages( $namespace, $prefix, null, 'all', null, null, array(), array(), 'ascending', 'all', $limit );
	}
	
	/**
	 * Returns an instance of the Page class as specified by $title or $pageid
	 * 
	 * @access public
	 * @param mixed $title Title of the page (default: null)
	 * @param mixed $pageid ID of the page (default: null)
	 * @param bool $followRedir Should it follow a redirect when retrieving the page (default: true)
	 * @param bool $normalize Should the class automatically normalize the title (default: true)
     * @param string $timestamp Timestamp of a reference point in the program.  Used to detect edit conflicts.
	 * @return Page
	 * @package initFunctions
	 */
	public function &initPage( $title = null, $pageid = null, $followRedir = true, $normalize = true, $timestamp = null ) {
		$page = new Page( $this, $title, $pageid, $followRedir, $normalize, $timestamp );
		return $page;
	}
	
	/**
	 * Returns an instance of the User class as specified by $username
	 * 
	 * @access public
	 * @param mixed $username Username
	 * @return User
	 * @package initFunctions
	 */
	public function &initUser( $username ) {
		$user = new User( $this, $username );
		return $user;
	}
	
	/**
	 * Returns an instance of the Image class as specified by $filename or $pageid
	 * 
	 * @access public
	 * @param string $filename Filename
	 * @param int $pageid Page ID of image
	 * @param array $prop Informatation to set. Default array( 'timestamp', 'user', 'comment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' )
	 * @return Image
	 * @package initFunctions
	 */
	public function &initImage( $filename = null, $pageid = null, $prop = array( 'timestamp', 'user', 'comment', 'url', 'size', 'dimensions', 'sha1', 'mime', 'metadata', 'archivename', 'bitdepth' ) ) {
		$image = new Image( $this, $filename, $pageid, $prop );
		return $image;
	}

	/**
	 * Get the difference between 2 pages
	 *
	 * @access public
	 * @param string $fromtitle First title to compare
	 * @param string $fromid First page ID to compare
	 * @param string $fromrev First revision to compare
	 * @param string $totitle Second title to compare
	 * @param string $toid Second page ID to compare
	 * @param string $torev Second revision to compare
	 * @return array
	 */
	public function compare( $fromtitle = null, $fromid = null, $fromrev = null, $totitle = null, $toid = null, $torev = null ) {
	
		pecho( "Getting differences...\n\n", PECHO_NORMAL );
		$apiArray = array(
			'action' => 'compare'
		);
		if( !is_null( $fromrev ) ) $apiArray['fromrev'] = $fromrev;
		else {
			if( !is_null( $fromid ) ) $apiArray['fromid'] = $fromid;
			else {
				if( !is_null( $fromtitle ) ) $apiArray['fromtitle'] = $fromtitle;
				else {
					pecho( "Error: a from parameter must be specified.\n\n", PECHO_FATAL );
					return false;
				}
			}
		}
		if( !is_null( $torev ) ) $apiArray['torev'] = $torev;
		else {
			if( !is_null( $toid ) ) $apiArray['toid'] = $toid;
			else {
				if( !is_null( $totitle ) ) $apiArray['totitle'] = $totitle;
				else {
					pecho( "Error: a to parameter must be specified.\n\n", PECHO_FATAL );
					return false;
				}
			}
		}	
		$results = $this->apiQuery( $apiArray );
		
		if( isset( $results['compare']['*'] ) ) return $results['compare']['*'];
		else {
			pecho( "Compare failure... Please check your parameters.\n\n", PECHO_FATAL );
			return false;
		}
	}
    
    /*
     * Search the wiki using the OpenSearch protocol.
     *
     * @access public
     * @param string $text Search string.  Default empty.
     * @param int $limit Maximum amount of results to return. Default 10.
     * @param array $namespaces Namespaces to search.  Default array(0).
     * @param bool $suggest Do nothing if $wgEnableOpenSearchSuggest is false. Default true.
     * @return array
     */
    public function opensearch( $text = '', $limit = 10, $namespaces = array(0), $suggest = true ) {
        
        $apiArray = array(
            'search' => $text,
            'action' => 'opensearch',
            'limit' => $limit,
            'namespace' => implode( '|', $namespaces )
        );
        if( $suggest ) $apiArray['suggest'] = 'yes';
        
        $OSres = $this->get_http()->get( $this->get_base_url(), $apiArray );        
        return json_decode( $OSres, true );

    }
    
    /*
     * Export an RSD (Really Simple Discovery) schema.
     *
     * @access public 
     * @return array
     */
    public function rsd() {
        
        $apiArray = array(
            'action' => 'rsd'
        );
        
        $OSres = $this->get_http()->get( $this->get_base_url(), $apiArray );        
        return $this->parsexml( $OSres );
    }
    
    /*
     * Parse an XML string into an array.  For more features, use the XML plugins.
     *
     * @access public
     * @param object $xml The XML blob to be parsed.
     * @param bool $recurse Part of the parsing parameters.  Leave false.
     * @param int $level Part of the parsing parameters.  Leave set to 1. 
     * @return array
     */
    public function parsexml( $xml, $recurse = false, $level = 1 ) {
        if( !$recurse ) {
            $parser = xml_parser_create();
            xml_parse_into_struct( $parser, $xml, $values );
            xml_parser_free( $parser ); 
        } else $values = $xml;
        $endarray = array();
        foreach( $values as $id=>$value ) {
            if( $value['type'] == 'open' ) {
                unset( $values[$id] );
                if( $value['level'] == $level ) {
                    if( !isset( $endarray[$value['tag']] ) ) $endarray[$value['tag']] = $this->parsexml( $values, true, $value['level'] + 1 );
                    else {
                        if( is_array($endarray[$value['tag']]) ) $endarray[$value['tag']][] = $this->parsexml( $values, true, $value['level'] + 1 );
                        else {
                            $endarray[$value['tag']] = array( $endarray[$value['tag']] );
                            $endarray[$value['tag']][] = $this->parsexml( $values, true, $value['level'] + 1 );
                        }  
                    }
                }
            } elseif( $value['type'] == 'complete' ) {
                if( $level == $value['level'] ) $endarray[$value['tag']] = $value['value'];
                unset( $values[$id] );
            } else {
                if( $value['level'] == $level - 1 ) return $endarray;
                unset( $values[$id] );
            }
        }
        return $endarray;
    }
    
    /*
     * Change preferences of the current user.
     *
     * @access public
     * @param bool $reset Resets preferences to the site defaults. Default false.
     * @param array|string $resetoptions List of types of options to reset when the "reset" option is set. Default 'all'.
     * @param array|string $changeoptions PartList of changes, formatted name=value (e.g. skin=vector), value cannot contain pipe characters. If no value is given (not even an equals sign), e.g., optionname|otheroption|..., the option will be reset to its default value. Default empty.
     * @param string $optionname A name of a option which should have an optionvalue set. Default null.
     * @param string $optionvalue A value of the option specified by the optionname, can contain pipe characters. Default null.
     * @return array
     */
    public function options( $reset = false, $resetoptions = array( 'all' ), $changeoptions = array(), $optionname = null, $optionvalue = null ) {
        $this->get_tokens();
        $apiArray = array(
            'action' => 'options',
            'token' => $this->tokens['options']
        );
        
        if( $reset ) {
            $apiArray['reset'] = 'yes';
            $apiArray['resetkinds'] = implode( '|', $resetoptions );
        }
        
        if( !empty( $changeoptions ) ) $apiArray['change'] = implode( '|', $changeoptions );
        
        if( !is_null( $optionname ) ) $apiArray['optionname'] = $optionname;
        if( !is_null( $optionvalue ) ) $apiArray['optionvalue'] = $optionvalue;
        
        $result = $this->apiQuery( $apiArray, true );
        if( isset( $result['options'] ) && $result['options'] == 'success' ) {
            if( isset( $result['warnings'] ) ) pecho( "Options set successfully, however a warning was thrown:\n". print_r( $result['warnings'], true ), PECHO_WARN );
            return true;
        }
        else {
            pecho( "Options error...\n\n" . print_r($result, true), PECHO_FATAL );
            return false;
        }
    }	
}
