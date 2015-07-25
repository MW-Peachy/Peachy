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
 * User object
 */

/**
 * User class, stores methods that relate to a specific user
 */
class User {

	/**
	 * Wiki class
	 *
	 * @var Wiki
	 * @access protected
	 */
	protected $wiki;

	/**
	 * Username
	 *
	 * @var string
	 * @access protected
	 */
	protected $username;

	/**
	 * Whether or not user exists
	 *
	 * @var bool
	 * @access protected
	 */
	protected $exists = true;

	/**
	 * Whether or not user is blocked
	 *
	 * @var bool
	 * @access protected
	 */
	protected $blocked;

	/**
	 * Array of block parameters
	 *
	 * (default value: array())
	 *
	 * @var array
	 * @access protected
	 */
	protected $blockinfo = array();

	/**
	 * Rough estimate as to number of edits
	 *
	 * @var int
	 * @access protected
	 */
	protected $editcount;

	/**
	 * List of groups user is a member of
	 *
	 * @var array
	 * @access protected
	 */
	protected $groups;

	/**
	 * Whether or not user is an IP
	 *
	 * @var bool
	 * @access protected
	 */
	protected $ip = false;

	/**
	 * Whether or not user has email enabled
	 *
	 * @var bool
	 * @access protected
	 */
	protected $hasemail = false;

	/**
	 * Date the user registered
	 *
	 * @var string
	 * @access protected
	 */
	protected $registration;

    /**
     * Construction method for the User class
     *
     * @access public
     * @param Wiki $wikiClass
     * @param mixed $pgUsername Username
     * @throws AssertFailure
     * @throws LoggedOut
     * @throws MWAPIError
     *
     * @todo    All return possibilities not accounted for
     */
	public function __construct( Wiki &$wikiClass, $pgUsername ) {

		$this->wiki = & $wikiClass;

		pecho( "Getting user information for $pgUsername...\n\n", PECHO_NORMAL );

		$uiRes = $this->wiki->apiQuery(
			array(
				'action'  => 'query',
				'list'    => 'users|logevents',
				'ususers' => $pgUsername,
				'letype'  => 'block',
				'letitle' => "User:" . $pgUsername,
				'lelimit' => 1,
				'usprop'  => 'editcount|groups|blockinfo|emailable|registration'
			)
		);

		if( !$uiRes ) {
			$this->username = $pgUsername;
			$this->exists = false;
		} else {
			$this->exists = true;
		}

		$this->username = $uiRes['query']['users'][0]['name'];

		if( long2ip( ip2long( $this->username ) ) == $this->username ) {
			$this->exists = false;
			$this->ip = true;

			if( isset( $uiRes['query']['logevents'][0]['block']['expiry'] ) && strtotime( $uiRes['query']['logevents'][0]['block']['expiry'] ) > time() ) {
				$this->blocked = true;
				$this->blockinfo = array(
					'by'     => $uiRes['query']['logevents'][0]['user'],
					'when'   => $uiRes['query']['logevents'][0]['timestamp'],
					'reason' => $uiRes['query']['logevents'][0]['comment'],
					'expiry' => $uiRes['query']['logevents'][0]['block']['expiry']
				);
			} else {
				$this->blocked = false;
				$this->blockinfo = array();
			}
		} elseif( isset( $uiRes['query']['users'][0]['missing'] ) || isset( $uiRes['query']['users'][0]['invalid'] ) ) {
			$this->exists = false;
			return false;
		} else {
			$this->editcount = $uiRes['query']['users'][0]['editcount'];

			if( isset( $uiRes['query']['users'][0]['groups'] ) ) {
				$this->groups = $uiRes['query']['users'][0]['groups'];
			}

			if( isset( $uiRes['query']['users'][0]['blockedby'] ) ) {
				$this->blocked = true;
				$this->blockinfo = array(
					'by'     => $uiRes['query']['users'][0]['blockedby'],
					'when'   => $uiRes['query']['logevents'][0]['timestamp'],
					'reason' => $uiRes['query']['users'][0]['blockreason'],
					'expiry' => $uiRes['query']['users'][0]['blockexpiry']
				);
			} else {
				$this->blocked = false;
				$this->blockinfo = array();
			}


			if( isset( $uiRes['query']['users'][0]['emailable'] ) ) {
				$this->hasemail = true;
			}

			if( isset( $uiRes['query']['users'][0]['registration'] ) ) {
				$this->registration = $uiRes['query']['users'][0]['registration'];
			}
		}
	}

