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
 * Stores all the subclasses of Exception
 */

/**
 * Generic Peachy Error
 *
 * @package Exceptions
 */
class PeachyError extends Exception {

	public function __construct( $code, $text ) {
		parent::__construct(
			"API Error: " . $code . " (" . $text . ")"
		);
	}
}

/**
 * Generic API Error
 *
 * @package Exceptions
 */
class APIError extends Exception {

	public function __construct( $error ) {
		parent::__construct(
			"API Error: " . $error['code'] . " (" . $error['text'] . $error['info'] . ")"
		);
	}
}

/**
 * Error with user permissions
 *
 * @package Exceptions
 */
class PermissionsError extends Exception {
	public function __construct( $error ) {
		parent::__construct(
			"Permissions Error: " . $error
		);
	}
}

/**
 * Generic cURL Error
 *
 * @package Exceptions
 */
class CURLError extends Exception {
	private $errno;
	private $error;

	/**
	 * @param integer $errno
	 * @param string $error
	 */
	public function __construct( $errno, $error ) {
		$this->errno = $errno;
		$this->error = $error;

		parent::__construct(
			"cURL Error (" . $this->errno . "): " . $this->error
		);
	}

	public function get_errno() {
		return $this->errno;
	}

	public function get_error() {
		return $this->error;
	}

}

/**
 * Invalid Title Error
 *
 * @package Exceptions
 */
class BadTitle extends Exception {

	private $title;

	public function __construct( $title ) {
		$this->title = $title;
		parent::__construct(
			"Invalid title: $title"
		);

	}

	public function getTitle() {
		return $this->title;
	}
}

/**
 * No Title Error
 *
 * @package Exceptions
 */
class NoTitle extends Exception {

	public function __construct() {
		parent::__construct(
			"No title or pageid stated when instantiating Page class"
		);

	}
}

/**
 * No User Error
 *
 * @package Exceptions
 */
class NoUser extends Exception {

	public function __construct( $title ) {
		parent::__construct(
			"Non-existant user: $title"
		);

	}
}

/**
 * Blocked User Error
 *
 * @package Exceptions
 */
class UserBlocked extends Exception {

	public function __construct( $username = "User" ) {
		parent::__construct(
			$username . " is currently blocked."
		);

	}

}

/**
 * Logged Out Error
 *
 * @package Exceptions
 */
class LoggedOut extends Exception {

	public function __construct() {
		parent::__construct(
			"User is not logged in."
		);

	}

}

/**
 * Missing DependencyError Error
 *
 * @package Exceptions
 */
class DependencyError extends Exception {

	public function __construct( $software, $url = false ) {
		$message = "Missing dependency: \`" . $software . "\`. ";
		if( $url ) $message .= "Download from <$url>";
		parent::__construct(
			$message
		);

	}

}

/**
 * Misspelling of "dependency", used for backwards compatibility
 *
 * @package Exceptions
 * @deprecated since 31 Jan 2014
 */
class DependancyError extends DependencyError {
	public function __construct( $software, $url = false ) {
		parent::__construct( $software, $url );
	}
}

/**
 * Login Error
 *
 * @package Exceptions
 */
class LoginError extends Exception {

	/**
	 * @param string[] $error
	 */
	public function __construct( $error ) {
		parent::__construct(
			"Login Error: " . $error[0] . " (" . $error[1] . ")"
		);
	}
}

/**
 * Peachy Hook Error
 *
 * @package Exceptions
 * @package Peachy_Hooks
 */
class HookError extends Exception {
	public function __construct( $error ) {
		parent::__construct(
			"Hook Error: " . $error
		);
	}
}

/**
 * Generic Database Error
 *
 * @package Exceptions
 * @package Peachy_Database
 */
class DBError extends Exception {

	/**
	 * @param string $sql
	 */
	public function __construct( $error, $errno, $sql = null ) {
		parent::__construct(
			"Database Error: " . $error . " (code $errno) " . $sql
		);
	}
}

/**
 * Generic Edit Error
 *
 * @package Exceptions
 */
class EditError extends Exception {

	/**
	 * @param string $error
	 * @param string $text
	 */
	public function __construct( $error, $text ) {
		parent::__construct(
			"Edit Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Move Error
 *
 * @package Exceptions
 */
class MoveError extends Exception {
	public function __construct( $error, $text ) {
		parent::__construct(
			"Move Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Delete Error
 *
 * @package Exceptions
 */
class DeleteError extends Exception {
	public function __construct( $error, $text ) {
		parent::__construct(
			"Delete Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Undelete Error
 *
 * @package Exceptions
 */
class UndeleteError extends Exception {
	public function __construct( $error, $text ) {
		parent::__construct(
			"Undelete Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Protect Error
 *
 * @package Exceptions
 */
class ProtectError extends Exception {
	public function __construct( $error, $text ) {
		parent::__construct(
			"Protect Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Email Error
 *
 * @package Exceptions
 */
class EmailError extends Exception {
	public function __construct( $error, $text ) {
		parent::__construct(
			"Email Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic Image Error
 *
 * @package Exceptions
 */
class ImageError extends Exception {
	public function __construct( $error ) {
		parent::__construct(
			"Image Error: " . $error
		);
	}
}

/**
 * Error for wrong parameters in a function
 *
 * @package Exceptions
 */
class BadEntryError extends Exception {

	/**
	 * @param string $error
	 */
	public function __construct( $error, $text ) {
		parent::__construct(
			"Bad Entry Error: " . $error . " ($text)"
		);
	}
}

/**
 * Generic XML Error
 *
 * @package Exceptions
 * @package XML
 */
class XMLError extends Exception {
	public function __construct( $error ) {
		parent::__construct(
			"XML Error: " . $error
		);
	}
}

