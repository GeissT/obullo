<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 HMVC Based Scalable Software.
 *
 * @package         obullo
 * @subpackage      Obullo.core
 * @author          obullo.com
 * @copyright       Obull Team
 * @filesource
 * @license
 */

 /**
 * Router Class
 * Parses URIs and determines routing
 *
 * @package     Obullo
 * @subpackage  obullo
 * @category    URI
 * @author      Obullo Team.
 * @link
 */
Class OB_Router {

    public $uri;
    public $config;
    public $hmvc                = FALSE;
    public $hmvc_response       = '';
    public $routes              = array();
    public $error_routes        = array();
    public $class               = '';
    public $method              = 'index';
    public $directory           = '';
    public $uri_protocol        = 'auto';
    public $default_controller;

    /**
    * Constructor
    * Runs the route mapping function.
    *
    * @return void
    */
    public function __construct()
    {
        // Warning :
        // 
        // Don't load any library in this Class because of Obullo use 
        // the Router Class at Bootstrap loading level. When you try load any library
        // you will get a Fatal Error.
        
        $this->uri = lib('ob/Uri');

        $this->_detect_sub_module();
        $this->_set_routing();         
        
        log_me('debug', 'Router Class Initialized', false, true); // core level log
    }
    
    //---------------------------------------------------------------------
    
    /**
    * Detec possible sub modules and
    * set routing rules at the the top level.
    * 
    * @return void
    */
    public function _detect_sub_module()
    {
        ############## Clone URI Object ############## 
        
        $uri = clone lib('ob/Uri');
        
        ############## Clone URI Object ############## 
        
        $uri->_fetch_uri_string();
        $uri->_parse_request_uri();
        $uri->_remove_url_suffix();
        $uri->_explode_segments();
        $uri->_parse_sub_module();
        
        $routes = get_config('routes'); //  Get the application routes.
        
        if (config('enable_query_strings') === TRUE AND isset($_GET[config('submodule_trigger')]) AND $this->hmvc == FALSE)
        {
            $sub_module     = $uri->fetch_sub_module();
            $module_segment = trim($uri->_filter_uri($_GET[config('submodule_trigger')]));
        } 
        else 
        {
            $sub_module     = $uri->fetch_sub_module();
            $module_segment = $uri->segment(0);
        }
        
        // Get possible sub.modules directory
        $GLOBALS['sub_path'] = ($sub_module == '') ? '' : 'sub.'.$sub_module. DS .SUB_MODULES;
        $module = ($module_segment == '' AND $module_segment != FALSE) ? $routes['default_controller']: $module_segment;

        if(strpos(trim($module, '/'), '/') > 0) // Check possible module route slash
        {
           $default = explode('/' ,$module);
           $module  = $default[0];
        }
        
        if($sub_module != '') // Sub Module
        { 
            if( file_exists(MODULES .'sub.'.$sub_module. DS .'config'. DS .'routes'. EXT))
            {
                $sub_module_routes = get_static('routes', '', MODULES .'sub.'.$sub_module. DS .'config');
            }
            
            if(isset($sub_module_routes) AND is_array($sub_module_routes))
            {
                $routes = array_merge($routes, $sub_module_routes);
                
                log_me('debug', '[ '.ucfirst($sub_module).' ]: Sub-Module and Application routes Merged', false, true);
            }
        }
        
        if(file_exists(MODULES .$GLOBALS['sub_path'].$module. DS .'config'. DS .'routes'. EXT))
        {   
            $module_routes = get_static('routes', '', MODULES .$GLOBALS['sub_path'].$module. DS .'config');
            
            if(isset($module_routes) AND is_array($module_routes))
            {
                $routes = array_merge($routes, $module_routes);
                
                log_me('debug', '[ '.ucfirst($module).' ]: Module Router Settings Initialized', false, true);
            }
        }
        
        // Clean Unnecessary slashes !!
        // $routes = array_map(create_function( '$a', 'return trim($a, "/");' ), $routes);
        
        ##############
        
        $uri->clear();        // Clear URI class variables.
        
        ##############
        
        $this->routes = ( ! isset($routes) OR ! is_array($routes)) ? array() : $routes;
        unset($routes);

        $this->method = $this->routes['index_method'];
    }
    
    // --------------------------------------------------------------------
    
    /**
    * When we use HMVC we need to Clean
    * all data.
    *
    * @return  void
    */
    public function clear()
    {
        $this->uri                 = lib('ob/Uri');   // reset cloned URI object.
        $this->config              = '';
        $this->hmvc                = FALSE;
        $this->hmvc_response       = '';
        // $this->routes           // route config shouln't be reset there cause some isset errors
        $this->error_routes        = array();
        $this->class               = '';
        $this->method              = 'index';
        $this->directory           = '';
        $this->uri_protocol        = 'auto';
        $this->default_controller  = '';
        
        $GLOBALS['sub_path']       = '';    // Reset sub module path
    }

    // --------------------------------------------------------------------

    /**
    * Clone URI object for HMVC Requests, When we
    * use HMVC we use $this->uri = clone lib('ob/Uri');
    * that means we say to Router class when Clone word used in HMVC library
    * use cloned URI object instead of orginal ( ersin ).
    */
    public function __clone()
    {
        $this->uri = clone $this->uri;
    }

    // --------------------------------------------------------------------

    /**
    * Set the route mapping
    *
    * This function determines what should be served based on the URI request,
    * as well as any "routes" that have been set in the routing config file.
    *
    * @access    private
    * @author    Ersin Guvenc
    * @version   0.1
    * @return    void
    */
    public function _set_routing()
    {
        if($this->hmvc == FALSE)    // GET request valid for standart router requests not HMVC.
        {
            // Are query strings enabled in the config file?
            // If so, we're done since segment based URIs are not used with query strings.
            if (config('enable_query_strings') === TRUE AND isset($_GET[config('controller_trigger')]) AND 
                    isset($_GET[config('directory_trigger')]))
            {
                $this->set_directory(trim($this->uri->_filter_uri($_GET[config('directory_trigger')])));
                $this->set_class(trim($this->uri->_filter_uri($_GET[config('controller_trigger')])));

                if (isset($_GET[config('function_trigger')]))
                {
                    $this->set_method(trim($this->uri->_filter_uri($_GET[config('function_trigger')])));
                }

                return;
            }
        }

        // Set the default controller so we can display it in the event
        // the URI doesn't correlated to a valid controller.
        $this->default_controller = ( ! isset($this->routes['default_controller']) OR $this->routes['default_controller'] == '') ? FALSE : strtolower($this->routes['default_controller']);

        // Fetch the complete URI string
        $this->uri->_fetch_uri_string();

        // Is there a URI string? If not, the default controller specified in the "routes" file will be shown.
        if ($this->uri->uri_string == '')
        {
            if ($this->default_controller === FALSE)
            {
                if($this->hmvc)
                {
                    $this->hmvc_response = 'Hmvc unable to determine what should be displayed. A default route has not been specified in the routing file.';
                    return FALSE;
                }

                show_error('Unable to determine what should be displayed. A default route has not been specified in the routing file.', 404);
            }

            // Turn the default route into an array.  We explode it in the event that
            // the controller is located in a subfolder
            $segments = $this->_validate_request(explode('/', $this->default_controller));

            if($this->hmvc)
            {
                if($segments === FALSE)
                {
                    return FALSE;
                }
            }

            $this->set_class($segments[1]);
            $this->set_method($this->routes['index_method']);  // index

            // Assign the segments to the URI class
            $this->uri->rsegments = $segments;

            // re-index the routed segments array so it starts with 1 rather than 0
            // $this->uri->_reindex_segments();

            log_me('debug', "No URI present. Default controller set.", false, true);
            
            return;
        }
        
        unset($this->routes['default_controller']);

        // Do we need to remove the URL suffix?
        $this->uri->_remove_url_suffix();

        // Compile the segments into an array
        $this->uri->_explode_segments();
        
        // Parse possible sub module
        $this->uri->_parse_sub_module();
        
        // Parse any custom routing that may exist
        $this->_parse_routes();

        // Re-index the segment array so that it starts with 1 rather than 0
        // $this->uri->_reindex_segments();
    }

    // --------------------------------------------------------------------

    /**
    * Set the Route
    *
    * This function takes an array of URI segments as
    * input, and sets the current class/method
    *
    * @access   private
    * @author   Ersin Guvenc
    * @param    array
    * @param    bool
    * @version  0.1
    * @version  0.2 Changed $segments[0] as $segments[1]  and
    *           $segments[1] as $segments[2]
    * @return   void
    */
    public function _set_request($segments = array())
    {
        $segments = $this->_validate_request($segments);
        
        if (count($segments) == 0)
        return;

        $this->set_class($segments[1]);

        if (isset($segments[2]))
        {
           // A standard method request
           $this->set_method($segments[2]);
        }
        else
        {
            // This lets the "routed" segment array identify that the default
            // index method is being used.
            $segments[2] = $this->routes['index_method'];
        }
        // Update our "routed" segment array to contain the segments.
        // Note: If there is no custom routing, this array will be
        // identical to $this->uri->segments
        $this->uri->rsegments = $segments;
    }

    // --------------------------------------------------------------------

    /**
    * Validates the supplied segments.  Attempts to determine the path to
    * the controller.
    *
    * $segments[0] = module
    * $segments[1] = controller
    *
    *       0      1           2
    * module / controller /  method  /
    *
    * @author   Ersin Guvenc
    * @author   CJ Lazell
    * @access   private
    * @param    array
    * @version  Changed segments[0] as segments[1]
    *           added directory set to segments[0]
    * @return   array
    */
    public function _validate_request($segments)
    {   
        if( ! isset($segments[0]) ) return $segments;
        
        $folder = 'controllers';
        
        if(defined('CMD') AND $this->hmvc == FALSE)  // Command Line Request
        {
            if(is_dir(MODULES .$GLOBALS['sub_path'].$segments[0]. DS .'tasks')) 
            {                   
                $folder = 'tasks'; 
            }
            else
            {
                array_unshift($segments, 'tasks');
            }
        }
                                        
        if (is_dir(MODULES .$GLOBALS['sub_path'].$segments[0]) OR defined('CMD'))  // Check module
        {
            $ROOT = MODULES .$GLOBALS['sub_path'];
            
            $this->set_directory($segments[0]);

            if( ! empty($segments[1]))
            {                    
                if (file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$segments[1]. EXT))
                {
                    return $segments; 
                }
            }
            
            // Merge Segments
            if (file_exists($ROOT .$this->fetch_directory(). DS .$folder. DS .$this->fetch_directory(). EXT))
            {
                array_unshift($segments, $this->fetch_directory());

                if( empty($segments[2]) )
                {
                    $segments[2] = $this->routes['index_method'];
                }

                return $segments;
            }
        }

        if($this->hmvc)
        {
            $this->hmvc_response = 'Hmvc request not found.';

            log_me('debug', 'Hmvc request not found.', false, true);
            
            return FALSE;
        }

        // If we've gotten this far it means that the URI does not correlate to a valid
        // controller class.  We will now see if there is an override
        if ( ! empty($this->routes['404_override']))
        {
            $x = explode('/', $this->routes['404_override']);

            $this->set_directory($x[0]);
            $this->set_class($x[1]);
            $this->set_method(isset($x[2]) ? $x[2] : 'index');

            return $x;
        }

        $error_page = (isset($segments[1])) ? $segments[0].'/'.$segments[1] : $segments[0];

        show_404($error_page);
    }
               
    // --------------------------------------------------------------------
    
    /**
    * Parse Routes
    *
    * This function matches any routes that may exist in
    * the config/routes.php file against the URI to
    * determine if the class/method need to be remapped.
    *
    * @access    private
    * @return    void
    */
    public function _parse_routes()
    { 
        // Do we even have any custom routing to deal with?
        // There is a default scaffolding trigger, so we'll look just for 1
        if (count($this->routes) == 1)
        {
            $this->_set_request($this->uri->segments);
            return;
        }
        
        //---------- sub.module support ---------
        
        $segments = $this->uri->segments;
     
        if($this->uri->fetch_sub_module() != '')
        {
            // Turn the segment array into a URI string
            array_unshift($segments, $this->uri->fetch_sub_module());
            $uri  = implode('/', $segments);
        } 
        else
        {
            $uri = implode('/', $this->uri->segments);
        }

        //---------- sub.module support ---------
        
        // Is there a literal match?  If so we're done
        if (isset($this->routes[$uri]))
        { 
            $this->_set_request(explode('/', $this->routes[$uri]));
            return;
        }

        // Loop through the route array looking for wild-cards
        foreach ($this->routes as $key => $val)
        {
            // Convert wild-cards to RegEx
            $key = str_replace(':any', '.+', str_replace(':num', '[0-9]+', $key));

            // Does the RegEx match?
            if (preg_match('#^'.$key.'$#', $uri))
            {
                // Do we have a back-reference?
                if (strpos($val, '$') !== FALSE AND strpos($key, '(') !== FALSE)
                {
                    $val = preg_replace('#^'.$key.'$#', $val, $uri);
                }

                $this->_set_request(explode('/', $val));
                return;
            }
        }
        
        // If we got this far it means we didn't encounter a
        // matching route so we'll set the site default route
        $this->_set_request($this->uri->segments);
    }

    // --------------------------------------------------------------------

    /**
    * Set the class name
    *
    * @access    public
    * @param     string
    * @return    void
    */
    public function set_class($class)
    {
        $this->class = $class;
    }

    // --------------------------------------------------------------------

    /**
    * Fetch the current class
    *
    * @access    public
    * @return    string
    */
    public function fetch_class()
    {
        return $this->class;
    }

    // --------------------------------------------------------------------

    /**
    *  Set the method name
    *
    * @access    public
    * @param     string
    * @return    void
    */
    public function set_method($method)
    {
        $this->method = $method;
    }

    // --------------------------------------------------------------------

    /**
    *  Fetch the current method
    *
    * @access    public
    * @return    string
    */
    public function fetch_method()
    {
        if ($this->method == $this->fetch_class())
        {
            return $this->routes['index_method'];
        }

        return $this->method;
    }

    // --------------------------------------------------------------------

    /**
    *  Set the directory name
    *
    * @access   public
    * @param    string
    * @return   void
    */
    public function set_directory($dir)
    {
        $this->directory = $dir.'';  // Obullo changes..
    }

    // --------------------------------------------------------------------

    /**
    * Fetch the directory (if any) that contains the requested controller class
    *
    * @access    public
    * @return    string
    */
    public function fetch_directory()
    {
        return $this->directory;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Check Router Request Is Hmvc.
    * 
    * @return boolean
    */
    public function is_hmvc()
    {
        return $this->hmvc;
    }
    
}
// END Router Class

/* End of file Router.php */
/* Location: ./obullo/libraries/core/Router.php */