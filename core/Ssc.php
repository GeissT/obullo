<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         obullo    
 * @subpackage      Obullo.core    
 * @author          obullo.com
 * @copyright       Ersin Guvenc (c) 2009 - 2010.
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
/**
* Super Static Controller (SSC). (c) 2010.
* Control the Procedural (static) Functions.
* 
* @version  0.1 
* @version  0.2  Renamed ssc class name as Ssc. 
* 
* @author   Ersin Guvenc
*/
Class Ssc {
    
    private static $instance;
    
    public $_ng   = NULL; // la_ng
    public $_er   = NULL; // log_er
    public $_vi   = NULL; // vi_ew
    public $_put  = NULL; // in_put
    public $_ity  = NULL; // secur_ity 
    public $_sion = NULL; // ses_sion
    public $_mark = NULL; // bench_mark
    
    public $profiler_var = array(); // profiler variable
    
    public static function instance($new_instance = '')
    {
        if(is_object($new_instance))
        {
            self::$instance = $new_instance; 
        }
        
        if( ! isset(self::$instance))
        {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
}

// END Ssc Class

/* End of file Ssc.php */
/* Location: ./obullo/core/Ssc.php */