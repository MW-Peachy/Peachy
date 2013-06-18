<?php

$pgAutoloader = array(
	'Wiki' => 'Includes/Wiki.php',
	'Script' => 'Script.php',
	'UtfNormal' => 'Plugins/normalize/UtfNormal.php',
	
	'DatabaseMySQL' => 'Plugins/database/MySQL.php',
	'DatabaseMySQLi' => 'Plugins/database/MySQLi.php',
	'DatabasePgSQL' => 'Plugins/database/PgSQL.php',
	'DatabaseBase' => 'Plugins/database.php',
	'ResultWrapper' => 'Plugins/database.php',
	
	'lime_test' => 'Includes/lime.php',
	'lime_output' => 'Includes/lime.php',
	'lime_output_color' => 'Includes/lime.php',
	'lime_colorizer' => 'Includes/lime.php',
	'lime_harness' => 'Includes/lime.php',
	'lime_coverage' => 'Includes/lime.php',
	'lime_registration' => 'Includes/lime.php',
	
	'sfYaml' => 'Plugins/yaml/sfYaml.php',
	'sfYamlDumper' => 'Plugins/yaml/sfYamlDumper.php',
	'sfYamlInline' => 'Plugins/yaml/sfYamlInline.php',
	'sfYamlParser' => 'Plugins/yaml/sfYamlParser.php',
	
	'Text_Diff' => 'Plugins/diff/textdiff/Diff.php',
	'Text_MappedDiff' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff_Op' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff_Op_copy' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff_Op_delete' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff_Op_add' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff_Op_change' => 'Plugins/diff/textdiff/Diff.php',
	'Text_Diff3' => 'Plugins/diff/textdiff/Diff3.php',
	'Text_Diff3_Op' => 'Plugins/diff/textdiff/Diff3.php',
	'Text_Diff3_Op_copy' => 'Plugins/diff/textdiff/Diff3.php',
	'Text_Diff3_BlockBuilder' => 'Plugins/diff/textdiff/Diff3.php',
	'Text_Diff_ThreeWay' => 'Plugins/diff/textdiff/Diff/ThreeWay.php',
	'Text_Diff_ThreeWay_Op' => 'Plugins/diff/textdiff/Diff/ThreeWay.php',
	'Text_Diff_ThreeWay_Op_copy' => 'Plugins/diff/textdiff/Diff/ThreeWay.php',
	'Text_Diff_ThreeWay_BlockBuilder' => 'Plugins/diff/textdiff/Diff/ThreeWay.php',
	'Text_Diff_Renderer' => 'Plugins/diff/textdiff/Diff/Renderer.php',
	'Text_Diff_Mapped' => 'Plugins/diff/textdiff/Diff/Mapped.php',
	'Text_Diff_Renderer_unified' => 'Plugins/diff/textdiff/Diff/Renderer/unified.php',
	'Text_Diff_Renderer_inline' => 'Plugins/diff/textdiff/Diff/Renderer/inline.php',
	'Text_Diff_Renderer_context' => 'Plugins/diff/textdiff/Diff/Renderer/context.php',
	'Text_Diff_Renderer_colorized' => 'Plugins/diff/textdiff/Diff/Renderer/colorized.php',
	'Text_Diff_Renderer_dualview' => 'Plugins/diff/textdiff/Diff/Renderer/dualview.php',
	'Text_Diff_Engine_xdiff' => 'Plugins/diff/textdiff/Diff/Engine/xdiff.php',
	'Text_Diff_Engine_string' => 'Plugins/diff/textdiff/Diff/Engine/string.php',
	'Text_Diff_Engine_shell' => 'Plugins/diff/textdiff/Diff/Engine/shell.php',
	'Text_Diff_Engine_native' => 'Plugins/diff/textdiff/Diff/Engine/native.php',
	
	'PeachyAWBFunctions' => 'Includes/PeachyAWBFunctions.php',
	
	
);

class AutoLoader {
	
	/**
	 * autoload - take a class name and attempt to load it
	 *
	 * @param $class_name String: name of class we're looking for.
	 * @return bool Returning false is important on failure as
	 * it allows Zend to try and look in other registered autoloaders
	 * as well.
	 */
	static function autoload( $class_name ) {
		global $pgIP, $pgAutoloader;

		if( isset( $pgAutoloader[$class_name] ) && is_file( $pgIP . $pgAutoloader[$class_name] ) ) {
			require_once( $pgIP . $pgAutoloader[$class_name] );
			return true;
		}
		
		if( is_file( $pgIP . 'Plugins/' . strtolower( $class_name ) . '.php' ) ) {
			Hooks::runHook( 'LoadPlugin', array( &$class_name ) );
					
			require_once( $pgIP . 'Plugins/' . strtolower( $class_name ) . '.php' );
			return true;
		}
		else {
			
			global $pgHTTP;
			
			$trunk_url = 'http://mw-peachy.googlecode.com/svn/trunk';
			
			$svninfo = Peachy::getSvnInfo();
			
			if( isset( $svninfo['url'] ) ) {
				$trunk_url = $svninfo['url'];
			}
			else {
				$trunk_url = 'http://mw-peachy.googlecode.com/svn/branches/REL' . strtoupper( str_replace( '.', '_', PEACHYVERSION ) );
			}
			
			$trunk_url = str_replace( 'https://', 'http://', $trunk_url );
			
			if( isset( $pgAutoloader[$class_name] ) ) {
				$file = $pgHTTP->get( $trunk_url . '/' . $pgAutoloader[$class_name] );
			}
			else {
				
				$file = $pgHTTP->get( $trunk_url . '/Plugins/' . strtolower( $class_name ) . '.php' );
				Hooks::runHook( 'LoadPlugin', array( &$class_name ) );
			}
			
			if( $pgHTTP->get_HTTP_code() == 200 ) {
				$temp_file = tempnam(sys_get_temp_dir(), 'peachy');
				
				file_put_contents($temp_file, <<<EOF
$file
EOF
	      		);
	      		
	      		require_once( $temp_file );
	      		
	      		unlink( $temp_file );
	      		
	      		return true;
			}
			return false;
			
		}

		return true;
	}

}

if ( function_exists( 'spl_autoload_register' ) ) {
	spl_autoload_register( array( 'AutoLoader', 'autoload' ) );
} else {
	function __autoload( $class ) {
		AutoLoader::autoload( $class );
	}

	ini_set( 'unserialize_callback_func', '__autoload' );
}
