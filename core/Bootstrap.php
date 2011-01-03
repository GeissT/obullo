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
 * @since           Version 1.0
 * @filesource
 * @license
 */
 
 /**
 * Obullo Bootstrap file.
 * Control Your Application Boot
 * 
 * @package         Obullo 
 * @subpackage      Obullo.core
 * @category        Front Controller
 * @version         1.0
 */  

Class CommonException extends Exception {};

//  Include application header files.
// -------------------------------------------------------------------- 
if( ! function_exists('ob_include_files'))
{
    function ob_include_files()
    {
        require (BASE .'constants'. DS .'db'. EXT);
        require (BASE .'constants'. DS .'file'. EXT);
        require (APP  .'config'. DS .'constants'. EXT);  // Your constants ..
        require (BASE .'core'. DS .'Ssc'. EXT);
        require (BASE .'core'. DS .'Registry'. EXT);
        require (BASE .'core'. DS .'Common'. EXT);
        require (BASE .'core'. DS .'Errors'. EXT);
        require (APP  .'config'. DS .'init'. EXT);      // Your system init functions ..
        
    }
}

//  Include header functions. 
// -------------------------------------------------------------------- 
if( ! function_exists('ob_set_headers'))
{
    function ob_set_headers()
    {
        // Kill magic quotes
        if ( ! is_php('5.3')) { @set_magic_quotes_runtime(0); }
                                  
        // Loaded system helpers
        if (config_item('log_threshold') > 0)
        {
            loaded_helper('log');
        }
        
        loaded_helper('input');
        loaded_helper('lang');
        loaded_helper('benchmark');        
    }
}

//  Run the application.
// --------------------------------------------------------------------    
if( ! function_exists('ob_system_run'))
{
    function ob_system_run()
    {
        $uri       = base_register('URI');
        $router    = base_register('Router');
        $output    = base_register('Output');
        $config    = base_register('Config'); 
        
        benchmark_mark('total_execution_time_start');
        benchmark_mark('loading_time_base_classes_start');
        
        // Check REQUEST uri if there is a Cached file exist
        if ($output->_display_cache($config, $uri) == TRUE) { exit; }
          
        _sanitize_globals(); // Initalize to input filter. ( Sanitize must be above the GLOBALS !! )
          
        $GLOBALS['d']   = $router->fetch_directory();   // Get requested directory
        $GLOBALS['s']   = $router->fetch_subfolder();   // Check subfolder exist
        $GLOBALS['c']   = $router->fetch_class();       // Get requested controller
        $GLOBALS['m']   = $router->fetch_method();      // Get requested method
        
        if($GLOBALS['s'] != '')
        {
            $page_uri = "{$GLOBALS['d']} / {$GLOBALS['s']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            // Check the sub controller exists or not
            if ( ! file_exists(DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT))
            {
                show_404($page_uri);
            }
            
            $controller = DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['s']. DS .$GLOBALS['c']. EXT;   
            $arg_slice  = 4;
        } 
        else 
        {
            $page_uri = "{$GLOBALS['d']} / {$GLOBALS['c']} / {$GLOBALS['m']}";
            
            // Check the controller exists or not
            if ( ! file_exists(DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['c']. EXT))
            {
                if(config_item('enable_query_strings') === TRUE) show_404($page_uri);
                
                throw new CommonException('Unable to load your default controller.Please make sure the controller specified in your Routes.php file is valid.');
            }
            
            $controller = DIR .$GLOBALS['d']. DS .'controllers'. DS .$GLOBALS['c']. EXT;
            $arg_slice  = 3;
        }
        
        require (BASE .'core'. DS .'Loader'. EXT);
        require (BASE .'core'. DS .'Controller'. EXT);
        require (BASE .'core'. DS .'Model'. EXT);
        
        // Set a mark point for benchmarking
        benchmark_mark('loading_time_base_classes_end');
        
        // Mark a start point so we can benchmark the controller
        benchmark_mark('execution_time_( '.$page_uri.' )_start');
        
        // call the controller.
        require ($controller);
        
        if ( ! class_exists($GLOBALS['c']) OR $GLOBALS['m'] == 'controller' 
              OR $GLOBALS['m'] == '_output'       // security fix.
              OR $GLOBALS['m'] == '_hmvc_output'  
              OR in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods('Controller')))
            )
        {
            show_404($page_uri);
        }
        
        // If Everyting ok Declare Called Controller !
        $OB = new $GLOBALS['c']();

        // Check method exist or not
        if ( ! in_array(strtolower($GLOBALS['m']), array_map('strtolower', get_class_methods($OB))))
        {
            show_404($page_uri);
        }
        
        // Call the requested method.                1       2       3
        // Any URI segments present (besides the directory/class/method) 
        // will be passed to the method for convenience
        call_user_func_array(array($OB, $GLOBALS['m']), array_slice($OB->uri->rsegments, $arg_slice));
        
        // Mark a benchmark end point
        benchmark_mark('execution_time_( '.$page_uri.' )_end');
        
        // Write Cache file if cache on ! and Send the final rendered output to the browser
        $output->_display();
            
    }
}

// Close the opened connections.
// --------------------------------------------------------------------  
if( ! function_exists('ob_system_close'))
{
    function ob_system_close()
    {
        $OB = this();
        
        // Close all PDO connections..        
        foreach(profiler_get('databases') as $db_name => $db_var)
        {
            $OB->{$db_var} = NULL;
        }
        
        // close all buffers.
        while (ob_get_level() > 0) { ob_end_flush(); }
    }
}
// END Bootstrap.php File

/* End of file Bootstrap.php
/* Location: ./obullo/core/Bootstrap.php */