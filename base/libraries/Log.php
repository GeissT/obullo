<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 * @package         obullo       
 * @author          obullo.com
 * @copyright       Ersin Güvenç (c) 2009.
 * @filesource
 * @license
 */
 
Class LogException extends CommonException {}  

/**
 * Logging Class
 *
 * @package     Obullo
 * @subpackage  Libraries
 * @category    Libraries
 * @author      Ersin Güvenç
 * @link        
 */
class OB_Log {

    public $log_path;
    public $_threshold  = 1;
    public $_date_fmt   = 'Y-m-d H:i:s';
    public $_enabled    = TRUE;
    public $_levels     = array('ERROR' => '1', 'DEBUG' => '2',  'INFO' => '3', 'ALL' => '4');

    /**
     * Constructor
     *
     * @access    public
     */
    public function __construct()
    {
        $config = get_config();
        
        $this->log_path = ($config['log_path'] != '') ? $config['log_path'] : APP.'system'.DS.'logs/';
        
        if ( ! is_dir($this->log_path) OR ! is_really_writable($this->log_path))
        {
            $this->_enabled = FALSE;
        }
        
        if (is_numeric($config['log_threshold']))
        {
            $this->_threshold = $config['log_threshold'];
        }
            
        if ($config['log_date_format'] != '')
        {
            $this->_date_fmt = $config['log_date_format'];
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Write Log File
     *
     * Generally this function will be called using the global log_message() function
     *
     * @access   public
     * @param    string    the error level
     * @param    string    the error message
     * @param    bool    whether the error is a native PHP error
     * @return   bool
     */        
    public function write_log($level = 'error', $msg, $php_error = FALSE)
    {        
        if ($this->_enabled === FALSE)
        {
            return FALSE;
        }
    
        $level = strtoupper($level);
        
        if ( ! isset($this->_levels[$level]) OR ($this->_levels[$level] > $this->_threshold))
        {
            return FALSE;
        }
    
        $filepath = $this->log_path.'log-'.date('Y-m-d').EXT;
        $message  = '';
        
        if ( ! file_exists($filepath))
        {
            $message .= "<"."?php  if ( ! defined('BASE')) exit('Access Denied!'); ?".">\n\n";
        }
            
        if ( ! $fp = @fopen($filepath, FOPEN_WRITE_CREATE))
        {
            return FALSE;
        }

        $message .= $level.' '.(($level == 'INFO') ? ' -' : '-').' '.date($this->_date_fmt). ' --> '.$msg."\n";
        
        flock($fp, LOCK_EX);    
        fwrite($fp, $message);
        flock($fp, LOCK_UN);
        fclose($fp);
    
        @chmod($filepath, FILE_WRITE_MODE);         
        return TRUE;
    }

}
// END Log Class

/* End of file Log.php */
/* Location: ./base/base/Log.php */