<?php

if( !class_exists( 'Peachy' ) ) {
	require_once(dirname(dirname(__FILE__)) . '/Init.php' );
}

/**
 * This file is part of the symfony package.
 * (c) 2004-2006 Fabien Potencier <fabien.potencier@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

lime_colorizer::style('ERROR', array('bg' => 'red', 'fg' => 'white', 'bold' => true));
lime_colorizer::style('INFO', array('fg' => 'green', 'bold' => true));
lime_colorizer::style('TRACE', array('fg' => 'green', 'bold' => true));
lime_colorizer::style('PARAMETER', array('fg' => 'cyan'));
lime_colorizer::style('COMMENT', array('fg' => 'yellow'));

lime_colorizer::style('GREEN_BAR', array('fg' => 'white', 'bg' => 'green', 'bold' => true));
lime_colorizer::style('RED_BAR', array('fg' => 'white', 'bg' => 'red', 'bold' => true));
lime_colorizer::style('YELLOW_BAR', array('fg' => 'black', 'bg' => 'yellow', 'bold' => true));
lime_colorizer::style('INFO_BAR', array('fg' => 'cyan', 'bold' => true));

lime_colorizer::style('DIFF_DELETED', array('fg' => 'red', 'bold' => true));
lime_colorizer::style('DIFF_ADDED', array('fg' => 'green', 'bold' => true));
