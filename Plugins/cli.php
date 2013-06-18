<?php

class CLI {
	
	static function getInput( $question = null, $answers = array(), $hide = false, $len = false ) {
			
		$ret = null;
		
		while( $ret == null ) {
			if( !is_null( $question ) ) {
				echo $question;
			}
			
			if( count( $answers ) > 0 ) {
				echo " [". implode( '/', $answers ) . "]";
			}
			
			echo  ": ";
		
		
			if( $hide ) system('stty -echo');
			
			if( $len ) {
				$line = trim(fgets(STDIN, ($len+1)));
			}
			else {
				$line = trim(fgets(STDIN));
			}
			
			if( $hide ) { system('stty echo'); echo "\n"; }
			
			if( count( $answers ) > 0 ) {
				if( in_array( strtolower($line), array_map('strtolower',$answers) ) ) {
					$ret = trim($line);
				} 
				else {	
					echo "Sorry, please try again.\n";
				}
			}
			else {
				$ret = trim($line);
			}
		}
		
		return $line;
	}
	
	static function getInt( $question = null, $answers = array(), $hide = false, $len = false ) {
		return intval( CLI::getInput( $question, $answers, $hide, $len ) );
	}
	
	static function getIntOrNull( $question = null, $answers = array(), $hide = false, $len = false ) {
		$val = CLI::getInput( $question, $answers, $hide, $len );
		return is_numeric( $val )
            ? intval( $val )
            : null;
	}
	
	static function getBool( $question = null ) {
		$res = CLI::getInput( $question, array( 'y', 'n' ) );
		if( $res == "y" ) return true;
		return false;
	}
	
}
