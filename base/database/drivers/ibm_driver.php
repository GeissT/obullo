<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 * 
 *
 * @package         Obullo
 * @author          Obullo.com  
 * @subpackage      Base.database        
 * @copyright       Copyright (c) 2009 Ersin Güvenç.
 * @license         public
 * @since           Version 1.0
 * @filesource
 */ 
// ------------------------------------------------------------------------

//require(BASE.'database'.DS.'DBAdapter'.EXT); 

/**
 * IBM DB2 Database Adapter Class
 *
 * @package       Obullo
 * @subpackage    Drivers
 * @category      Database
 * @author        Ersin Güvenç
 * @author        Drew Harvey
 * @link                              
 */

Class Obullo_DB_Driver_Ibm extends OB_DBAdapter
{
    /**
    * The character used for escaping
    * 
    * @var string
    */
    public $_escape_char = '';
    
    
    // clause and character used for LIKE escape sequences - not used in MySQL
    // http://publib.boulder.ibm.com/infocenter/dzichelp/v2r2/index.jsp?topic=/com.ibm.db29.doc.odbc/db2z_likesc.htm
    // same as ODBC
    public $_like_escape_str = " {escape '%s'} ";
    public $_like_escape_chr = '!';     
     
    public function __construct($param, $db_var = 'db')
    {   
        parent::__construct($param, $db_var);
    }
    
    
    /**
    * Connect to PDO
    * 
    * @author   Ersin Güvenç 
    * @param    string $dsn  Dsn
    * @param    string $user Db username
    * @param    mixed  $pass Db password
    * @param    array  $options Db Driver options
    * @link     http://www.php.net/manual/en/ref.pdo-dblib.connection.php
    * @return   void
    */
    public function _connect()
    {
        // If connection is ok .. not need to again connect..
        if ($this->_conn) { return; }
        
        $port = empty($this->dbh_port) ? '' : 'PORT='.$this->dbh_port.';';
        $dsn  = empty($this->dsn) ? 'ibm:DRIVER={IBM DB2 ODBC DRIVER};DATABASE='.$this->database.';HOSTNAME='.$this->hostname.';'.$port.'PROTOCOL=TCPIP;' : $this->dsn; 
        
        $this->_conn = $this->pdo_connect($dsn, $this->username, $this->password, $this->options);
        
        // We set exception attribute for always showing the pdo exceptions errors. (ersin)
        $this->_conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
    } 

    // --------------------------------------------------------------------
    
    /**
     * Escape the SQL Identifiers
     *
     * This function escapes column and table names
     *
     * @access    private
     * @param    string
     * @return    string
     */
    public function _escape_identifiers($item)
    {
        if ($this->_escape_char == '')
        {
            return $item;
        }

        foreach ($this->_reserved_identifiers as $id)
        {
            if (strpos($item, '.'.$id) !== FALSE)
            {
                $str = $this->_escape_char. str_replace('.', $this->_escape_char.'.', $item);  
                
                // remove duplicates if the user already included the escape
                return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
            }        
        }
        
        if (strpos($item, '.') !== FALSE)
        {
            $str = $this->_escape_char.str_replace('.', $this->_escape_char.'.'.$this->_escape_char, $item).$this->_escape_char;            
        }
        else
        {
            $str = $this->_escape_char.$item.$this->_escape_char;
        }
    
        // remove duplicates if the user already included the escape
        return preg_replace('/['.$this->_escape_char.']+/', $this->_escape_char, $str);
    }
            
    // --------------------------------------------------------------------
    
    /**
     * Escape String
     *
     * @access    public
     * @param    string
     * @param    bool    whether or not the string will be used in a LIKE condition
     * @return    string
     */
    public function escape_str($str, $like = FALSE)
    {
        if (is_array($str))
        {
            foreach($str as $key => $val)
            {
                $str[$key] = $this->escape_str($val, $like);
            }
           
           return $str;
        }

        $str = $this->quote(ob::instance()->input->_remove_invisible_characters($str), PDO::PARAM_STR);
        
        // escape LIKE condition wildcards
        if ($like === TRUE)
        {
            $str = str_replace( array('%', '_', $this->_like_escape_chr),
                                array($this->_like_escape_chr.'%', $this->_like_escape_chr.'_', $this->_like_escape_chr.$this->_like_escape_chr),
                                $str);
        }
        
        return $str;
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Escape Table Name
     *
     * This function adds backticks if the table name has a period
     * in it. Some DBs will get cranky unless periods are escaped
     *
     * @access   private
     * @param    string    the table name
     * @return   string
     */
    public function _escape_table($table)
    {
        if (stristr($table, '.'))
        {
            $table = preg_replace("/\./", "`.`", $table);
        }

        return $table;
    }
    
    /**
     * From Tables
     *
     * This function implicitly groups FROM tables so there is no confusion
     * about operator precedence in harmony with SQL standards
     *
     * @access    public
     * @param    type
     * @return    type
     */
    public function _from_tables($tables)
    {
        if ( ! is_array($tables))
        {
            $tables = array($tables);
        }

        return ' '.implode(', ', $tables).' ';
    }

    // --------------------------------------------------------------------
    
    /**
     * Insert statement
     *
     * Generates a platform-specific insert string from the supplied data
     *
     * @access    public
     * @param    string    the table name
     * @param    array    the insert keys
     * @param    array    the insert values
     * @return    string
     */
    public function _insert($table, $keys, $values)
    {
        return "INSERT INTO " . $this->_escape_table($table) . " (".implode(', ', $keys).") VALUES (".implode(', ', $values).")";
    }
    
    // --------------------------------------------------------------------
    
    
    /**
     * Delete statement
     *
     * Generates a platform-specific delete string from the supplied data
     *
     * @access   public
     * @param    string    the table name
     * @param    array    the where clause
     * @return   string
     */
    public function _delete($table, $where = array(), $like = array(), $limit = FALSE)
    {
        return "DELETE FROM ".$this->_escape_table($table)." WHERE ".implode(" ", $where);
    }
    
    // --------------------------------------------------------------------
    
    /**
     * Update statement
     *
     * Generates a platform-specific update string from the supplied data
     *
     * @access   public
     * @param    string    the table name
     * @param    array    the update data
     * @param    array    the where clause
     * @return   string
     */
    public function _update($table, $values, $where, $orderby = array(), $limit = FALSE)
    {
        foreach($values as $key => $val)
        {
            $valstr[] = $key." = ".$val;
        }

        return "UPDATE ".$this->_escape_table($table)." SET ".implode(', ', $valstr)." WHERE ".implode(" ", $where);
    }

    // --------------------------------------------------------------------

    /**
     * Limit string
     *
     * Generates a platform-specific LIMIT clause
     *
     * @access   public
     * @param    string    the sql query string
     * @param    integer   the number of rows to limit the query to
     * @param    integer   the offset value
     * @return   string
     */
    public function _limit($sql, $limit, $offset = 0)
    {
        if ($offset == 0)
        {
            $offset = '';
        }
        else
        {
            $offset .= ", ";
        }

        return $sql."RETURN FIRST  " . $limit . " ROWS ONLY";
    }
    
    /**
    * Get Platform Specific Database 
    * Version number.
    *
    * @access    public
    * @return    string
    */
    public function version()
    {
        try 
        {
            $stmt = $this->_conn->query('SELECT service_level, fixpack_num FROM TABLE (sysproc.env_get_inst_info()) as INSTANCEINFO');
            
            $result = $stmt->fetchAll(PDO::FETCH_NUM);
            
            if (count($result))
            {
                $matches = NULL;
                if (preg_match('/((?:[0-9]{1,2}\.){1,3}[0-9]{1,2})/', $result[0][0], $matches))
                {
                    return $matches[1];
                } 
                else 
                {
                    return NULL;
                }
            }
            return null;
            
        } catch (PDOException $e) 
        {
            return NULL;
        }
    }


} // end class.


/* End of file ibm_driver.php */
/* Location: ./base/database/drivers/ibm_driver.php */