<?php defined('BASE') or die('No direct script access.');
/**
 * UTF8::strrev
 *
 * @package    Obullo
 * @author     Kohana Team
 * @copyright  (c) 2007-2011 Kohana Team
 * @copyright  (c) 2005 Harry Fuecks
 * @license    http://www.gnu.org/licenses/old-licenses/lgpl-2.1.txt
 */
function utf8_strrev($str)
{
	if (UTF8::is_ascii($str))
		return strrev($str);

	preg_match_all('/./us', $str, $matches);
	return implode('', array_reverse($matches[0]));
}