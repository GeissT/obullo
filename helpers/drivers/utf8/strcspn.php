<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
 * 
 * @package         obullo    
 * @author          obullo.com
 * @since           Version 1.0.1
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * UTF8 strcspn
 *
 * @access  private
 * @param string $str
 * @param string $mask
 * @param int $offset
 * @param int $length 
 * @return  string
 */
if( ! function_exists('utf8_strcspn'))
{
    function utf8_strcspn($str, $mask, $offset = NULL, $length = NULL)
    {
        $utf8 = lib('ob/utf8');
        
        if ($str == '' OR $mask == '')
        {
           return 0; 
        }

        if ($utf8->is_ascii($str) AND $utf8->is_ascii($mask))
        {
            return ($offset === NULL) ? strcspn($str, $mask) : (($length === NULL) ? strcspn($str, $mask, $offset) : strcspn($str, $mask, $offset, $length));
        }

        if ($offset !== NULL OR $length !== NULL)
        {
            $str = $utf8->substr($str, $offset, $length);
        }

        // Escape these characters:  - [ ] . : \ ^ /
        // The . and : are escaped to prevent possible warnings about POSIX regex elements
        $mask = preg_replace('#[-[\].:\\\\^/]#', '\\\\$0', $mask);
        
        preg_match('/^[^'.$mask.']+/u', $str, $matches);

        return isset($matches[0]) ? $utf8->strlen($matches[0]) : 0;
    }
}

/* End of file strcspn.php */
/* Location: ./obullo/helpers/drivers/utf8/strcspn.php */