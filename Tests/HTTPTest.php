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

namespace Tests;
use HTTP;

/**
 * Class HTTPTest
 *
 * @package Tests
 */
class HTTPTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var HTTP $http
     */
    protected $http;
    protected $curl_instance;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();
        $this->http = new HTTP();
    }

    /**
     * Verify that get_HTTP_code() would return an integer
     *
     * Note: get_HTTP_code() relies on this $this->curl_instance. More testing may be required.
     *
     * @see HTTP::get_HTTP_code_test()
     */
    public function get_HTTP_code_test()
    {
        $this->curl_instance = curl_init( 'http://www.google.com' );
        $ci = $this->http->get_HTTP_code();
        $this->assertInternalType('int', $ci['http_code']);
    }

}
