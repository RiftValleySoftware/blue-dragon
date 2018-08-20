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

require_once(dirname(__FILE__).'/a_rvp_php_sdk_data_object.class.php');   // Make sure that we have the base class in place.

/****************************************************************************************************************************/
/**
 */
class RVP_PHP_SDK_User extends A_RVP_PHP_SDK_Data_Object {
    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    \returns true, if the save was successful.
     */
    protected function _save_data(  $in_args = ''   ///< OPTIONAL: Default is an empty string. This is any previous arguments. This will be appeneded to the end of the list, so it should begin with an ampersand (&), and be url-encoded.
                                ) {
        $to_set = [
            'surname' => (isset($this->_object_data->surname) ? $this->_object_data->surname : NULL),
            'middle_name' => (isset($this->_object_data->middle_name) ? $this->_object_data->middle_name : NULL),
            'given_name' => (isset($this->_object_data->given_name) ? $this->_object_data->given_name : NULL),
            'nickname' => (isset($this->_object_data->nickname) ? $this->_object_data->nickname : NULL),
            'prefix' => (isset($this->_object_data->prefix) ? $this->_object_data->prefix : NULL),
            'suffix' => (isset($this->_object_data->suffix) ? $this->_object_data->suffix : NULL),
            'tag7' => (isset($this->_object_data->tag7) ? $this->_object_data->tag7 : NULL),
            'tag8' => (isset($this->_object_data->tag8) ? $this->_object_data->tag8 : NULL),
            'tag9' => (isset($this->_object_data->tag9) ? $this->_object_data->tag9 : NULL),
            ];
        
        // Only God can change login IDs.
        if ($this->_sdk_object->is_main_admin()) {
            if (isset($this->_object_data->associated_login_id)) {
                $to_set['login_id'] = intval($this->_object_data->associated_login_id);
            }
        }
        
        $put_args = '';
        
        foreach ($to_set as $key => $value) {
            if (isset($key) && isset($value)) {
                $put_args .= '&'.$key.'='.urlencode(trim(strval($value)));
            }
        }
        
        $ret = parent::_save_data($put_args.$in_args, NULL, NULL);
        
        return $ret;
    }
    
