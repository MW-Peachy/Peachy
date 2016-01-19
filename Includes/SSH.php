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
 * SSH object
 * Establishes and maintains a remote SSH connection
 */

/**
 * SSH Class, Establishes and maintains a remote SSH connection
 */
class SSH {

	/**
	 * Host of server
	 *
	 * @var string
	 * @access private
	 */
	private $host;

	/**
	 * Username of authenticated session
	 *
	 * @var string
	 * @access private
	 */
	private $username;

	/**
	 * File path to the private key file
	 *
	 * @var string
	 * @access private
	 */
    private $privateKey;

	/**
	 * SSH protocol being used
	 *
	 * @var int
	 * @access private
	 */
	private $protocol;

	/**
	 * Whether or not the connection was successful.
	 *
	 * @var bool
	 * @access private
	 */
	public $connected = false;

	/**
	 * The SSH connection object.
	 *
	 * @var Object
	 * @access private
	 */
    private $SSHObject;

	/**
	 * The SFTP connection object.
	 *
	 * @var Object
	 * @access private
	 */
    private $SFTPObject;

	/**
	 * Stores the commits of the PHPSecLib class
	 *
	 * @var array
	 * @access private
	 */
	protected $commits;

	/**
	 * HTTP class
	 *
	 * @var Object
	 * @access private
	 */
	protected $http;


	/**
	 * Construction method for the SSH class
	 *
	 * @FIXME: Codebase no longer includes SSH-related classes
	 *
     * @access      public
     * @param       string $pgHost Address of remote host.Default
     * @param       int $pgPort Default: 22
     * @param       string|null $pgUsername
     * @param       string|null $pgPassphrase
     * @param       string|null $pgPrivateKey
     * @param       int $pgProtocol
     * @param       int $pgTimeout
     * @param       HTTP $http This must be a HTTP class object from HTTP::getDefaultInstance()
     *
     * @see         HTTP::getDefaultInstance()
     * @see         WIKI::__construct()
	 */
    public function __construct(
        $pgHost,
        $pgPort = 22,
        $pgUsername = null,
        $pgPassphrase = null,
        $pgPrivateKey = null,
        $pgProtocol = 2,
        $pgTimeout = 10,
        HTTP $http
    ) {
		pecho( "Initializing SSH class...\n\n", PECHO_NORMAL );
		global $pgIP;
		$this->http = $http;
        // FIXME    The code base no longer includes a 'Includes/SSHCore' directory
        if (!file_exists($pgIP . 'Includes/SSHCore')) {
			pecho( "Setting up the SSH Class for first time use...\n\n", PECHO_NOTICE );
			$data = json_decode( $this->http->get( 'https://api.github.com/repos/phpseclib/phpseclib/branches/master', null, array(), false ), true );
			$this->commits = $data;
			$this->installPHPseclib();
		}

		//Check for updates
		if( !$this->CheckForUpdate() ) $this->installPHPseclib();

		set_include_path( get_include_path() . PATH_SEPARATOR . $pgIP . 'Includes/SSHCore' );
        require_once('Net/SCP.php');      // FIXME    Directory doesn't exist
        require_once('Crypt/RSA.php');    // FIXME    Directory doesn't exist
        require_once('Net/SFTP.php');     // FIXME    Directory doesn't exist
		switch( $pgProtocol ){
			case 1:
                require_once('Net/SSH1.php'); // FIXME    Directory doesn't exist
				break;
			case 2:
                require_once('Net/SSH2.php'); // FIXME    Directory doesn't exist
				break;
			default:
                require_once('Net/SSH2.php'); // FIXME    Directory doesn't exist
				break;
		}

		//Determine which SSH class to use. We could just use $pgProtocol as a variable to define the class, but this method error handles better.
		$this->protocol = $pgProtocol;
		$this->host = $pgHost;
		$this->username = $pgUsername;
        $this->privateKey = $pgPrivateKey;
		if( !$this->connect( $pgHost, $pgPort, $pgProtocol, $pgTimeout ) ) {
			pecho( "Cannot connect, aborting SSH initialization...\n\n", PECHO_FATAL );
			$this->__destruct();
			return;
		}
		pecho( "Successfully connected to $pgHost.\n\n", PECHO_NORMAL );

		//Now authenticate
        if (!($this->authenticate($pgUsername, $pgPassphrase, $pgPrivateKey))) {
			$this->__destruct();
			return;
		}

		$this->connected = true;
		if( $this->connected ) {
			echo "Connection socket open.\n\n";
		} else {
            echo "A connection error occurred. Closing...\n\n";
			$this->__destruct();
			return;
		}
	}

