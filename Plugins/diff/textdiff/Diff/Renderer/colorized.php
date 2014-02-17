<?php
/**
 * "Inline" diff renderer.
 *
 * $Horde: framework/Text_Diff/Diff/Renderer/inline.php,v 1.4.10.16 2009/07/24 13:25:29 jan Exp $
 *
 * Copyright 2004-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you did
 * not receive this file, see http://opensource.org/licenses/lgpl-license.php.
 *
 * @author  Ciprian Popovici
 * @package Text_Diff
 */

/**
 * "Inline" diff renderer.
 *
 * This class renders diffs in the Wiki-style "inline" format.
 *
 * @author  Ciprian Popovici
 * @package Text_Diff
 */
class Text_Diff_Renderer_colorized extends Text_Diff_Renderer_inline {

    /**
     * Prefix for inserted text.
     */
    var $_ins_prefix = '';

    /**
     * Suffix for inserted text.
     */
    var $_ins_suffix = '';

    /**
     * Prefix for deleted text.
     */
    var $_del_prefix = '';

    /**
     * Suffix for deleted text.
     */
    var $_del_suffix = '';
    
    function _added($lines)
    {
        array_walk($lines, array(&$this, '_encode'));
        $lines[0] = $this->_ins_prefix . $lines[0];
        $lines[count($lines) - 1] .= $this->_ins_suffix;
        return cecho( '[' . $this->_lines($lines, ' ', false) . "|DIFF_ADDED]", true );
    }
    
    function _deleted($lines, $words = false)
    {
        array_walk($lines, array(&$this, '_encode'));
        $lines[0] = $this->_del_prefix . $lines[0];
        $lines[count($lines) - 1] .= $this->_del_suffix;
        return cecho( '[' . $this->_lines($lines, ' ', false) . "|DIFF_DELETED]", true );
    }
    
    function _changed($orig, $final)
    {
        /* If we've already split on words, don't try to do so again - just
         * display. */
        if ($this->_split_level == 'words') {
            $prefix = '';
            while ($orig[0] !== false && $final[0] !== false &&
                   substr($orig[0], 0, 1) == ' ' &&
                   substr($final[0], 0, 1) == ' ') {
                $prefix .= substr($orig[0], 0, 1);
                $orig[0] = substr($orig[0], 1);
                $final[0] = substr($final[0], 1);
            }
            return $prefix . $this->_deleted($orig) . $this->_added($final);
        }

        $text1 = implode("\n", $orig);
        $text2 = implode("\n", $final);

        /* Non-printing newline marker. */
        $nl = "\0";

        /* We want to split on word boundaries, but we need to
         * preserve whitespace as well. Therefore we split on words,
         * but include all blocks of whitespace in the wordlist. */
        $diff = new Text_Diff('native',
                              array($this->_splitOnWords($text1, $nl),
                                    $this->_splitOnWords($text2, $nl)));

        /* Get the diff in inline format. */
        $renderer = new Text_Diff_Renderer_colorized
            (array_merge($this->getParams(),
                         array('split_level' => 'words')));

        /* Run the diff and get the output. */
        return str_replace($nl, "\n", $renderer->render($diff)) . "\n";
    }
}
