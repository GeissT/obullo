<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009.
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Path Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @author      Ersin Guvenc
 * @link        
 */

// ------------------------------------------------------------------------

/**
* Set Realpath
*
* @access	public
* @param	string
* @param	bool	checks to see if the path exists
* @return	string
*/	
function set_realpath($path, $check_existance = FALSE)
{
	// Security check to make sure the path is NOT a URL.  No remote file inclusion!
	if (preg_match("#^(http:\/\/|https:\/\/|www\.|ftp|[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})#i", $path))
	{
		throw new CommonException('The path you submitted must be a local server path, not a URL');
	}

	// Resolve the path
	if (function_exists('realpath') AND @realpath($path) !== FALSE)
	{
		$path = realpath($path).'/';
	}

	// Add a trailing slash
	$path = preg_replace("#([^/])/*$#", "\\1/", $path);

	// Make sure the path exists
	if ($check_existance == TRUE)
	{
		if ( ! is_dir($path))
		{
			throw new CommonException('Not a valid path: '.$path);
		}
	}

	return $path;
}

/* End of file path.php */
/* Location: ./base/helpers/path.php */
?>