    /**
     * Establishes a connection to the remote server.
     *
     * @FIXME: Codebase no longer includes SSH-related classes
     *
     * @access  protected
     * @param   string $pgHost Host of server to connect to.
     * @param   int $pgPort Port of server.
     * @param   int $pgProtocol Which SSH protocol to use.
     * @param   int $pgTimeout How long before the connection times out. (Milliseconds)
     * @return  bool
     */
	protected function connect( $pgHost, $pgPort = 22, $pgProtocol, $pgTimeout = 10 ) {
		pecho( "Connecting to $pgHost:$pgPort...\n\n", PECHO_NORMAL );
		switch( $pgProtocol ){
			case 1:
                $this->SSHObject = new Net_SSH1($pgHost, $pgPort, $pgTimeout); // FIXME    Directory doesn't exist
				break;
			case 2:
                $this->SSHObject = new Net_SSH2($pgHost, $pgPort, $pgTimeout); // FIXME    Directory doesn't exist
				break;
			default:
                $this->SSHObject = new Net_SSH2($pgHost, $pgPort, $pgTimeout); // FIXME    Directory doesn't exist
				break;
		}

        return $this->SFTPObject = new Net_SFTP($pgHost, $pgPort, $pgTimeout); // FIXME    Directory doesn't exist
	}

	/**
	 * Authenticates to the remote server.
	 *
     * @access  protected
     * @param   string $pgUsername Username
     * @param   string $pgPassphrase Password or passphrase of key file
     * @param   string $pgPrivateKey File path of key file.
     * @return  bool
	 */
    protected function authenticate($pgUsername, $pgPassphrase, $pgPrivateKey)
    {
		//Determine the type of authentication to use.
		if( is_null( $pgUsername ) ) {
			pecho( "A username must at least be specified to authenticate to the server,\neven if there is authentication is none.\n\n", PECHO_FATAL );
			return false;
		}
		$fails = 0;
        if (!is_null($pgUsername) && !is_null($pgPrivateKey) && $this->protocol == 2) {
			pecho( "Authenticating with Private Key Authentication...\n\n", PECHO_NORMAL );
            $key = new Crypt_RSA(); // FIXME    Class doesn't exist
            if (!is_null($pgPassphrase)) {
                $key->setPassword($pgPassphrase);
            } // FIXME    Method doesn't exist
            $key->loadKey(file_get_contents($pgPrivateKey)); // FIXME    Method doesn't exist
            if ($this->SSHObject->login($pgUsername, $key) && $this->SFTPObject->login($pgUsername, $key)) {
				pecho( "Successfully authenticated using Private Key Authentication.\n\n", PECHO_NORMAL );
				return true;
			}
			$fails++;
		} else $fails += 5;
		if( !is_null( $pgUsername ) && !is_null( $pgPassphrase ) ) {
			pecho( "Authenticating with Password Authentication...\n\n", PECHO_NORMAL );
            if ($this->SSHObject->login($pgUsername, $pgPassphrase) && $this->SFTPObject->login($pgUsername,
                    $pgPassphrase)
            ) {
				pecho( "Successfully authenticated using Password Authentication\n\n", PECHO_NORMAL );
				return true;
			}
			$fails++;
		} else $fails += 5;
		if( !is_null( $pgUsername ) ) {
			pecho( "Authenticating with No Authentication...\n\n", PECHO_NORMAL );
            if ($this->SSHObject->login($pgUsername) && $this->SFTPObject->login($pgUsername)) {
				pecho( "Successfully authenticated with No Authentication\n\n", PECHO_NORMAL );
				return true;
			}
			$fails++;
		} else $fails += 5;

		if( $fails == 15 ) {
			pecho( "An incorrect combination of parameters was used and therefore, no proper authentication can be established.\n\n", PECHO_FATAL );
			return false;
		} else {
			pecho( "Peachy was unable to authenticate with any method of authentication.\nPlease check your connection settings and try again.\n\n", PECHO_FATAL );
			return false;
		}
	}

