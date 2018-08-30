<?php
/***************************************************************************************************************************/
/**
    BLUE DRAGON PHP SDK
    
    © Copyright 2018, Little Green Viper Software Development LLC.
    
    MIT License
    
    Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
    files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
    modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
    Software is furnished to do so, subject to the following conditions:

    The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

    THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
    OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT.
    IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
    CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
    
    Little Green Viper Software Development: https://littlegreenviper.com
*/
defined( 'RVP_PHP_SDK_ACCESS' ) or die ( 'Cannot Execute Directly' );	// Makes sure that this file is in the correct context.

require_once(dirname(__FILE__).'/rvp_php_sdk.class.php');   // Make sure that we have the main SDK class in place.

/****************************************************************************************************************************/
/**
 This is an abstract base class for various data objects provided by the SDK. It deals with the "top-level" common data, like the read/write tokens, name and localization code.
 
 This class will provide the history list, links to the SDK object, the basic JSON data object, as well as a bit of state.
 
 It is a generic class that serves both the data and security databases.
 */
abstract class A_RVP_PHP_SDK_Object {
    protected   $_sdk_object;       ///< This is the RVP_PHP_SDK object that "owns" this object.
    protected   $_object_id;        ///< This is the server unique ID of this object.
    protected   $_object_data;      ///< This is any data that was associated with this object (parsed JSON).
    protected   $_details;          ///< If true, then the last load was a "show details" load..
    protected   $_plugin_path;      ///< This is a string that is applied to fetches to get the object.
    protected   $_changed_states;   ///< This will contain an array of objects (of whatever class this is), that represent previous object states.
    
    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    This is the base "load some data" method. It will send a JSON GET REST request to the API in order to fetch information about this object.
    
    Once it receives the object, it JSON-decodes it, and stores it in the _object_data internal field.
    
    Subclasses will usually overload this, then apply their own filtering to the data after calling this parent method.
    
