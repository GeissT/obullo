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
 * @updated by         CJ Lazell
 * @license         public
 * @since           Version 1.0
 * @filesource
 * @license
 */

// ------------------------------------------------------------------------

/**
 * Obullo Task Helpers
 *
 * @package     Obullo
 * @subpackage  Helpers
 * @category    Helpers
 * @link
 */

/**
* Run Command Line Tasks
*
* @param  array $uri
* @return void
*/
if ( ! function_exists('task_run'))
{
    function task_run($uri, $debug = FALSE)
    {
        $uri    = explode('/', $uri);
        $module = array_shift($uri);
        
        $shell  = PHP_PATH .' '. FPATH .'/task.php '. $module .' '. implode('/', $uri) .' OB_TASK_REQUEST';

        if($debug)
        {
            $output = shell_exec($shell);
            echo "<pre>$output</pre>";
            
            log_me('debug', 'Task function output -> '. $output);
        }
        else   // continious task
        {
            shell_exec($shell.' > /dev/null &');
        }

        log_me('debug', 'Task function command -> '. $shell);
    }
}

/* End of file task.php */
/* Location: ./obullo/helpers/task.php */