	/**
	 * Creates the account with the specified parameters
	 *
	 * @access public
	 * @param string $password Password (ignored if mailpassword is set). Default null.
	 * @param string $email Email address of user (optional). Default null.
	 * @param bool $mailpassword If set to true, a random password will be emailed to the user. Default false.
	 * @param string $reason Optional reason for creating the account to be put in the logs. Default null.
	 * @param string $realname Real name of user (optional). Default null.
	 * @param string $language Language code to set as default for the user (optional, defaults to content language). Default null.
	 * @param string $domain Domain for external authentication (optional). Default null.
	 * @return bool True on success, false otherwise
	 */
	public function create( $password = null, $email = null, $mailpassword = false, $reason = null, $realname = null, $language = null, $domain = null ) {
		global $pgNotag, $pgTag;
		pecho( "Creating user account " . $this->username . "...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Create" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$token = $this->wiki->apiQuery(
			array(
				'action' => 'createaccount',
				'name'   => $this->username
			), true
		);

		$token = $token['createaccount']['token'];

		$apiArray = array(
			'action' => 'createaccount',
			'token'  => $token,
			'name'   => $this->username
		);

		if( !$password == null ) $apiArray['password'] = $password;
		if( !$email == null ) $apiArray['email'] = $email;
		if( !$realname == null ) $apiArray['realname'] = $realname;
		if( !$domain == null ) $apiArray['domain'] = $domain;
		if( !$reason == null ) {
			if( !$pgNotag ) $reason .= $pgTag;
			$apiArray['reason'] = $reason;
		}
		if( !$language == null ) $apiArray['language'] = $language;

		if( $this->exists() ) {
			pecho( "Error: User account already exists.\n\n", PECHO_ERROR );
			return false;
		}
		if( $password == null && !$mailpassword ) {
			pecho( "Error: neither a password or the mailpassword have been set.\n\n", PECHO_ERROR );
			return false;
		}
		if( $mailpassword ) {
			if( $email == null ) {
				pecho( "Error: Email not specified.\n\n", PECHO_ERROR );
				return false;
			} else $apiArray['mailpassword'] = 'yes';
		} else {
			if( is_null( $password ) ) {
				pecho( "Error: No password specified.\n\n", PECHO_ERROR );
				return false;
			}
		}

		$result = $this->wiki->apiQuery( $apiArray, true );

		$this->__construct( $this->wiki, $this->username );

		if( isset( $result['createaccount']['result'] ) ) {
			if( $result['createaccount']['result'] == 'success' ) {
				return true;
			} else {
				pecho( "Create error...\n\n" . print_r( $result['createaccount'], true ), PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Create error...\n\n" . print_r( $result['createaccount'], true ), PECHO_FATAL );
			return false;
		}
	}

	/**
	 * Returns whether or not the user is blocked
	 *
	 * @access public
	 * @param bool $force Whether or not to use the locally stored cache. Default false.
	 * @return bool
	 */
	public function is_blocked( $force = false ) {

		if( !$force && $this->blocked !== null ) {
			return $this->blocked;
		}

		pecho( "Checking if {$this->username} is blocked...\n\n", PECHO_NORMAL );

		$this->__construct( $this->wiki, $this->username );

		return $this->blocked;
	}

	/**
	 * get_blockinfo function.
	 *
	 * @access public
	 * @return array
	 */
	public function get_blockinfo() {
		return $this->blockinfo;
	}

	/**
	 * is_ip function.
	 *
	 * @access public
	 * @return boolean
	 */
	public function is_ip() {
		return $this->ip;
	}

	/**
	 * Blocks the user
	 *
	 * @access public
	 * @param string $reason Reason for blocking. Default null
	 * @param string $expiry Expiry. Can be a date, {@link http://www.gnu.org/software/tar/manual/html_node/Date-input-formats.html GNU formatted date}, indefinite, or anything else that MediaWiki accepts. Default indefinite.
	 * @param array $params Parameters to set. Options are anononly, nocreate, autoblock, noemail, hidename, noallowusertalk. Defdault array().
	 * @param bool $watch Watch the user/IP's user and talk pages. Default false.
	 * @param int $range The number of CIDR prefix bits to use for a rangeblock. Default null.
	 * @return bool
	 */
	public function block( $reason = null, $expiry = 'indefinite', $params = array(), $watch = false, $range = null ) {
		global $pgNotag, $pgTag;
		$token = $this->wiki->get_tokens();
		$target = $this->username;

		if( !in_array( 'block', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to block users.\n\n", PECHO_FATAL );
			return false;
		}

		if( !$this->exists() && !$this->is_ip() ) {
			pecho( "User does not exist.\n\n", PECHO_FATAL );
			return false;
		}

		if( $range !== null ) {
			// intval() returns 0 or 1 on failure
			$range = intval( $range );
			if( !$this->is_ip() ) {
				pecho( "Can only combine a range with an IP address, not a username.\n\n", PECHO_WARN );
			}
			if( $range !== null && ( $range < 2 || $range > 32 ) ) {
				pecho( "Range must be an integer between 2 and 32 inclusive (more restrictive limits may also apply).\n\n", PECHO_WARN );
			}
			$target .= '/' . $range;
		}

		if( !array_key_exists( 'block', $token ) ) return false;

		$apiArr = array(
			'action'        => 'block',
			'user'          => $target,
			'token'         => $token['block'],
			'expiry'        => $expiry,
			'reblock'       => 'yes',
			'allowusertalk' => 'yes'
		);

		if( !is_null( $reason ) ) {
			if( !$pgNotag ) $reason .= $pgTag;
			$apiArr['reason'] = $reason;
		}

		foreach( $params as $param ){
			switch( $param ){
				case 'anononly':
					$apiArr['anononly'] = 'yes';
					break;
				case 'nocreate':
					$apiArr['nocreate'] = 'yes';
					break;
				case 'autoblock':
					$apiArr['autoblock'] = 'yes';
					break;
				case 'noemail':
					$apiArr['noemail'] = 'yes';
					break;
				case 'hidename':
					$apiArr['hidename'] = 'yes';
					break;
				case 'noallowusertalk':
					unset( $apiArr['allowusertalk'] );
					break;

			}
		}

		if( $watch ) $apiArr['watchuser'] = 'yes';

		Hooks::runHook( 'StartBlock', array( &$apiArr ) );

		pecho( "Blocking $target...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Block" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $apiArr, true );

		if( isset( $result['block'] ) ) {
			if( !isset( $result['error'] ) ) {
				$this->__construct( $this->wiki, $this->username );
				return true;
			} else {
				pecho( "Block error...\n\n" . print_r( $result['block'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Block error...\n\n" . print_r( $result, true ), PECHO_FATAL );
			return false;
		}

	}

	/**
	 * Unblocks the user, or a block ID
	 *
	 * @access public
	 * @param string $reason Reason for unblocking. Default null
	 * @param int $id Block ID to unblock. Default null
	 * @return bool
	 */
	public function unblock( $reason = null, $id = null ) {
		global $pgNotag, $pgTag;
		if( !in_array( 'block', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to unblock users", PECHO_FATAL );
			return false;
		}

		$token = $this->wiki->get_tokens();

		if( !array_key_exists( 'block', $token ) ) return false;

		$apiArr = array(
			'action' => 'unblock',
			'user'   => $this->username,
			'token'  => $token['unblock'],
		);

		if( !is_null( $id ) ) {
			$apiArr['id'] = $id;
			unset( $apiArr['user'] );
		}
		if( !is_null( $reason ) ) {
			if( !$pgNotag ) $reason .= $pgTag;
			$apiArr['reason'] = $reason;
		}

		Hooks::runHook( 'StartUnblock', array( &$apiArr ) );

		pecho( "Unblocking {$this->username}...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Unblock" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $apiArr, true );

		if( isset( $result['unblock'] ) ) {
			if( isset( $result['unblock']['user'] ) ) {
				$this->__construct( $this->wiki, $this->username );
				return true;
			} else {
				pecho( "Unblock error...\n\n" . print_r( $result['unblock'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Unblock error...\n\n" . print_r( $result, true ), PECHO_FATAL );
			return false;
		}
	}

	/**
	 * Returns the editcount of the user
	 *
	 * @access public
	 * @param bool $force Whether or not to use the locally stored cache. Default false.
	 * @param Database &$database Use an instance of the mysqli class to get a more accurate count
	 * @param bool $liveonly Whether or not to only get the live edit count. Only works with $database. Default false.
	 * @return int Edit count
	 */
	public function get_editcount( $force = false, &$database = null, $liveonly = false ) {
		global $pgUseLabs;
		//First check if $database exists, because that returns a more accurate count
		if( !is_null( $database ) && $database instanceOf mysqli ) {

			pecho( "Getting edit count for {$this->username} using the Database class...\n\n", PECHO_NORMAL );

			if( !$liveonly && $result = mysqli_query( $database, "SELECT COUNT(*) AS count FROM " . ( $pgUseLabs ? "archive_userindex" : "archive" ) . " WHERE `ar_user_text` = '{$this->username}';" ) ) {
                $res = mysqli_fetch_assoc( $result );
                $del_count = $res['count'];
                mysqli_free_result( $result );
                unset( $res );
            } else $del_count = 0;
            
            if( $result = mysqli_query( $database, "SELECT COUNT(*) AS count FROM " . ( $pgUseLabs ? "revision_userindex" : "revision" ) . " WHERE `rev_user_text` = '{$this->username}';" ) ) {
                $res = mysqli_fetch_assoc( $result );
                $live_count = $res['count'];
                mysqli_free_result( $result );
                unset( $res );
            } else $live_count = 0;          

			$this->editcount = $del_count + $live_count;
		} else {
			if( $force ) {
				$this->__construct( $this->wiki, $this->username );
			}
		}
		return $this->editcount;
	}

	/**
	 * Returns a list of all user contributions
	 *
	 * @access public
	 * @param bool $mostrecentfirst Set to true to get the most recent edits first. Default true.
	 * @param bool $limit Only get this many edits. Default null.
	 * @return array Array, first level indexed, second level associative with keys user, pageid, revid, ns, title, timestamp, size and comment (edit summary).
	 */
	public function get_contribs( $mostrecentfirst = true, $limit = null ) {
		if( !$this->exists ) return array();

		$ucArray = array(
			'_code'  => 'uc',
			'ucuser' => $this->username,
			'action' => 'query',
			'list'   => 'usercontribs',
			'_limit' => $limit,
		);

		if( $mostrecentfirst ) {
			$ucArray['ucdir'] = "older";
		} else {
			$ucArray['ucdir'] = "newer";
		}

		$result = $this->wiki->listHandler( $ucArray );

		pecho( "Getting list of contributions by {$this->username}...\n\n", PECHO_NORMAL );

		return $result;
	}

	/**
	 * Returns whether or not the user has email enabled
	 *
	 * @access public
	 * @return bool
	 */
	public function has_email() {
		return $this->hasemail;
	}

	/**
	 * Returns the usergroups, NULL if user is IP.
	 *
	 * @access public
	 * @param bool force Force use of the API.  Default false;
	 * @return array
	 */
	public function get_usergroups( $force = false ) {
		if( $force ) {

			$uiRes = $this->wiki->apiQuery(
				array(
					'action'  => 'query',
					'list'    => 'users',
					'ususers' => $this->username,
					'usprop'  => 'groups'
				)
			);

			$this->groups = $uiRes['query']['users'][0]['groups'];
		}
		return $this->groups;
	}

	/**
	 * Returns date the user registered
	 *
	 * @access public
	 * @return string
	 */
	public function get_registration() {
		return $this->registration;
	}

	/**
	 * Returns whether or not the user exists
	 *
	 * @access public
	 * @return bool
	 */
	public function exists() {
		return $this->exists;
	}

	/**
	 * Returns the raw username
	 *
	 * @access public
	 * @return string
	 */
	public function username() {
		return $this->username;
	}

	/**
	 * Send an email to another wiki user
	 *
	 * @access public
	 * @param string $text Text to send
	 * @param string $subject Subject of email. Default 'Wikipedia Email'
	 * @param bool $ccme Whether or not to send a copy of the email to "myself". Default false.
	 * @throws EmailError
	 * @return bool True on success, false otherwise.
	 */
	public function email( $text = null, $subject = "Wikipedia Email", $ccme = false ) {
		global $pgNotag;
		if( !$this->has_email() ) {
			pecho( "Cannot email {$this->username}, user has email disabled", PECHO_FATAL );
			return false;
		}

		$tokens = $this->wiki->get_tokens();
		if( !$pgNotag ) $text .= "\n\nPowered by Peachy " . PEACHYVERSION;
		$editarray = array(
			'action'  => 'emailuser',
			'target'  => $this->username,
			'token'   => $tokens['email'],
			'subject' => $subject,
			'text'    => $text
		);

		if( $ccme ) $editarray['ccme'] = 'yes';

		Hooks::runHook( 'StartEmail', array( &$editarray ) );

		pecho( "Emailing {$this->username}...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Email" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $editarray, true );

		if( isset( $result['error'] ) ) {
			throw new EmailError( $result['error']['code'], $result['error']['info'] );
		} elseif( isset( $result['emailuser'] ) ) {
			if( $result['emailuser']['result'] == "Success" ) {
				$this->__construct( $this->wiki, $this->username );
				return true;
			} else {
				pecho( "Email error...\n\n" . print_r( $result['emailuser'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Email error...\n\n" . print_r( $result['edit'], true ) . "\n\n", PECHO_FATAL );
			return false;
		}
	}

	public function userrights( $add = array(), $remove = array(), $reason = '' ) {
		global $pgNotag, $pgTag;

        $tokens = $this->wiki->get_tokens();
        
		if( !$pgNotag ) $reason .= $pgTag;
		$apiArr = array(
			'action' => 'userrights',
			'user'   => $this->username,
			'token'  => $tokens['userrights'],
			'add'    => implode( '|', $add ),
			'remove' => implode( '|', $remove ),
			'reason' => $reason
		);

		Hooks::runHook( 'StartUserrights', array( &$apiArr ) );

		pecho( "Assigning user rights to {$this->username}...\n\n", PECHO_NOTICE );

		try{
			$this->preEditChecks( "Rights" );
		} catch( EditError $e ){
			pecho( "Error: $e\n\n", PECHO_FATAL );
			return false;
		}

		$result = $this->wiki->apiQuery( $apiArr, true );

		if( isset( $result['userrights'] ) ) {
			if( isset( $result['userrights']['user'] ) ) {
				$this->__construct( $this->wiki, $this->username );
				return true;
			} else {
				pecho( "Userrights error...\n\n" . print_r( $result['userrights'], true ) . "\n\n", PECHO_FATAL );
				return false;
			}
		} else {
			pecho( "Userrights error...\n\n" . print_r( $result, true ), PECHO_FATAL );
			return false;
		}

	}

	/**
	 * List all deleted contributions.
	 * The logged in user must have the 'deletedhistory' right
	 *
	 * @access public
	 * @param bool $content Whether or not to return content of each contribution. Default false
	 * @param string $start Timestamp to start at. Default null.
	 * @param string $end Timestamp to end at. Default null.
	 * @param string $dir Direction to list. Default 'older'
	 * @param array $prop Information to retrieve. Default array( 'revid', 'user', 'parsedcomment', 'minor', 'len', 'content', 'token' )
	 * @return array
	 */
	public function deletedcontribs( $content = false, $start = null, $end = null, $dir = 'older', $prop = array(
		'revid', 'user', 'parsedcomment', 'minor', 'len', 'content', 'token'
	) ) {
		if( !in_array( 'deletedhistory', $this->wiki->get_userrights() ) ) {
			pecho( "User is not allowed to view deleted revisions", PECHO_FATAL );
			return false;
		}

		if( $content ) $prop[] = 'content';

		$drArray = array(
			'_code'  => 'dr',
			'list'   => 'deletedrevs',
			'druser' => $this->username,
			'drprop' => implode( '|', $prop ),
			'drdir'  => $dir
		);

		if( !is_null( $start ) ) $drArray['drstart'] = $start;
		if( !is_null( $end ) ) $drArray['drend'] = $end;

		Hooks::runHook( 'StartDelrevs', array( &$drArray ) );

		pecho( "Getting deleted revisions by {$this->username}...\n\n", PECHO_NORMAL );

		return $this->wiki->listHandler( $drArray );
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
	 * Returns a page class for the userpage
	 *
	 * @return Page
	 */
	public function &getPageclass() {
		$user_page = new Page( $this->wiki, "User:" . $this->username );
		return $user_page;
	}

}