    \returns true, if it loaded the data.
     */
    protected function _load_data(  $in_force = false,      ///< OPTIONAL: Default is false. If true, then the load will happen, even if we already have the data.
                                    $in_details = false,    ///< OPTIONAL: Default is false. If true, then the load will be a "show details" load, which could bring in a great deal more data.
                                    $in_parents = false     ///< OPTIONAL: Default is false. If true, then the load will be a "show details" load, AND it will get the "parents," which can be a time-consuming operation. This will also "force" a load.
                                ) {
        $ret = false;
        
        if ($in_force || $in_parents || (NULL == $this->_object_data) || ($in_details && !$this->_details)) {
            $this->_details = $in_details;
            $fetched_data = $this->_sdk_object->fetch_data('json/'.$this->_plugin_path.'/'.$this->_object_id, $in_details ? 'show_details'.($in_parents ? '&show_parents' : '') : NULL);
            $this->_object_data = (NULL != $fetched_data) ? json_decode($fetched_data) : NULL;
            $ret = (NULL != $this->_object_data);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This actually sends the data through the API to the server, and returns the response object.
    
    \returns the JSON change object. NULL if not successful.
     */
    protected function _save_data(  $in_args = '',              ///< OPTIONAL: Default is an empty string. This is any previous arguments. This will be appeneded to the end of the list, so it should begin with an ampersand (&), and be url-encoded.
                                    $in_payload = NULL,         ///< OPTIONAL: Any payload to be asociated with this object. Must be an associative array (['data' => data, 'type' => MIME Type string]).
                                    $in_new_child_ids = NULL    ///< IGNORED. Just here to suppress PHP warnings.
                                ) {
        $ret = NULL;
        $put_args = '';
        
        $name = isset($this->_object_data->name) ? $this->_object_data->name : NULL;
        $lang = isset($this->_object_data->lang) ? $this->_object_data->lang : NULL;
        $read_token = isset($this->_object_data->read_token) ? intval($this->_object_data->read_token) : NULL;
        $write_token = isset($this->_object_data->write_token) ? ((0 < intval($this->_object_data->write_token)) ? intval($this->_object_data->write_token) : $this->_sdk_object->my_info()['login']->id()) : NULL;
        
        if (isset($name)) {
            $put_args .= '&name='.urlencode($name);
        }
        
        if (isset($lang)) {
            $put_args .= '&lang='.urlencode($lang);
        }
        
        if (isset($read_token)) {
            $put_args .= '&read_token='.intval($read_token);
        }
        
        if (isset($write_token)) {
            $put_args .= '&write_token='.intval($write_token);
        }
        
        $result = json_decode($this->_sdk_object->put_data('/json/'.$this->_plugin_path.'/'.$this->id(), $put_args.$in_args, $in_payload));
        
        return $result;
    }
    
    /***********************/
    /**
    This is called after a successful save. It has the change record[s], and the subclass should take care of parsing that record to save in the object's change record.
    
    It's important that subclasses apply their own parsing, as the response data is different, between people, places and things.
    
    \returns true, if the save was successful.
     */
    protected abstract function _save_change_record(    $in_change_record_object    ///< REQUIRED: The change response, as a parsed object.
                                                    );
    
    /************************************************************************************************************************/    
    /*#################################################### PUBLIC METHODS ##################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    The basic constructor for the class. You have the option of "priming" the object with information.
     */
    function __construct(   $in_sdk_object,                 ///< REQUIRED: The "owning" SDK object.
                            $in_id,                         ///< REQUIRED: The server ID of the object. An integer.
                            $in_data = NULL,                ///< OPTIONAL: Parsed JSON Data for the object. Default is NULL.
                            $in_detailed_data = false,      ///< OPTIONAL: Ignored if $in_data is NULL. Default is false. If true, then the data sent in was in "detailed" format.
                            $in_plugin_path = 'baseline'    ///< OPTIONAL: This is a path that is added to the server, to fetch data. Default is "baseline."
                        ) {
        $this->_object_id = $in_id;
        $this->_object_data = $in_data;
        $this->_details = (NULL != $in_data) ? $in_detailed_data : false;
        $this->_plugin_path = $in_plugin_path;
        $this->_changed_states = [];
        $this->set_sdk_object($in_sdk_object);
    }
    
    /***********************/
    /**
    Simple accessor to set the "owning" SDK object.
     */
    function set_sdk_object(    $in_sdk_object  ///< REQUIRED: The "owning" SDK object.
                            ) {
        $this->_sdk_object = $in_sdk_object;    // This is the RVP_PHP_SDK object that "owns" this user.
    }
    
    /***********************/
    /**
    \returns an array of instances, representing the "before" state of this object, prior to any changes made. It should be noted that the lifetime of these changes are dependent on the lifetime of this instance.
     */
    function changes() {
        return isset($this->_changed_states) ? $this->_changed_states : [];
    }
    
    /***********************/
    /**
    \returns true, if the save was successful.
     */
    function save_data() {
        $ret = false;
        
        $ret = $this->_save_change_record($this->_save_data());
        
        return $ret;
    }
    
    /***********************/
    /**
    \returns the integer ID of the object; unique in the object's database.
     */
    function id() {
        return $this->_object_id;
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns the string name of the object. This is the generic "object_name" column that all records have.
     */
    function name() {
        $ret = NULL;
        
        $this->_load_data();

        if (isset($this->_object_data) && isset($this->_object_data->name)) {
            $ret = $this->_object_data->name;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the name of the object.
    
    \returns true, if the save worked.
     */
    function set_name(  $in_new_value   ///< REQUIRED: A new value for the name.
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);

        if (isset($this->_object_data)) {
            $this->_object_data->name = $in_new_value;
            
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns the string for the object's "lang" (language code) field.
     */
    function lang() {
        $ret = NULL;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->lang)) {
            $ret = $this->_object_data->lang;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the language ID of the object.
    
    \returns true, if the save worked.
     */
    function set_lang(  $in_new_value   ///< REQUIRED: A new value for the language ID.
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);

        if (isset($this->_object_data)) {
            $this->_object_data->lang = $in_new_value;
            
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns an associative array ('read' => integer, 'write' => integer), with the tokens for the object. The tokens will only be available if they are visible to the current user, or NULL, if there are no tokens (should never happen).
     */
    function object_access() {
        $ret = NULL;
        $read_token = NULL;
        $write_token = NULL;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->read_token)) {
            $read_token = intval($this->_object_data->read_token);
        }
        
        if (isset($this->_object_data) && isset($this->_object_data->write_token)) {
            $write_token = intval($this->_object_data->write_token);
        }
        
        if ($read_token || $write_token) {
            $ret = [];
            
            if ($read_token) {
                $ret['read'] = $read_token;
            }
            
            if ($write_token) {
                $ret['write'] = $write_token;
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the read and write tokens of the object.
    
    The current login must have at least read access to each of the tokens. If a token is NULL, then the token is not changed.
    
    \returns true, if the save worked.
     */
    function set_object_access( $in_new_read = NULL,    ///< OPTIONAL: A new read token. Default is NULL (no change).
                                $in_new_write = NULL    ///< OPTIONAL: A new write token. Default is NULL (no change).
                                ) {
        $ret = false;
        
        $this->_load_data(false, true);

        if (isset($this->_object_data)) {
            $this->_object_data->read_token = isset($in_new_read) ? intval($in_new_read) : $this->_object_data->read_token;
            $this->_object_data->write_token = isset($in_new_write) ? intval($in_new_write) : $this->_object_data->write_token;
            
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns true, if the current login can write/modify this object.
     */
    function writeable() {
        $ret = false;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->writeable) && $this->_object_data->writeable) {
            $ret = true;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns the last access date, as a timedate integer.
     */
    function last_access() {
        $ret = NULL;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->last_access)) {
            $ret = strtotime($this->_object_data->last_access);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This forces at least a detailed load.
    
    This reloads the information, refreshing the object, if necessary.
    
    \returns true, if successful.
     */
    function force_reload(  $in_parents = false ///< OPTIONAL: Default is false. If true, then the parent info is also loaded.
                        ) {
        return $this->_load_data(true, true, $in_parents);
    }
};