	/**
	 * Opens a shell, sends a command and returns output and closes the shell.
	 * NOTICE: Using a command that opens a new shell will cause hangups.
	 *
	 * @access public
	 * @param string $command Command to execute
	 * @param string $callback Function to call upon executing task.
	 * @param bool $displayError Should stderr be outputted as well. Only available with SSH2.
	 * @param bool $exitstatus Returns the exit status along with output.  Output becomes array. Only available with SSH2.
	 * @returns bool|string|array
	 */
	public function exec( $command, $callback = null, $displayError = false, $exitstatus = false ) {
		if( $this->protocol === 2 ) {
			if( $displayError ) {
                $this->SSHObject->enableQuietMode();
            } else $this->SSHObject->disableQuietMode();
		}
		if( $this->protocol === 2 && $exitstatus ) {
			return array(
                'result' => $this->SSHObject->exec($command, $callback),
                'exit_status' => $this->SSHObject->getExitStatus()
			);
        } else return $this->SSHObject->exec($command, $callback);
	}

	/**
	 * Opens an interactive shell if not done already and transmits commands and returns output.
	 *
	 * @access public
	 * @param string $command Command to execute
	 * @param string $expect String of output to expect and remove from output.
     * @param bool $expectRegex Switches string expectation to regular expressions.
	 * @returns bool|string
     *
     * FIXME    Contains undefined constants
	 */
    public function iExec($command, $expect = "", $expectRegex = false) {
		trim( $command, "\n" );
        if ($this->SSHObject->write($command . "\n")) {
            return $this->SSHObject->read($expect,
                ($expectRegex ? NET_SSH . $this->protocol . _READ_REGEX : NET_SSH . $this->protocol . _READ_SIMPLE));
		} else return false;
	}

	/**
	 * Sets a timeout for exec and iexec
	 *
	 * @access public
	 * @param int $time
	 * @return void
	 */
	public function setTimeout( $time )
    {
        $this->SSHObject->setTimeout($time);
	}

	/**
	 * Returns whether or not exec or iexec timed out.  Only available for SSH2.
	 *
	 * @access public
	 * @returns bool|null
	 */
	public function didTimout() {
		if( $this->protocol === 2 ) {
            return $this->SSHObject->isTimout();
		} else return null;
	}

	/**
	 * Write a file to a remote server
	 *
	 * @access public
	 * @param string $to location of file to be placed
	 * @param string $data data to write or file location of file to upload
	 * @param bool $resume resume an interrupted transfer
	 * @return bool
     *
     * FIXME    Contains undefined constants
	 */
	public function file_put_contents( $to, $data, $resume = false ) {
		if( $resume ) {
            return $this->SFTPObject->put($to, $data,
                (is_file($data) && file_exists($data) ? NET_SFTP_LOCAL_FILE : NET_SFTP_STRING) | NET_SFTP_RESUME);
        } else return $this->SFTPObject->put($to, $data,
            (is_file($data) && file_exists($data) ? NET_SFTP_LOCAL_FILE : NET_SFTP_STRING));

	}

	/**
	 * Retrieve a file from a remote server
	 *
	 * @access public
	 * @param string $from Location on remote server to retrieve from.
     * @param string|bool $to Location to write to.  If left blank, file contents is returned.
	 * @param int $offset Where to start retrieving files from.
	 * @param int $length How much of the file to retrieve.
	 * @returns bool|string
	 */
	public function file_get_contents( $from, $to = false, $offset = 0, $length = -1 )
    {
        return $this->SFTPObject->get($from, $to, $offset, $length);
	}

	/**
	 * Makes directory
	 *
	 * @param string $pathname The directory path.
	 * @param int $mode The mode is 0777 by default, which means the widest possible access.
	 * @param bool $recursive Allows the creation of nested directories specified in the pathname.
	 * @return bool
	 * @access public
	 */
	public function mkdir( $pathname, $mode = 0777, $recursive = false )
    {
        return $this->SFTPObject->mkdir($pathname, $mode, $recursive);
	}

	/**
	 * Changes SFTP's current directory to directory.
	 *
	 * @param string $directory The new current directory
	 * @return bool
	 * @access public
	 */
	public function chdir( $directory )
    {
        return $this->SFTPObject->chdir($directory);
	}