    /***********************/
    /**
    This is called after a successful save. It has the change record[s], and we will parse them to save the "before" object.
    
    \returns true, if the save was successful.
     */
    protected function _save_change_record( $in_change_record_object    ///< REQUIRED: The change response, as a parsed object.
                                            ) {
        $ret = false;
        if (isset($in_change_record_object->people->people) && isset($in_change_record_object->people->people->changed_users) && is_array($in_change_record_object->people->people->changed_users) && count($in_change_record_object->people->people->changed_users)) {
            foreach ($in_change_record_object->people->people->changed_users as $changed_person) {
                if ($before = $changed_person->before) {
                    $this->_changed_states[] = new RVP_PHP_SDK_User($this->_sdk_object, $before->id, $before, true);
                    $ret = true;
                }
            }
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This is the specific "load some data" method. It will send a GET REST request to the API in order to fetch information about this object.
    
    \returns true, if it loaded the data.
     */
    protected function _load_data(  $in_force = false,      ///< OPTIONAL: If true (default is false), then the load will happen, even if we already have the data.
                                    $in_details = false,    ///< OPTIONAL: Default is false. If true, then the load will be a "show details" load, which could bring in a great deal more data.
                                    $in_parents = false     ///< OPTIONAL: Default is false. If true, then the load will be a "show details" load, AND it will get the "parents," which can be a time-consuming operation. This will also "force" a load.
                                ) {
        $ret = parent::_load_data($in_force, $in_details, $in_parents);
        
        if ($ret) {
            if (isset($this->_object_data) && isset($this->_object_data->people) && isset($this->_object_data->people->people) && is_array($this->_object_data->people->people) && (1 == count($this->_object_data->people->people))) {
                $this->_object_data = $this->_object_data->people->people[0];
            } else {
                $this->_object_data = NULL;
                $this->_details = false;
            }
        }
        
        return $ret;
    }

    /************************************************************************************************************************/    
    /*#################################################### PUBLIC METHODS ##################################################*/
    /************************************************************************************************************************/
    
    /***********************/
    /**
     */
    function __construct(   $in_sdk_object,             ///< REQUIRED: The "owning" SDK object.
                            $in_id,                     ///< REQUIRED: The server ID of the object. An integer.
                            $in_data = NULL,            ///< OPTIONAL: Parsed JSON Data for the object. Default is NULL.
                            $in_detailed_data = false   ///< OPTIONAL: Ignored if $in_data is NULL. Default is false. If true, then the data sent in was in "detailed" format.
                        ) {
        parent::__construct($in_sdk_object, $in_id, $in_data, $in_detailed_data, 'people/people');
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns an array of integer (security tokens) that comprise the "pool" for this user (assuming it has a login).
     */
    function security_tokens() {
        $ret = [];
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->associated_login) && isset($this->_object_data->associated_login->security_tokens)) {
            $ret = $this->_object_data->associated_login->security_tokens;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user surname.
     */
    function surname() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->surname)) {
            $ret = trim($this->_object_data->surname);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_surname(   $in_new_string_value    ///< REQUIRED: The new value
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->surname = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user middle name.
     */
    function middle_name() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->middle_name)) {
            $ret = trim($this->_object_data->middle_name);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_middle_name(   $in_new_string_value    ///< REQUIRED: The new value
                            ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->middle_name = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user given (first) name.
     */
    function given_name() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->given_name)) {
            $ret = trim($this->_object_data->given_name);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_given_name(    $in_new_string_value    ///< REQUIRED: The new value
                            ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->given_name = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user nickname.
     */
    function nickname() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->nickname)) {
            $ret = trim($this->_object_data->nickname);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_nickname(  $in_new_string_value    ///< REQUIRED: The new value
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->nickname = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user prefix.
     */
    function prefix() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->prefix)) {
            $ret = trim($this->_object_data->prefix);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_prefix(    $in_new_string_value    ///< REQUIRED: The new value
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->prefix = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns a string, with the user suffix.
     */
    function suffix() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->suffix)) {
            $ret = trim($this->_object_data->suffix);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if the operation succeeded.
     */
    function set_suffix(    $in_new_string_value    ///< REQUIRED: The new value
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->suffix = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 7, or NULL.
     */
    function tag7() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag7;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 7.
    
    \returns true, if the operation suceeded.
     */
    function set_tag7(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag7 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 8, or NULL.
     */
    function tag8() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag8;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 8.
    
    \returns true, if the operation suceeded.
     */
    function set_tag8(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag8 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 9, or NULL.
     */
    function tag9() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag9;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 9.
    
    \returns true, if the operation suceeded.
     */
    function set_tag9(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag9 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns an integer, with the associated login ID, or NULL.
     */
    function associated_login_id() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = intval($this->_object_data->associated_login_id);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of the associated login ID.
    
    \returns true, if the operation suceeded.
     */
    function set_associated_login_id(   $in_new_integer_value   ///< REQUIRED: The new login ID value.
                                    ) {
        $ret = false;
        
        if ($this->_sdk_object->is_main_admin()) {  // Only God can change login IDs.
            $login_object = $this->_sdk_object->get_login_info($in_new_integer_value);
        
            if ($login_object instanceof RVP_PHP_SDK_Login) {
                if (0 == $login_object->user_object_id()) { // We can only associate a login that is not associated with another user.
                    $this->_load_data(false, true);
        
                    if (isset($this->_object_data)) {
                        $this->_object_data->associated_login_id = intval($in_new_integer_value);
                        $ret = $this->save_data();
                    }
                } else {
                    $this->_sdk_object->set_error(_ERR_LOGIN_HAS_USER__);
                }
            } else {
                $this->_sdk_object->set_error(_ERR_INVALID_PARAMETERS__);
            }
        } else {
            $this->_sdk_object->set_error(_ERR_NOT_AUTHORIZED__);
        }
        
        return $ret;
    }
};
