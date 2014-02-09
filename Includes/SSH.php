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

require_once( 'SSHCore/Net/SCP.php' );
require_once( 'SSHCore/Crypt/RSA.php' );
require_once( 'SSHCore/Net/SFTP.php' );

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
    private $prikey;
    
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
    private $sshobject;
    
    /**
     * The SFTP connection object.
     * 
     * @var Object
     * @access private
     */
    private $sftpobject;
    
    
    /**
     * Construction method for the SSH class
     * 
     * @access public
     * @param string $host Address of remote host.  Default 
     * 
     * @return void
     */    
    function __construct( $host, $port = 22, $username=null, $passphrase=null, $prikey=null, $protocol = 2, $timeout = 10 ) {
        pecho( "Initializing SSH class...\n\n", PECHO_NORMAL );
        //Determine which SSH class to use. We could just use $protocol as a variable to define the class, but this method error handles better.
        switch( $protocol ) {
            case 1:
            require_once( 'SSHCore/Net/SSH1.php' );
            break;
            case 2:
            require_once( 'SSHCore/Net/SSH2.php' );
            break;
            default:
            require_once( 'SSHCore/Net/SSH2.php' );
            break;
        }
        $this->protocol = $protocol;
        $this->host = $host;
        $this->username = $username;
        $this->prikey = $prikey;
        if( !$this->connect( $host, $port, $protocol, $timeout ) ) {
            $this->__destruct();
            return;
        }
        
        //Now authenticate
        if( !($this->authenticate( $username, $passphrase, $prikey )) ) {
            $this->__destruct();
            return;
        }
        
        $this->connected = ( $protocol == 2 ? $this->sshobject->isConnected() : $this->sshobject->getSocket() );
        if( $this->connected ) echo "Connection socket open.\n\n";
        else {
            echo "A connection error occured. Closing...\n\n";
            $this->__destruct();
            return;
        }
    }
    /**
    * Establishes a connection to the remote server.
    *
    * @access protected 
    * @param string $host Host of server to connect to.
    * @param int $port Port of server.
    * @param int $protocol Which SSH protocol to use.
    * @return bool
    */
    protected function connect( $host, $port = 22, $protocol, $timeout = 10 ) {
        pecho( "Connecting to $host:$port...\n\n", PECHO_NORMAL );
        switch( $protocol ) {
            case 1:
            $this->sshobject = new Net_SSH1( $host, $port, $timeout );
            break;
            case 2:
            $this->sshobject = new Net_SSH2( $host, $port, $timeout );
            break;
            default:
            $this->sshobject = new Net_SSH2( $host, $port, $timeout );
            break;
        }
        $this->sftpobject = new Net_SFTP( $host, $port, $timeout );
        $this->connected = ( $protocol == 2 ? $this->sshobject->isConnected() : $this->sshobject->getSocket() );
        if( !($this->sshobject->getSocket()) ) {
            pecho( "Cannot connect, aborting SSH initialization...\n\n", PECHO_FATAL );
            return false;
        } else {
            pecho( "Successfully connected to $host.\n\n", PECHO_NORMAL );
            return true;
        }
    }
    
    /**
    * Authenticates to the remote server.
    * 
    * @access protected
    * @param string $username Username
    * @param string $passphrase Password or passphrase of key file
    * @param string $prikey File path of key file.
    * @return bool
    */
    protected function authenticate( $username, $passphrase, $prikey ) {
        //Determine the type of authentication to use.
        if( is_null( $username ) ) {
            pecho( "A username must at least be specified to authenticate to the server,\neven if there is authentication is none.\n\n", PECHO_FATAL );
            return false;
        } 
        $fails = 0;
        if( !is_null( $username ) && !is_null( $prikey ) && $this->protocol == 2 ) {
            pecho( "Authenticating with Private Key Authentication...\n\n", PECHO_NORMAL );
            $key = new Crypt_RSA();
            if( !is_null( $passphrase ) ) $key->setPassword($passphrase);
            $key->loadKey(file_get_contents($prikey));
            if( $this->sshobject->login($username, $key) && $this->sftpobject->login($username, $key) ) {
                pecho( "Successfully authenticated using Private Key Authentication.\n\n", PECHO_NORMAL );
                return true;   
            }
            $fails++;
        } else $fails += 5;
        if( !is_null( $username ) && !is_null( $passphrase ) ) {
            pecho( "Authenticating with Password Authentication...\n\n", PECHO_NORMAL );
            if( $this->sshobject->login($username, $passphrase) && $this->sftpobject->login($username, $passphrase) ) {
                pecho( "Successfully authenticated using Password Authentication\n\n", PECHO_NORMAL );
                return true;
            }
            $fails++;
        } else $fails += 5;
        if( !is_null( $username ) ) {
            pecho( "Authenticating with No Authentication...\n\n", PECHO_NORMAL );
            if( $this->sshobject->login($username) && $this->sftpobject->login($username) ) {
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
    * Establishes a mysql connection over a remote SSH tunnel.  This function requires further development.
    * 
    * @access public
    * @param string $host Host of DB server location
    * @param string $username Username to access DB server with
    * @param string $passwd Password to authenticate with
    * @param string $dbname DB name to use
    * @param int $port Port of DB server
    * @return null
    */
    public function mysqli_connect( $host = null, $username = null, $passwd = null, $dbname = "", $port = null ) {
        pecho( "This function requires further development of the SSH class.\n\n", PECHO_WARN );
        return null;
    }
    
    /**
    * Opens a shell, sends a command and returns output and closes the shell.
    * NOTICE: Using a command that opens a new shell will cause hangups.
    *
    * @access public
    * @param string $command Comand to execute
    * @param string $callback Function to call upon executing task.
    * @param bool $displayError Should stderr be outputted as well. Only available with SSH2.
    * @param bool $exitstatus Returns the exit status along with output.  Output becomes array. Only available with SSH2.
    * @returns bool|string|array
    */
    public function exec( $command, $callback = null, $displayError = false, $exitstatus = false ) {
        if( $this->protocol === 2 ) {
            if( $displayError ) $this->sshobject->enableQuietMode();
            else $this->sshobject->disableQuietMode();   
        }
        if( $this->protocol === 2 && $exitstatus ) return array( 'result' => $this->sshobject->exec( $command, $callback ), 'exit_status' => $this->sshobject->getExitStatus() );
        else return $this->sshobject->exec( $command, $callback );
    }
    
    /**
    * Opens an interactive shell if not done already and transmits commands and returns output.
    * 
    * @access public
    * @param string $command Command to execute
    * @param string $expect String of output to expect and remove from output.
    * @param bool $expectregex Switches string expectation to regular expressions.
    * @returns bool|string
    */
    public function iexec( $command, $expect = "", $expectregex = false ) {
        trim( $command, "\n" );
        if( $this->sshobject->write( $command."\n" ) ) {
            return $this->sshobject->read( $expect, ( $expectregex ? NET_SSH.$this->protocol._READ_REGEX : NET_SSH.$this->protocol._READ_SIMPLE ) );  
        } else return false;
    }
    
    /**
    * Sets a timeout for exec and iexec
    * 
    * @access public
    * @param int $time
    * @return void
    */
    public function setTimeout( $time ) {
        $this->sshobject->setTimeout( $time );
    }
    
    /**
    * Returns whether or not exec or iexec timed out.  Only available for SSH2.
    * 
    * @access public
    * @returns bool|null
    */
    public function didTimout() {
        if( $this->protocol === 2 ) return $this->sshobject->isTimout();
        else return null;
    }
    
    /**
    * Write a file to a remote server
    * 
    * @access public
    * @param string $to location of file to be placed
    * @param string $data data to write or file location of file to upload
    * @param bool $resume resume an interrupted transfer
    * @return bool
    */
    public function file_put_contents( $to, $data, $resume = false ) {
        if( $resume ) return $this->sftpobject->put( $to, $data, ( is_file( $data ) && file_exists( $data ) ? NET_SFTP_LOCAL_FILE : NET_SFTP_STRING ) | NET_SFTP_RESUME );
        else return $this->sftpobject->put( $to, $data, ( is_file( $data ) && file_exists( $data ) ? NET_SFTP_LOCAL_FILE : NET_SFTP_STRING ) );      
      
    }
    
    /**
    * Retrieve a file from a remote server
    * 
    * @access public
    * @param string $from Location on remote server to retrieve from.
    * @param string $to Location to write to.  If left blank, file contents is returned.
    * @param int $offset Where to start retrieving files from.
    * @param int $length How much of the file to retrieve.
    * @returns bool|string
    */
    public function file_get_contents( $from, $to = false, $offset = 0, $length = -1 ) {
        return $this->sftpobject->get( $from, $to, $offset, $length );   
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
    public function mkdir( $pathname, $mode = 0777, $recursive = false ) {
        return $this->sftpobject->mkdir( $pathname, $mode, $recursive );
    }
    
    /**
     * Changes SFTP's current directory to directory. 
     *
     * @param string $directory The new current directory
     * @return bool
     * @access public
     */
    public function chdir( $directory ) {
        return $this->sftpobject->chdir( $directory );
    }
    
    /**
    * Displays the current directory
    * 
    * @access public
    * @return string
    */
    public function pwd() {
        return $this->sftpobject->pwd();
    }
    
    /**
    * Deletes a directory and all of its contents
    * 
    * @access public
    * @param string $dirname Path to the directory.
    * @return bool
    */
    public function rmdir( $dirname ) {
        return $this->sftpobject->delete( $dirname, true );
    }
    
    /**
    * Retrieves the contents of the directory
    * 
    * @param string $dir Directory to retrieve
    * @param bool $detailed Return details of contents
    * @access public
    * @return bool|array
    */
    public function directory_get_contents( $dir, $detailed = false ) {
        return $this->sftpobject->_list( $dir, $detailed );
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
    public function chmod( $path, $mode, $recursive = false) {
        return $this->sftpobject->chmod( $mode, $path, $recursive );   
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
    public function touch($filename, $time = null, $atime = null) {
        return $this->sftpobject->touch( $filename, $time, $atime ); 
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
    public function chown( $filename, $user, $recursive = false ) {
        return $this->sftpobject->chown( $filename, $user, $recursive );   
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
    public function chgrp( $filename, $group, $recursive = false ) {
        return $this->sftpobject->chgrp( $filename, $group, $recursive );   
    }
    
    /**
    * Truncates a file to a specified size
    * 
    * @param string $filename Path to the file.
    * @param int $size Size to truncate to.
    * @access public
    * @return bool
    */
    public function truncate( $filename, $size ){
        return $this->sftpobject->truncate( $filename, $size );
    }
    
    /**
    * Gives information about a file
    * 
    * @param string $filename Path to the file. 
    * @access public
    * @return array|bool
    */
    public function stat( $filename ) {
        return $this->sftpobject->stat( $filename );  
    }
    
    /**
    * Gives information about a file or symbolic link
    * 
    * @param string $filename Path to a file or a symbolic link.  
    * @access public
    * @return array|bool
    */
    public function lstat( $filename ) {
        return $this->sftpobject->lstat( $filename );  
    }
    
    /**
    * Gets file size.  Files >4GB return as 4GB.
    * 
    * @access public
    * @param string $filename Path to the file. 
    * @return bool|int
    */
    public function filesize( $filename ) {
        return $this->sftpobject->size( $filename );  
    }
    
    /**
    * Deletes a file
    * 
    * @access public
    * @param string $filename Path to the file. 
    * @return bool
    */
    public function unlink( $filename ) {
        return $this->sftpobject->delete( $filename, false );   
    }
    
    /**
    * Renames a file or directory
    *                            
    * @access public
    * @param string $oldname The old name.
    * @param string $newname The new name.
    * @return bool
    */
    public function rename( $oldname, $newname ) {
        return $this->sftpobject->rename( $oldname, $newname );  
    }
    
    /**
    * Destruction class.  Closes the SSH connection and kills everything.
    * 
    * @access private
    * @return void
    */
    function __destruct() {
        $this->prikey = null;
        $this->conected = false;
        $this->host = null;
        $this->username = null;
        $this->sshobject->_disconnect( "Peachy Connection Terminated" );
        $this->sftpobject->_disconnect( "Peachy Connection Terminated" );
    }
 }
?>
