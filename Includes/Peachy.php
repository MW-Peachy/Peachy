<?php

/**
 * Base Peachy class, used to generate all other classes
 */
class Peachy {

	/**
	 * Initializes Peachy, logs in with a either configuration file or a given username and password
	 *
	 * @static
	 * @access public
	 *
	 * @param string $config_name Name of the config file stored in the Configs directory, minus the .cfg extension. Default null
	 * @param string $username Username to log in if no config file specified. Default null
	 * @param string $password Password to log in with if no config file specified. Default null
	 * @param string $base_url URL to api.php if no config file specified. Defaults to English Wikipedia's API.
	 * @param string $classname
	 *
	 * @throws LoginError
	 * @return Wiki Instance of the Wiki class, where most functions are stored
	 */
	public static function newWiki( $config_name = null, $username = null, $password = null, $base_url = 'http://en.wikipedia.org/w/api.php', $classname = 'Wiki' ) {
		pecho( "Loading Peachy (version " . PEACHYVERSION . ")...\n\n", PECHO_NORMAL );
		/*$updater = new AutoUpdate();
		$Uptodate = $updater->Checkforupdate();
		if( !$Uptodate ) $updater->updatePeachy();*/

		if( !is_null( $config_name ) ) {
			$config_params = self::parse_config( $config_name );

		}
		else {
			$config_params = array(
				'username' => $username,
				'password' => $password,
				'baseurl' => $base_url
			);

		}

		if( is_null( $config_params['baseurl'] ) || !isset( $config_params['baseurl'] ) ) {
			throw new LoginError( array( "MissingParam", "The baseurl parameter was not set." ) );
		}

		if( !isset( $config_params['username'] ) || !isset( $config_params['password'] ) ) {
			$config_params['nologin'] = true;
		}

		list( $version, $extensions ) = Peachy::wikiChecks( $config_params['baseurl'] );

		Hooks::runHook( 'StartLogin', array( &$config_params, &$extensions ) );

		$w = new $classname( $config_params, $extensions, false, null );
		$w->mwversion = $version;

		return $w;
	}

	/**
	 * Performs various checks and settings
	 * Checks if MW version is at least {@link MINMW}
	 *
	 * @static
	 * @access public
	 * @param string $base_url URL to api.php
	 * @throws DependencyError
	 * @return array Installed extensions
	 */
	public static function wikiChecks( $base_url ) {
		$http = HTTP::getDefaultInstance();

		$siteinfo = unserialize( $http->get(
			$base_url,
			array( 'action' => 'query',
				'meta' => 'siteinfo',
				'format' => 'php',
				'siprop' => 'extensions|general',
			)));

		if( isset( $siteinfo['error'] ) && $siteinfo['error']['code'] == 'readapidenied' ) {
			global $pgHooks;
			$pgHooks['PostLogin'][] = array( 'Peachy::wikiChecks', $base_url );
			return array( MINMW, array() );
		}

		$version = preg_replace( '/[^0-9\.]/', '', $siteinfo['query']['general']['generator'] );

		if( version_compare( $version, MINMW ) < 0 ) {
			throw new DependencyError( "MediaWiki " . MINMW, "http://mediawiki.org" );
		}

		$extensions = array();

		foreach( $siteinfo['query']['extensions'] as $ext ) {
			if( isset( $ext['version'] ) ) {
				$extensions[$ext['name']] = $ext['version'];
			}
			else {
				$extensions[$ext['name']] = '';
			}
		}

		return array( $version, $extensions );
	}

	/**
	 * Loads a specific plugin into memory
	 *
	 * @static
	 * @access public
	 * @param string|array $plugins Name of plugin(s) to load from Plugins directory, minus .php ending
	 * @return void
	 * @deprecated since 18 June 2013
	 */
	public static function loadPlugin( $plugins ) {
		self::deprecatedWarn( null, null, "Warning: Peachy::loadPlugin() is deprecated. Thanks to the wonders of PHP 5, the call can just be removed." );
	}

	/**
	 * Loads all available plugins
	 *
	 * @static
	 * @access public
	 * @return void
	 * @deprecated since 18 June 2013
	 */
	public static function loadAllPlugins() {
		self::deprecatedWarn( null, null, "Warning: Peachy::loadAllPlugins() is deprecated. Thanks to the wonders of PHP 5, the call can just be removed." );

	}

	/**
	 * Checks for config files, parses them.
	 *
	 * @access private
	 * @static
	 * @param string $config_name Name of config file
	 * @throws BadEntryError
	 * @return array Config params
	 */
	private static function parse_config( $config_name ) {
		global $pgIP;
		if( !is_file( $config_name ) ) {
			if( !is_file( $pgIP . 'Configs/' . $config_name . '.cfg' ) ) {
				throw new BadEntryError( "BadConfig", "A non-existent configuration file was specified." );
			}
			else {
				$config_name = $pgIP . 'Configs/' . $config_name . '.cfg';
			}
		}



		$config_params = parse_ini_file( $config_name );

		if( isset( $config_params['useconfig'] ) ) {
			$config_params = $config_params + self::parse_config( $config_params['useconfig'] );
		}

		return $config_params;
	}

	/**
	 * @param null|string $method
	 * @param null|string $newfunction
	 * @param string $message
	 */
	public static function deprecatedWarn( $method, $newfunction, $message = null ) {
		if( is_null( $message ) ) {
			$message = "Warning: $method is deprecated. Please use $newfunction instead.";
		}

		$message = "[$message|YELLOW_BAR]\n\n";

		pecho( $message, PECHO_WARN, 'cecho' );
	}

	public static function getSvnInfo() {
		global $pgIP;

		// http://svnbook.red-bean.com/nightly/en/svn.developer.insidewc.html
		$entries = $pgIP . '/.svn/entries';

		if( !file_exists( $entries ) ) {
			return false;
		}

		$lines = file( $entries );
		if ( !count( $lines ) ) {
			return false;
		}

		// check if file is xml (subversion release <= 1.3) or not (subversion release = 1.4)
		if( preg_match( '/^<\?xml/', $lines[0] ) ) {
			return false;
		}

		// Subversion is release 1.4 or above.
		if ( count( $lines ) < 11 ) {
			return false;
		}

		$info = array(
			'checkout-rev' => intval( trim( $lines[3] ) ),
			'url' => trim( $lines[4] ),
			'repo-url' => trim( $lines[5] ),
			'directory-rev' => intval( trim( $lines[10] ) )
		);

		return $info;
	}
}