<?php
defined('BASE') or exit('Access Denied!');

/**
 * Obullo Framework (c) 2009.
 *
 * PHP5 MVC Based Minimalist Software.
 *
 * @package         Obullo
 * @author          Obullo.com  
 * @subpackage      Base.libraries        
 * @copyright       Copyright (c) 2009 Ersin Guvenc.
 * @license          
 * @since           Version 1.0
 * @filesource
 */ 
 
Class VMException extends CommonException {}
 
/**
 * Validation Model.
 *
 * @package         Obullo 
 * @subpackage      Obullo.core     
 * @category        Core Model
 * @version         0.1
 */                    

Class VM extends Model {

    public $property   = array();  // User public variables, we set them in controller.
    public $errors     = array();  // Validation errors.
    public $values     = array();  // Filtered safe values.
    public $no_input   = array();  // No input fields, so save function will not save them to database.
    public $where      = FALSE;    // Check where func used or not.
    public $validation = FALSE;    // If form validation success we set it to true.
    
    /**
    * Construct
    * 
    * @return void
    */
    public function __construct()
    {               
        if( ! isset($this->settings['fields']) OR ! isset($this->settings['database'])) 
        {
            throw new VMException('Check your model it must be contain $settings[\'fields\'] and $settings[\'database\'] array.');
        }
        
        $db = $this->settings['database'];
                 
        loader::database($db);
        $this->db = this()->$db;
        
        parent::__construct();
        
        log_me('debug', "VM Class Initialized");
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Grab validator object and set validation
    * rules automatically.
    * 
    * @param  array $_GLOBALS can be $_POST, $_GET, $_FILES
    * @return boolean
    */
    public function validator($_GLOBALS = array(), $fields = array())
    {
        $validator = lib('Validator', '', TRUE);
        $validator->clear();

        if(count($_GLOBALS) == 0)
        {
            $_GLOBALS == $_REQUEST;
        }

        if(count($fields) == 0)
        {
            $fields = $this->settings['fields'];
        }

        $validator->set('_globals', $_GLOBALS);
        $validator->set('_callback_object', $this);
    
        $table = $this->settings['table'];

        foreach($fields as $key => $val)
        {
            if(is_array($this->$key) AND isset($this->{$key}['rules'])) // reset validation rules
            {   
                $val['rules'] = $this->{$key}['rules'];
                $new_value    = (isset($this->{$key}['value'])) ? $this->{$key}['value'] : FALSE;
                
                if($new_value !== FALSE) { $this->{$key} = $new_value; }
            }

            if(is_array($val))
            {
                if(isset($val['rules']) AND $val['rules'] != '')
                {
                    $validator->set_rules($key, $val['label'], $val['rules']);
                }
            }
        }
        
        if($validator->run())   // Run validation
        {            
            foreach($fields as $key => $val)  // Set filtered values
            {
                $this->values[$table][$key] = $this->set_value($key, $this->{$key});
            }
            
            $this->validation = TRUE;
            
            return TRUE;
        }
        else
        {
            foreach($fields as $key => $val)  // Set validation errors..
            { 
               if(isset($validator->_field_data[$key]['error']))
               {
                   $error = $validator->error($key, NULL, NULL);

                   if( ! empty($error))
                   {
                       $this->errors[$table][$key] = $error;
                   }
               }
               
               // Set filtered values
               $this->values[$table][$key] = $this->set_value($key, $this->{$key});
            }
            
            $this->validation = FALSE;

            return FALSE;
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Alias of validate_request();
    */
    public function validate($fields = array())
    {
        return $this->validate_request($fields);
    }
    
    // --------------------------------------------------------------------

    /**
    * Validate the native GET or POST requests.
    * No insert, No update operations.
    *
    * @return bool
    */
    public function validate_request($fields = array())
    {
        $v_data = array();   // validation fields data
        $db     = $this->settings['database'];
        $table  = $this->settings['table'];
        $id     = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';

        loader::lang('ob/vm');  // Load the language file that contains error messages

        $db_fields = $this->settings['fields'];
        
        if(count($fields) > 0)
        {
            unset($db_fields);
            
            foreach($fields as $f_key => $v)
            {
                $db_fields[$f_key] = $this->settings['fields'][$f_key];
            }
        }

        foreach($db_fields as $k => $v)
        {
            if($this->$k != '')
            {
                $v_data[$k] = $this->$k;
            }
            else
            {
                $v_data[$k] = i_get_post($k);
            }
        }
        
        return $this->validator($v_data, $db_fields);
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Check Validation is success ?
    *
    * @return boolean
    */
    public function validation()
    {
        return $this->validation;
    }
    
    // --------------------------------------------------------------------
   
    /**
    * Return validaton errors to current model.
     * 
    * @return array
    */
    public function errors($field = '')
    {
        if(isset($this->errors[$this->item('table')]))
        {
            if(isset($this->errors[$this->item('table')][$field]))
            {
                return $this->errors[$this->item('table')][$field];
            }
            
            return $this->errors[$this->item('table')];
        }
        
        return $this->errors;
    }
    
    // --------------------------------------------------------------------

    /**
    * Get one error.
    *
    * @param string $field
    */
    public function error($field)
    {
        if(isset($this->errors[$this->item('table')][$field]))
        {
            return $this->errors[$this->item('table')][$field];
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Return filtered validation values for current model.
    * 
    * @param string $field return to filtered one item's value.
    * @return array
    */
    public function values($field = '')
    {
        if(isset($this->values[$this->item('table')]))
        {
            if(isset($this->values[$this->item('table')][$field]))
            {
                return $this->values[$this->item('table')][$field];
            }
            
            return $this->values[$this->item('table')];
        }
        
        return $this->values;
    }
   
    // --------------------------------------------------------------------

    /**
    * Alias of values() function.
    * Just return to filtered one item's value.
    *
    * @param string $field
    */
    public function value($field)
    {
        if(isset($this->values[$this->item('table')][$field]))
        {
            return $this->values[$this->item('table')][$field];
        }
    }

    // --------------------------------------------------------------------

    /**
    * Set Custom VM error for field.
    *
    * @param string $field
    */
    public function set_error($field, $error)
    {
        $this->errors[$this->item('table')][$field] = $error;
    }

    // --------------------------------------------------------------------
    
    /**
    * Get Settings
    * 
    * @param mixed $item
    * @param mixed $index
    */
    public function item($item)
    {
        if(strpos($item, '['))
        {
            $index = explode('[', $item);
            $index_item = str_replace(']', '', $index[1]);
            
            return $this->settings[$index[0]][$index_item];
        }
        else
        {
            return $this->settings[$item];
        }
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Set requested property
    * 
    * @param  string $key
    * @param  mixed  $val
    * @return void
    */
    public function __set($key, $val) 
    {
        $this->property[$key] = $val;
    }
    
    // --------------------------------------------------------------------
   
    /**
    * Get requested property
    * 
    * @param  string $property_name
    * @return mixed
    */
    public function __get($key) 
    {
        if(isset($this->property[$key])) 
        {
            return($this->property[$key]);
        } 
        else 
        {
            return(NULL);
        }
    }
    
    // --------------------------------------------------------------------

    /**
    * Some times we don't want save some fields or that 
    * the fields which we haven't got in the db tables we have to validate them.
    * To overcome to this problem we use the $model->no_save(); function.
    * 
    * @param type $key
    */
    public function no_save($key)
    {
        $this->no_input[$key] = $key;
    }
    
    /**
    * Alias of no_save() function.
    * 
    * @param type $key
    */
    public function no_input($key)
    {
        return $this->no_save($key);
    }

    // --------------------------------------------------------------------

    /**
    * Do update if ID exists otherwise
    * do insert.
    * 
    * @return  FALSE | Integer
    */
    public function save()
    {
        $v_data = array();   // validation fields data
        $s_data = array();   // mysql insert / update fields data
        $db     = $this->settings['database'];
        $table  = $this->settings['table'];
        $id     = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';
    
        loader::lang('ob/vm');  // Load the language file that contains error messages

        if(count($this->no_input) > 0)
        {
            foreach($this->no_input as $k)
            {
                unset($this->settings['fields'][$k]);
            }
        }

        $has_rules = FALSE;
        foreach($this->settings['fields'] as $k => $v)
        {
            if(isset($this->settings['fields'][$k]['rules']))  // validation used or not
            {
                $has_rules = TRUE;
            }

            if($this->$k != '')
            {
                $v_data[$k] = $this->set_value($k, $this->property[$k]);

                if(isset($this->settings['fields'][$k]['func']))
                {
                    $function_string = trim($this->settings['fields'][$k]['func'], '|');

                    if(strpos($function_string, '|') > 0)
                    {
                       $functions = explode('|', $this->settings['fields'][$k]['func']);
                    }
                    else
                    {
                       $functions = array($function_string);
                    }

                    foreach($functions as $name)
                    {
                        if(is_array($this->{$k}) AND isset($this->{$k}['value'])) // set custom var rules
                        {
                            $s_data[$k] = $this->{'_'.$name}($this->{$k}['value']);
                        }
                        else
                        {
                            $s_data[$k] = $this->{'_'.$name}($v_data[$k]);
                        }
                    }
                }
                else
                {
                    if(is_array($this->{$k}) AND isset($this->{$k}['value'])) // set custom var rules
                    {
                        $s_data[$k] = $this->{$k}['value'];
                    }
                    else
                    {
                        $s_data[$k] = $v_data[$k];
                    }
                }
            }
            else
            {
                if(isset($this->property[$k])) // anyway send null data
                {
                    $s_data[$k] = '';
                }
                
                $v_data[$k] = $this->set_value($k, i_get_post($k));
            }
            
        }

        if($has_rules)  // if we have validation rules ..
        {
            $validator = $this->validator($v_data);  // do validation
        }
        else
        {
            $validator = TRUE;  // don't do validation
        }

        if($validator)  // if validation success !
        {
            if($this->$id != '')     // if isset ID do update ..
            {
                unset($s_data[$id]);
                
                if(count($this->db->ar_where) == 0 AND count($this->db->ar_wherein) == 0)
                {
                    $this->db->where($id, $this->$id);
                }

                try {

                    $this->db->transaction(); // begin the transaction

                    $this->errors[$table]['affected_rows'] = $this->db->update($table, $s_data);

                    $this->db->commit();    // commit the transaction

                } catch(Exception $e)
                {
                    $this->db->rollback();       // roll back the transaction if we fail

                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_update_fail');
                    $this->errors[$table]['transaction_error'] = $e->getMessage();

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;
                }
                
                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_update_success');

                    lib('Validator')->clear();  // reset validator data

                    return TRUE;
                } 
                elseif($this->errors[$table]['affected_rows'] == 0)
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_update_fail');

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;  
                }
            }
            else   // ELSE do insert ..
            {
                try {

                    $this->db->transaction(); // begin the transaction

                    $this->errors[$table]['affected_rows'] = $this->db->insert($table, $s_data);
                    $this->values[$table][$id] = $this->db->insert_id();  // add last inserted id.

                    $this->db->commit();    // commit the transaction

                } catch(Exception $e)
                {
                    $this->db->rollback();       // roll back the transaction if we fail

                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_insert_fail');
                    $this->errors[$table]['transaction_error'] = $e->getMessage();

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;
                }
                
                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_insert_success');

                    lib('Validator')->clear();  // reset validator data

                    return $this->values[$table][$id];
                }
                else
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_insert_fail');

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;  
                } 
          
            }
            
        }
        
        if( ! i_ajax())  // If request not AJAX, add success key for native posts.
        {
            $this->errors[$table]['success'] = 0;
        }
                
        return FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Do type casting foreach value
    * 
    * @param mixed $field
    * @param mixed $default
    * @return string
    */
    public function set_value($field, $default = '')
    {
        if( ! isset($this->settings['fields'][$field]['type']))
        {
            return $default;  // No type, return default value.
        }
        
        $type  = strtolower($this->settings['fields'][$field]['type']);
        $value = lib('Validator')->set_value($field, $default);
            
        if($type == 'string')
        {
            return form_prep($value);  
        }
        
        if($type == 'int' OR $type == 'integer')
        return (int)$value;
        
        if($type == 'float')
        return (float)$value;
    
        if($type == 'double')
        return (double)$value;
        
        if($type == 'bool' OR $type == 'boolean')
        return (boolean)$value;
        
        if($type == 'null')
        return 'NULL';
        
        return $value;   // Unknown type.
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Delete a record from current table 
    * using ID
    * 
    * @return boolean
    */
    public function delete()
    {
        $data  = array();
        $db    = $this->settings['database'];
        $table = $this->settings['table'];
        $id    = (isset($this->settings['primary_key'])) ? $this->settings['primary_key'] : 'id';
        
        loader::lang('ob/vm');  // Load the language file that contains error messages
        
        if(isset($this->property[$id]))
        {
            $data[$id] = $this->{$id};
        }
        
        $validator = $this->validate_request($data);
        
        if($validator)
        {
            if($this->$id != '')    // do delete ..
            {
                try {

                    $this->db->transaction(); // begin the transaction
                    
                    if(count($this->db->ar_where) == 0 AND count($this->db->ar_wherein) == 0)
                    {
                        $this->db->where($id, $this->$id);
                    }

                    $this->errors[$table]['affected_rows'] = $this->db->delete($table);

                    $this->db->commit();    // commit the transaction

                } catch(Exception $e)
                {
                    $this->db->rollback();       // roll back the transaction if we fail

                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_delete_fail');
                    $this->errors[$table]['transaction_error'] = $e->getMessage();

                    lib('Validator')->clear();  // reset validator data

                    return FALSE;
                }
                
                if($this->errors[$table]['affected_rows'] == 1)
                {
                    $this->errors[$table]['success'] = 1;
                    $this->errors[$table]['msg']     = lang('vm_delete_success');
                
                    lib('Validator')->clear();  // reset validator data
                    
                    return TRUE;
                } 
                elseif($this->errors[$table]['affected_rows'] == 0)
                {
                    $this->errors[$table]['success'] = 0;
                    $this->errors[$table]['msg']     = lang('vm_delete_fail');
                    
                    lib('Validator')->clear();  // reset validator data
                    
                    return FALSE;  
                }
            }
        }
        
        if( ! i_ajax())  // If request not AJAX, add success key for native posts.
        {
            $this->errors[$table]['success'] = 0;
        }
            
        return FALSE;
    }
    
    // --------------------------------------------------------------------
    
    /**
    * Access active record last_query()
    * function.
    * 
    * @param  bool $prepare
    * @return string
    */
    public function last_query($prepare = FALSE)
    {
        return $this->db->last_query($prepare);
    }

    // --------------------------------------------------------------------

    /**
    * Covert string to md5 hash
    *
    * @param  string
    */
    private function _md5($str)
    {
        return md5($str);
    }
   
}

// END Validation Model Class

/* End of file VM.php */
/* Location: ./obullo/core/VM.php */