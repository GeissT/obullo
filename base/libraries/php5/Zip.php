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
 * @filesource
 * @license
 */

Class ZipException extends CommonException {} 
 
// ------------------------------------------------------------------------

/**
 * Zip Compression Class
 *
 * This class is based on a library I found at Zend:
 * http://www.zend.com/codex.php?id=696&single=1
 *
 * The original library is a little rough around the edges so I
 * refactored it and added several additional methods -- Rick Ellis
 *
 * @package       Obullo
 * @subpackage    Libraries
 * @category      Libraries
 * @author        Ersin Guvenc
 * @link          
 */
Class zip_CORE implements PHP5_Library {

    public $zipdata     = '';
    public $directory   = '';
    public $entries     = 0;
    public $file_num    = 0;
    public $offset      = 0;
    public $now;

    private static $instance;
    
    public static function instance()
    {
       if(! (self::$instance instanceof self))
       {
            self::$instance = new self();
       } 
       
       return self::$instance;
    }
    
    // --------------------------------------------------------------------
    
    public function init()
    {
        log_message('debug', "Zip Compression Class Initialized");
    }

    // --------------------------------------------------------------------

    // --------------------------------------------------------------------

    /**
     * Add Directory
     *
     * Lets you add a virtual directory into which you can place files.
     *
     * @access    public
     * @param    mixed    the directory name. Can be string or array
     * @return    void
     */
    public function add_dir($directory)
    {
        foreach ((array)$directory as $dir)
        {
            if ( ! preg_match("|.+/$|", $dir))
            {
                $dir .= '/';
            }

            $dir_time = $this->_get_mod_time($dir);

            $this->_add_dir($dir, $dir_time['file_mtime'], $dir_time['file_mdate']);
        }
    }

    // --------------------------------------------------------------------    

    /**
     *    Get file/directory modification time
     *    
     *    If this is a newly created file/dir, we will set the time to 'now'
     *
     *    @param string    path to file
     *    @return array     filemtime/filemdate
     */
    public function _get_mod_time($dir)
    {
        // filemtime() will return false, but it does raise an error.
        $date = (@filemtime($dir)) ? filemtime($dir) : getdate($this->now); 

        $time['file_mtime'] = ($date['hours'] << 11) + ($date['minutes'] << 5) + $date['seconds'] / 2;
        $time['file_mdate'] = (($date['year'] - 1980) << 9) + ($date['mon'] << 5) + $date['mday'];
        
        return $time;
    }

    // --------------------------------------------------------------------

    /**
     * Add Directory
     *
     * @access    private
     * @param    string    the directory name
     * @return    void
     */
    public function _add_dir($dir, $file_mtime, $file_mdate)
    {        
        $dir = str_replace("\\", "/", $dir);

        $this->zipdata .=
            "\x50\x4b\x03\x04\x0a\x00\x00\x00\x00\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', 0) // crc32
            .pack('V', 0) // compressed filesize
            .pack('V', 0) // uncompressed filesize
            .pack('v', strlen($dir)) // length of pathname
            .pack('v', 0) // extra field length
            .$dir
            // below is "data descriptor" segment
            .pack('V', 0) // crc32
            .pack('V', 0) // compressed filesize
            .pack('V', 0); // uncompressed filesize

        $this->directory .=
            "\x50\x4b\x01\x02\x00\x00\x0a\x00\x00\x00\x00\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V',0) // crc32
            .pack('V',0) // compressed filesize
            .pack('V',0) // uncompressed filesize
            .pack('v', strlen($dir)) // length of pathname
            .pack('v', 0) // extra field length
            .pack('v', 0) // file comment length
            .pack('v', 0) // disk number start
            .pack('v', 0) // internal file attributes
            .pack('V', 16) // external file attributes - 'directory' bit set
            .pack('V', $this->offset) // relative offset of local header
            .$dir;

        $this->offset = strlen($this->zipdata);
        $this->entries++;
    }
    
    // --------------------------------------------------------------------

    /**
     * Add Data to Zip
     *
     * Lets you add files to the archive. If the path is included
     * in the filename it will be placed within a directory.  Make
     * sure you use add_dir() first to create the folder.
     *
     * @access    public
     * @param    mixed
     * @param    string
     * @return    void
     */    
    public function add_data($filepath, $data = NULL)
    {    
        if (is_array($filepath))
        {
            foreach ($filepath as $path => $data)
            {
                $file_data = $this->_get_mod_time($path);    

                $this->_add_data($path, $data, $file_data['file_mtime'], $file_data['file_mdate']);
            }
        }
        else
        {
            $file_data = $this->_get_mod_time($filepath);
            
            $this->_add_data($filepath, $data, $file_data['file_mtime'], $file_data['file_mdate']);
        }
    }

    // --------------------------------------------------------------------

    /**
     * Add Data to Zip
     *
     * @access   private
     * @param    string    the file name/path
     * @param    string    the data to be encoded
     * @return   void
     */    
    private function _add_data($filepath, $data, $file_mtime, $file_mdate)
    {
        $filepath = str_replace("\\", "/", $filepath);

        $uncompressed_size = strlen($data);
        $crc32  = crc32($data);

        $gzdata = gzcompress($data);
        $gzdata = substr($gzdata, 2, -4);
        $compressed_size = strlen($gzdata);

        $this->zipdata .=
            "\x50\x4b\x03\x04\x14\x00\x00\x00\x08\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', $crc32)
            .pack('V', $compressed_size)
            .pack('V', $uncompressed_size)
            .pack('v', strlen($filepath)) // length of filename
            .pack('v', 0) // extra field length
            .$filepath
            .$gzdata; // "file data" segment

        $this->directory .=
            "\x50\x4b\x01\x02\x00\x00\x14\x00\x00\x00\x08\x00"
            .pack('v', $file_mtime)
            .pack('v', $file_mdate)
            .pack('V', $crc32)
            .pack('V', $compressed_size)
            .pack('V', $uncompressed_size)
            .pack('v', strlen($filepath)) // length of filename
            .pack('v', 0) // extra field length
            .pack('v', 0) // file comment length
            .pack('v', 0) // disk number start
            .pack('v', 0) // internal file attributes
            .pack('V', 32) // external file attributes - 'archive' bit set
            .pack('V', $this->offset) // relative offset of local header
            .$filepath;

        $this->offset = strlen($this->zipdata);
        $this->entries++;
        $this->file_num++;
    }
    
    // --------------------------------------------------------------------

    /**
     * Read the contents of a file and add it to the zip
     *
     * @access    public
     * @return    bool
     */    
    public function read_file($path, $preserve_filepath = FALSE)
    {
        if ( ! file_exists($path))
        {
            return FALSE;
        }

        if (FALSE !== ($data = file_get_contents($path)))
        {
            $name = str_replace("\\", "/", $path);
            
            if ($preserve_filepath === FALSE)
            {
                $name = preg_replace("|.*/(.+)|", "\\1", $name);
            }

            $this->add_data($name, $data);
            return TRUE;
        }
        return FALSE;
    }

    // ------------------------------------------------------------------------
    
    /**
     * Read a directory and add it to the zip.
     *
     * This function recursively reads a folder and everything it contains (including
     * sub-folders) and creates a zip based on it.  Whatever directory structure
     * is in the original file path will be recreated in the zip file.
     *
     * @access    public
     * @param    string    path to source
     * @return    bool
     */
    public function read_dir($path, $preserve_filepath = TRUE, $root_path = NULL)
    {
        if ( ! $fp = @opendir($path))
        {
            return FALSE;
        }

        // Set the original directory root for child dir's to use as relative
        if ($root_path === NULL)
        {
            $root_path = dirname($path).'/';
        }

        while (FALSE !== ($file = readdir($fp)))
        {
            if(substr($file, 0, 1) == '.')
            {
                continue;
            }

            if (@is_dir($path.$file))
            {
                $this->read_dir($path.$file."/", $preserve_filepath, $root_path);
            }
            else
            {
                if (FALSE !== ($data = file_get_contents($path.$file)))
                {
                    $name = str_replace("\\", "/", $path);

                    if ($preserve_filepath === FALSE)
                    {
                        $name = str_replace($root_path, '', $name);
                    }

                    $this->add_data($name.$file, $data);
                }
            }
        }

        return TRUE;
    }

    // --------------------------------------------------------------------

    /**
     * Get the Zip file
     *
     * @access    public
     * @return    binary string
     */    
    public function get_zip()
    {
        // Is there any data to return?
        if ($this->entries == 0)
        {
            return FALSE;
        }

        $zip_data = $this->zipdata;
        $zip_data .= $this->directory."\x50\x4b\x05\x06\x00\x00\x00\x00";
        $zip_data .= pack('v', $this->entries); // total # of entries "on this disk"
        $zip_data .= pack('v', $this->entries); // total # of entries overall
        $zip_data .= pack('V', strlen($this->directory)); // size of central dir
        $zip_data .= pack('V', strlen($this->zipdata)); // offset to start of central dir
        $zip_data .= "\x00\x00"; // .zip file comment length

        return $zip_data;
    }
    
    // --------------------------------------------------------------------

    /**
     * Write File to the specified directory
     *
     * Lets you write a file
     *
     * @access   public
     * @param    string    the file name
     * @return   bool
     */    
    public function archive($filepath)
    {
        if ( ! ($fp = @fopen($filepath, FOPEN_WRITE_CREATE_DESTRUCTIVE)))
        {
            return FALSE;
        }

        flock($fp, LOCK_EX);    
        fwrite($fp, $this->get_zip());
        flock($fp, LOCK_UN);
        fclose($fp);

        return TRUE;    
    }

    // --------------------------------------------------------------------

    /**
     * Download
     *
     * @access   public
     * @param    string    the file name
     * @param    string    the data to be encoded
     * @return   bool
     */
    public function download($filename = 'backup.zip')
    {
        if ( ! preg_match("|.+?\.zip$|", $filename))
        {
            $filename .= '.zip';
        }
        
        loader::base_helper('download');

        $get_zip = $this->get_zip();

        $zip_content =& $get_zip;

        force_download($filename, $zip_content);
    }

    // --------------------------------------------------------------------

    /**
     * Initialize Data
     *
     * Lets you clear current zip data.  Useful if you need to create
     * multiple zips with different data.
     *
     * @access    public
     * @return    void
     */        
    public function clear_data()
    {
        $this->zipdata      = '';
        $this->directory    = '';
        $this->entries      = 0;
        $this->file_num     = 0;
        $this->offset       = 0;
    }
    
}
/* End of file Zip.php */
/* Location: ./base/libraries/php5/Zip.php */