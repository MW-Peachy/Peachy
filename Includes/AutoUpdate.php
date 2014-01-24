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
 * AutoUpdate class
 * Checks for a new version of Peachy and installes it if there is one.
 */
 
Class AutoUpdate {
    
    protected $http;
    protected $repository;
    
    function __construct() {
       global $pgIP, $experimentalupdates;
       $this->http = new HTTP( false, false );
       $this->repository = ($experimentalupdates ? 'cyberpower678' : 'MW-Peachy');
    }
    
    /**
    * Scans the GitHub repository for any updates and returns false if there are.
    * 
    * @access public
    * @return bool
    */
    public function Checkforupdate() {
        global $pgIP;
        pecho( "Checking for updates...\n\n", PECHO_NORMAL );    
        $data = json_decode( $this->get_http()->get('https://api.github.com/repos/'.$this->repository.'/Peachy/commits'), true );
        //$data = $this->processreturn($data);
        if( file_exists( $pgIP . 'Includes/Update.log' ) ) {
            $log = unserialize( file_get_contents( $pgIP . 'Includes/Update.log' ) );
            if( isset($data[0]['sha']) && $log[0]['sha'] != $data[0]['sha']) {
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
    * Updates the Peachy framework
    * 
    * @access public
    * @return bool
    */
    public function updatePeachy() {
        global $pgIP;
        pecho( "Updating Peachy...\n\n", PECHO_NORMAL ); 
        if( !file_exists($pgIP.'tmp') ) mkdir($pgIP.'tmp', 2775);   
        $data = json_decode( $this->get_http()->get('https://api.github.com/repos/'.$this->repository.'/Peachy/commits'), true );
        if( file_exists( $pgIP . 'Includes/Update.log' ) ) {
            $log = unserialize( file_get_contents( $pgIP . 'Includes/Update.log' ) );
            if( isset($data[0]['sha']) && $log[0]['sha'] != $data[0]['sha']) {
                foreach( $data as $item ) {
                    if( $item['sha'] == $log[0]['sha'] ) break;
                    $deploy[] = $item['url'];    
                }
                $success = $this->pullNewcontents( $deploy );
            } else {
                $success = true;
            }    
        } else {
            if( file_exists( $pgIP.'tmp/commit.tmp' ) && file_exists( $pgIP.'tmp/download.tmp' ) ){
                $commitdata = unserialize( file_get_contents($pgIP.'tmp/commit.tmp') );
                $downloaddata = unserialize( file_get_contents($pgIP.'tmp/download.tmp') );
                if( isset($data[0]['sha']) && $commitdata[0]['sha'] == $data[0]['sha']) {
                    $success = $this->pullcontents( 'https://api.github.com/repos/'.$this->repository.'/Peachy/contents', false, $downloaddata, true );                
                } elseif( isset($data[0]['sha']) && $commitdata[0]['sha'] != $data[0]['sha']) {
                    if( file_exists($pgIP.'tmp/commit.tmp') ) unlink( $pgIP.'tmp/commit.tmp' );
                    if( file_exists($pgIP.'tmp/download.tmp') ) unlink( $pgIP.'tmp/download.tmp' );
                    file_put_contents( $pgIP.'tmp/commit.tmp', serialize($data) );
                    $success = $this->pullcontents();                
                } else {
                   $success = false; 
                }
            } else {
                if( file_exists($pgIP.'tmp') ) mkdir($pgIP.'tmp', 2775);
                file_put_contents( $pgIP.'tmp/commit.tmp', serialize($data) );
                //$data = $this->processreturn( $data );
                $success = $this->pullcontents();
            }
        }
        if( $success ) {
            file_put_contents( $pgIP.'Includes/Update.log', serialize($data) );
            if( file_exists($pgIP.'tmp/commit.tmp') ) unlink( $pgIP.'tmp/commit.tmp' );
            if( file_exists($pgIP.'tmp/download.tmp') ) unlink( $pgIP.'tmp/download.tmp' );
            pecho( "Peachy Updated!  Changes will go into effect on the next run.\n\n", PECHO_NOTICE );
            return true;
        } else {
            pecho( "Update failed!  Peachy could not retrieve all contents from GitHub.  Please open an issue on cyberpower678/Peachy.\n\n", PECHO_WARN );
            return false;
        }    
    }
    
    /**
    * Pulls the specified commits from GitHub.
    *
    * @access private
    * @return bool 
    */
    private function pullNewcontents( $links=array(), $files = array() ) {
        global $pgIP;
        foreach( array_reverse($links) as $path ) {
            $data = json_decode( $this->get_http()->get($path), true );
            if( !isset($data['files']) ) {
                pecho( "Error retreiving file.  GitHub query limit may be exhausted.\n\n", PECHO_WARN );
                return false;    
            }
            foreach( $data['files'] as $file ) {
                $files[$pgIP.$file['filename']]['status'] = $file['status'];
                if( $file['status'] != 'removed' ) {
                    $data2 = json_decode( $this->get_http()->get('https://api.github.com/repos/'.$this->repository.'/Peachy/contents/'.$file['filename']), true );
                    if( isset($data2['encoding']) && $data2['encoding'] == 'base64' ) $files[$pgIP.$file['filename']]['content'] = base64_decode($data2['content']);
                    elseif( isset($data2['encoding']) && $data2['encoding'] != 'base64' ) {
                        pecho( "Error: Unknown encoding, ".$item['encoding'].".", PECHO_WARN );
                        return false;
                    } else {                                     
                        pecho( "Error retreiving file.  GitHub query limit may be exhausted.\n\n", PECHO_WARN );
                        return false;
                    }
                } else $files[$pgIP.$file['filename']]['content'] = null;
            }
        }
        //process the new files.
        foreach( $files as $path=>$data ) {
            if( $data['status'] == 'removed' ) {
                unlink($path);
            } else {
                $result = file_put_contents($path, $data['content']);
                if( !$result ) return false;
            }
        }
        return true;       
    }
    
    /**
    * Pulls the contents from GitHub.
    *
    * @access private
    * @return bool 
    */
    private function pullcontents( $path='https://api.github.com/repos/'.$this->repository.'/Peachy/contents', $recurse = false, $files = array(), $dir = true ) {
        global $pgIP;
        $data = json_decode( $this->get_http()->get($path), true );
        //$data = $this->processreturn( $data );
        //Gather and decode the contents of the repository
        if( $dir ) {
            foreach( $data as $item ) {
                if( isset($item['type']) && $item['type'] == 'file' ) {
                    if( isset($item['encoding']) && $item['encoding'] == 'base64' ) {
                        $files[$pgIP.$item['path']] = base64_decode($item['content']);   
                    } elseif( isset($item['encoding']) && $item['encoding'] != 'base64' ) {
                        pecho( "Error: Unknown encoding, ".$item['encoding'].".", PECHO_WARN );
                        return false;
                    } else {
                        if( !isset($files[$pgIP.$item['path']]) ) $files = $this->pullcontents( $item['url'], true, $files, false );
                        if( !$files ) return false;
                    } 
                } elseif( isset($item['type']) && $item['type'] == 'dir' ) {
                    if( !file_exists( $pgIP.$item['path'] ) ) mkdir( $pgIP.$item['path'], 2775 );
                    $files = $this->pullcontents( $item['url'], true, $files, true );
                    if( !$files ) return false;
                } else {
                    file_put_contents( $pgIP.'tmp/download.tmp', serialize($files) );
                    pecho( "Error retreiving file.  GitHub query limit may be exhausted.\n\n", PECHO_WARN );
                    return false;
                }       
            }
        } else {
            $item = $data;
            if( isset($item['type']) && $item['type'] == 'file' ) {
                if( isset($item['encoding']) && $item['encoding'] == 'base64' ) {
                    $files[$pgIP.$item['path']] = base64_decode($item['content']);   
                } elseif( isset($item['encoding']) && $item['encoding'] != 'base64' ) {
                    pecho( "Error: Unknown encoding, ".$item['encoding'].".", PECHO_WARN );
                    return false;
                } else {
                    if( !isset($files[$pgIP.$item['path']]) ) $files = $this->pullcontents( $item['url'], true, $files, false );
                    if( !$files ) return false;
                } 
            } elseif( isset($item['type']) && $item['type'] == 'dir' ) {
                if( !file_exists( $pgIP.$item['path'] ) ) mkdir( $pgIP.$item['path'], 2775 );
                $files = $this->pullcontents( $item['url'], true, $files, true );
                if( !$files ) return false;
            } else {
                file_put_contents( $pgIP.'tmp/download.tmp', serialize($files) );
                pecho( "Error retreiving file.  GitHub query limit may be exhausted.\n\n", PECHO_WARN );
                return false;
            }
        }
        if( $recurse ) return $files;
        else {
            //Load all the files.
            pecho( "Retrieved files from Github...\n\n", PECHO_VERBOSE );
            foreach( $files as $filepath=>$contents ) {
                $result = file_put_contents( $filepath, $contents );
                if( !$result ) return false;
            }
        }
        return true;                      
    }
    
    /**
    * Cleans up the returned Git information.
    * 
    * @param mixed $data The object returned from the json_decode to be converted to an array.
    * @access public
    * @return array
    */
    public function processreturn($data){
        //Arrayify the data to be readable
        foreach( $data as $value=>$object ) {
            if( is_object($data[$value]) ) {
                $data[$value] = (array)$object;
                $data[$value] = $this->processreturn((array)$object);
            }
        }
        return $data;
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
    
}   
?>
