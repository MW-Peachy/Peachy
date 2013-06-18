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
class Text_Diff_Renderer_dualview extends Text_Diff_Renderer_inline {

    /**
     * Prefix for inserted text.
     */
    var $_ins_prefix = '@$(@IH@KW@Wwq2epw';

    /**
     * Suffix for inserted text.
     */
    var $_ins_suffix = 'P(R#$J:W*F@ej72oiet';

    /**
     * Prefix for deleted text.
     */
    var $_del_prefix = 'WOIRUW*EOukcsfGQ';

    /**
     * Suffix for deleted text.
     */
    var $_del_suffix = '*ROw2T){E@*jVWOw';
    
}