	/**
	 * Displays the current directory
	 *
	 * @access public
	 * @return string
	 */
	public function pwd()
    {
        return $this->SFTPObject->pwd();
	}

	/**
	 * Deletes a directory and all of its contents
	 *
	 * @access public
	 * @param string $dirname Path to the directory.
	 * @return bool
	 */
	public function rmdir( $dirname )
    {
        return $this->SFTPObject->delete($dirname, true);
	}

	/**
	 * Retrieves the contents of the directory
	 *
	 * @param string $dir Directory to retrieve
	 * @param bool $detailed Return details of contents
	 * @access public
	 * @return bool|array
	 */
	public function directory_get_contents( $dir, $detailed = false )
    {
        return $this->SFTPObject->_list($dir, $detailed);
	}

	/**
	 * Changes file mode
	 *
	 * @access public
	 * @param string $path Path to the directory or file
	 * @param int $mode Mode to change to
	 * @param bool $recursive Apply it to files within directory and children directories.
	 * @return bool|int
	 */
	public function chmod( $path, $mode, $recursive = false )
    {
        return $this->SFTPObject->chmod($mode, $path, $recursive);
	}

	/**
	 * Sets access and modification time of file
	 *
	 * @access public
	 * @param string $filename The name of the file being touched.
	 * @param int $time The touch time. If time is not supplied, the current system time is used.
	 * @param int $atime If present, the access time of the given filename is set to the value of atime. Otherwise, it is set to the value passed to the time parameter. If neither are present, the current system time is used.
	 * @return bool
	 */
	public function touch( $filename, $time = null, $atime = null )
    {
        return $this->SFTPObject->touch($filename, $time, $atime);
	}

	/**
	 * Changes file or directory owner
	 *
	 * @param string $filename Path to the file or directory.
	 * @param int $user A user number.
	 * @param bool $recursive Apply to all files and directories within dirctory.
	 * @access public
	 * @return bool
	 */
	public function chown( $filename, $user, $recursive = false )
    {
        return $this->SFTPObject->chown($filename, $user, $recursive);
	}

	/**
	 * Changes file or directory group
	 *
	 * @param string $filename Path to the file or directory.
	 * @param int $group A group number.
	 * @param bool $recursive Apply to all files and directories within dirctory.
	 * @access public
	 * @return bool
	 */
	public function chgrp( $filename, $group, $recursive = false )
    {
        return $this->SFTPObject->chgrp($filename, $group, $recursive);
	}

	/**
	 * Truncates a file to a specified size
	 *
	 * @param string $filename Path to the file.
	 * @param int $size Size to truncate to.
	 * @access public
	 * @return bool
	 */
	public function truncate( $filename, $size )
    {
        return $this->SFTPObject->truncate($filename, $size);
	}

	/**
	 * Gives information about a file
	 *
	 * @param string $filename Path to the file.
	 * @access public
	 * @return array|bool
	 */
	public function stat( $filename )
    {
        return $this->SFTPObject->stat($filename);
	}

	/**
	 * Gives information about a file or symbolic link
	 *
	 * @param string $filename Path to a file or a symbolic link.
	 * @access public
	 * @return array|bool
	 */
	public function lstat( $filename )
    {
        return $this->SFTPObject->lstat($filename);
	}

	/**
	 * Gets file size.  Files >4GB return as 4GB.
	 *
	 * @access public
	 * @param string $filename Path to the file.
	 * @return bool|int
	 */
	public function filesize( $filename )
    {
        return $this->SFTPObject->size($filename);
	}

	/**
	 * Deletes a file
	 *
	 * @access public
	 * @param string $filename Path to the file.
	 * @return bool
	 */
	public function unlink( $filename )
    {
        return $this->SFTPObject->delete($filename, false);
	}

	/**
	 * Renames a file or directory
	 *
	 * @access public
	 * @param string $oldname The old name.
	 * @param string $newname The new name.
	 * @return bool
	 */
	public function rename( $oldname, $newname )
    {
        return $this->SFTPObject->rename($oldname, $newname);
	}

