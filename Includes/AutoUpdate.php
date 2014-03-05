<?php

/*This file is part of Peachy MediaWiki Bot API

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
 * Checks for a new version of Peachy and installs it if there is one.
 */
Class AutoUpdate {

	/**
	 * @var Http
	 */
	protected $http;
	protected $repository;
	protected $logfile;
	protected $lastused;
	protected $commits;

	function __construct( $http ) {
		global $pgIP, $experimentalupdates;
		$this->http = $http;
		$this->repository = ( $experimentalupdates ? 'master' : 'stable' );
		$this->logfile = ( $experimentalupdates ? 'Update.log' : 'StableUpdate.log' );
		$this->lastused = ( file_exists( $pgIP . 'Includes/updateversion' ) ? unserialize( file_get_contents( $pgIP . 'Includes/updateversion' ) ) : 'Unknown' );
	}

	/**
	 * Scans the GitHub repository for any updates and returns false if there are.
	 *
	 * @access public
	 * @return bool
	 */
	public function Checkforupdate() {
		global $pgIP, $experimentalupdates;
		pecho( "Checking for updates...\n\n", PECHO_NORMAL );
		if( $experimentalupdates ) pecho( "Warning: You have experimental updates switched on.\nExperimental updates are not fully tested and can cause problems,\nsuch as, bot misbehaviors up to complete crashes.\nUse at your own risk.\nPeachy will not revert back to a stable release until switched off.\n\n", PECHO_NOTICE );
		$data = json_decode( $this->http->get( 'https://api.github.com/repos/MW-Peachy/Peachy/branches/' . $this->repository, null, array(), false ), true );
		$this->commits = $data;
		/*if( strstr( $this->http->getLastHeader(), 'Status: 304 Not Modified') ) {
			pecho( "Peachy is up to date.\n\n", PECHO_NORMAL );
			return true;
		}*/
		if( is_array( $data ) && array_key_exists( 'message', $data ) && strpos( $data['message'], 'API rate limit exceeded' ) === 0 ) {
			pecho( "Cant check for updates right now, next window in " . $this->getTimeToNextLimitWindow() . "\n\n", PECHO_NOTICE );
			return true;
		}
		$this->cacheLastGithubETag();
		if( $this->lastused !== $this->repository ) {
			pecho( "Changing Peachy version to run using " . ( $experimentalupdates ? "experimental" : "stable" ) . " updates.\n\n", PECHO_NOTICE );
			return false;
		}
		if( file_exists( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . $this->logfile ) ) {
			$log = unserialize( file_get_contents( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . $this->logfile ) );
			if( isset( $data['commit']['sha'] ) && $log['commit']['sha'] != $data['commit']['sha'] ) {
				pecho( "Update available!\n\n", PECHO_NOTICE );
				return false;
			} else {
				pecho( "Peachy is up to date.\n\n", PECHO_NORMAL );
				return true;
			}
		} else {
			pecho( "No update log found.\n\n", PECHO_WARN );
			return false;
		}
	}

	/**
	 * Caches the last Etag from github in a tmp file
	 */
	private function cacheLastGithubETag() {
		global $pgIP;
		if( preg_match( '/ETag\: \"([^\"]*)\"/', $this->http->getLastHeader(), $matches ) ) {
			file_put_contents( $pgIP . 'tmp' . DIRECTORY_SEPARATOR . 'github-ETag.tmp', $matches[1] );
		}
	}

	/**
	 * @return string representing time to next github api request window
	 */
	private function getTimeToNextLimitWindow() {
		if( preg_match( '/X-RateLimit-Reset: (\d*)/', $this->http->getLastHeader(), $matches ) ) {
			return gmdate( "i \m\i\\n\u\\t\\e\s s \s\\e\c\o\\n\d\s", $matches[1] - time() );
		}
		return 'Unknown';
	}

	private function getLocalPath( $fullUpdatePath ) {
		global $pgIP;
		$xplodesAt = DIRECTORY_SEPARATOR . 'gitUpdate' . DIRECTORY_SEPARATOR . 'Peachy-' . $this->repository . DIRECTORY_SEPARATOR;
		$parts = explode( $xplodesAt, $fullUpdatePath, 2 );
		return $pgIP . $parts[1];
	}

	/**
	 * Updates the Peachy framework
	 *
	 * @access public
	 * @return boolean|null
	 */
	public function updatePeachy() {
		global $pgIP, $experimentalupdates;
		$gitZip = $pgIP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'gitUpdate.zip';
		if( file_exists( $gitZip ) ) {
			unlink( $gitZip );
		}
		file_put_contents( $gitZip, file_get_contents( 'http://github.com/MW-Peachy/Peachy/archive/' . $this->repository . '.zip' ) );
		$zip = new ZipArchive();
		$res = $zip->open( $gitZip );
		if( $res === true ) {
			$gitFolder = $pgIP . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'gitUpdate';
			if( file_exists( $gitFolder ) ) {
				$this->rrmdir( $gitFolder );
			}
			mkdir( $gitFolder, 02775 );
			$zip->extractTo( $gitFolder );
			$zip->close();

			$this->copyOverGitFiles( $gitFolder . DIRECTORY_SEPARATOR . 'Peachy-' . $this->repository );

			file_put_contents( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . 'updateversion', serialize( ( $experimentalupdates ? 'master' : 'stable' ) ) );

			pecho( "Peachy Updated!  Changes will go into effect on the next run.\n\n", PECHO_NOTICE );

			file_put_contents( $pgIP . 'Includes' . DIRECTORY_SEPARATOR . $this->logfile, serialize( $this->commits ) );
		} else {
			pecho( "Update failed!  Peachy could not retrieve all contents from GitHub.\n\n", PECHO_WARN );
		}
	}

	/**
	 * @param string $gitFolder
	 */
	private function copyOverGitFiles( $gitFolder ) {
		/** @var $fileInfo DirectoryIterator */
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
}