    /**
     * Check for Update Function
     *
     * Checks the phpseclib/phpseclib library for updates.
     *
     * @return  bool    Returns true if no updates
     *                  Returns false if updates needed
     *
     * FIXME    The .json file may no longer contain ['commit']['sha'], but possibly ['tree']['sha']
     */
    protected function CheckForUpdate() {
		global $pgIP;
		$data = json_decode( $this->http->get( 'https://api.github.com/repos/phpseclib/phpseclib/branches/master', null, array(), false ), true );
		$this->commits = $data;
		if( !file_exists( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'phpseclibupdate' ) ) return false;
		$log = unserialize( file_get_contents( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'phpseclibupdate' ) );
		if( isset( $data['commit']['sha'] ) && $log['commit']['sha'] != $data['commit']['sha'] ) {
			pecho( "Updating SSH class!\n\n", PECHO_NOTICE );
			return false;
		}
		return true;
	}

	protected function installPHPseclib() {
		global $pgIP;
		$gitZip = $pgIP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'phpseclibupdate.zip';
		if( file_exists( $gitZip ) ) {
			unlink( $gitZip );
		}
		file_put_contents( $gitZip, file_get_contents( 'http://github.com/phpseclib/phpseclib/archive/master.zip' ) );
		$zip = new ZipArchive();
		$res = $zip->open( $gitZip );
		if( $res === true ) {
			$gitFolder = $pgIP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'phpseclibupdate';
			if( file_exists( $gitFolder ) ) {
				$this->rrmdir( $gitFolder );
			}
			mkdir( $gitFolder, 02775 );
			$zip->extractTo( $gitFolder );
			$zip->close();

			$this->copyOverGitFiles( $gitFolder . DIRECTORY_SEPARATOR . 'phpseclib-master' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR );

			pecho( "Successfully installed SSH class\n\n", PECHO_NOTICE );

			file_put_contents( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'phpseclibupdate', serialize( $this->commits ) );
		} else {
			pecho( "Unable to install SSH class\n\n", PECHO_WARN );
		}
	}

	/**
	 * @param string $gitFolder
	 */
	private function copyOverGitFiles( $gitFolder ) {
		/** @var $fileInfo DirectoryIterator */
		global $pgIP;
		if( !file_exists( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'SSHCore' ) ) mkdir( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'SSHCore', 2775 );
		foreach( new DirectoryIterator( $gitFolder ) as $fileInfo ){
			if( $fileInfo->isDot() ) continue;
			$gitPath = $fileInfo->getRealPath();
			$lclPatch = $this->getLocalPath( $gitPath );

			if( $fileInfo->isDir() ) {
				if( !file_exists( $lclPatch ) ) {
					mkdir( $lclPatch );
				}
				$this->copyOverGitFiles( $gitPath );
			} elseif( $fileInfo->isFile() ) {
				file_put_contents( $lclPatch, file_get_contents( $gitPath ) );
			}
		}
	}

	/**
	 * recursively remove a directory
	 * @param string $dir
	 */
	private function rrmdir( $dir ) {
		if( is_dir( $dir ) ) {
			$objects = scandir( $dir );
			foreach( $objects as $object ){
				if( $object != "." && $object != ".." ) {
					if( filetype( $dir . "/" . $object ) == "dir" ) $this->rrmdir( $dir . "/" . $object ); else unlink( $dir . "/" . $object );
				}
			}
			reset( $objects );
			rmdir( $dir );
		}
	}

	/**
	 * @param string $fullUpdatePath
	 * @return string
	 */
	private function getLocalPath( $fullUpdatePath ) {
		global $pgIP;
		$xplodesAt = DIRECTORY_SEPARATOR . 'phpseclibupdate' . DIRECTORY_SEPARATOR . 'phpseclib-master' . DIRECTORY_SEPARATOR . 'phpseclib' . DIRECTORY_SEPARATOR;
		$parts = explode( $xplodesAt, $fullUpdatePath, 2 );
		return $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'SSHCore' . DIRECTORY_SEPARATOR . $parts[1];
	}

	/**
	 * Destruction class. Closes the SSH connection and kills everything.
	 *
	 * @access private
	 * @return void
	 */
	public function __destruct()
    {
        $this->privateKey = null;
		$this->connected = false;
		$this->host = null;
		$this->username = null;
        $this->SSHObject->_disconnect("Peachy Connection Terminated");
        $this->SFTPObject->_disconnect("Peachy Connection Terminated");
	}
}
