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

require_once(dirname(__FILE__).'/a_rvp_php_sdk_security_object.class.php');   // Make sure that we have the base class in place.

/****************************************************************************************************************************/
/**
 */
class RVP_PHP_SDK_Login extends A_RVP_PHP_SDK_Security_Object {
    /************************************************************************************************************************/    
    /*#################################################### INTERNAL METHODS ################################################*/
    /************************************************************************************************************************/
    /***********************/
    /**
    \returns true, if the save was successful.
     */
    protected function _save_data(  $in_args = ''   ///< OPTIONAL: Default is an empty string. This is any previous arguments. This will be appeneded to the end of the list, so it should begin with an ampersand (&), and be url-encoded.
                                ) {
        $tokens = $this->_object_data->security_tokens;
        
        $to_set = [
            'password' => (isset($this->_object_data->password) ? $this->_object_data->password : NULL),
            'tokens' => ((isset($tokens) && is_array($tokens)) ? implode(',', $tokens) : NULL)
            ];
        
        $put_args = '';
        
        foreach ($to_set as $key => $value) {
            if (isset($key) && isset($value)) {
                $put_args .= '&'.$key.'='.urlencode(trim(strval($value)));
            }
        }
        
        return parent::_save_data($put_args.$in_args);
    }
    
    /***********************/
    /**
    This is called after a successful save. It has the change record[s], and we will parse them to save the "before" object.
    
    \returns true, if the save was successful.
     */
    protected function _save_change_record( $in_change_record_object    ///< REQUIRED: The change response, as a parsed object.
                                            ) {
        $ret = false;
        if (isset($in_change_record_object->people->logins) && isset($in_change_record_object->people->logins->changed_logins) && is_array($in_change_record_object->people->logins->changed_logins) && count($in_change_record_object->people->logins->changed_logins)) {
            foreach ($in_change_record_object->people->logins->changed_logins as $changed_login) {
                if ($before = $changed_login->before) {
                    $this->_changed_states[] = new RVP_PHP_SDK_Login($this->_sdk_object, $before->id, $before, true);
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
            if (isset($this->_object_data) && isset($this->_object_data->people) && isset($this->_object_data->people->logins) && is_array($this->_object_data->people->logins) && (1 == count($this->_object_data->people->logins))) {
                $this->_object_data = $this->_object_data->people->logins[0];
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
        parent::__construct($in_sdk_object, $in_id, $in_data, $in_detailed_data, 'people/logins');
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns the current login ID as a string.
     */
    function login_id() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->login_id)) {
            $ret = $this->_object_data->login_id;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if this is login is currently logged in.
     */
    function is_logged_in() {
        $ret = false;
        
        $this->_load_data(false, true);
        if (isset($this->_object_data) && isset($this->_object_data->current_login) && $this->_object_data->current_login) {
            $ret = true;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if this is a manager login.
     */
    function is_manager() {
        $ret = false;
        
        if ($this->is_logged_in() && isset($this->_object_data->is_manager) && $this->_object_data->is_manager) {
            $ret = true;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns true, if this is a main admin login.
     */
    function is_main_admin() {
        $ret = false;
        
        if ($this->is_manager() && isset($this->_object_data->is_main_admin) && $this->_object_data->is_main_admin) {
            $ret = true;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Sets the password for this login. You cannot remove a password.
    
    \returns true, if the operation succeeded
     */
    function set_password(  $in_new_password    ///< REQUIRED: The new cleartext password.
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->password = trim(strval($in_new_password));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns an integer. The ID of any associated user object. It returns 0 if there is no associated user object.
     */
    function user_object_id() {
        $ret = 0;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->user_object_id)) {
            $ret = intval($this->_object_data->user_object_id);
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a "detailed" load.
    
    \returns an array of integer (security tokens) that comprise the "pool" for this login. It sorts the tokens, which include 1 (login) and the ID of this instance.
     */
    function security_tokens() {
        $ret = [1, $this->id()];
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->security_tokens)) {
            $ret = array_merge($ret, array_map('intval', $this->_object_data->security_tokens));
        }

        sort($ret);
        $ret = array_unique($ret);
        
        return $ret;
    }
    
    /***********************/
    /**
    Set the tokens for this ID. NOTE: For security reasons, a user is not allowed to change their own tokens. In order to set the tokens for another user, the current user must be a manager.
    The manager must "own" all the tokens they specify. If they specify tokens they don't "own," then those tokens will be ignored.
    
    AN IMPORTANT NOTE ABOUT SECURITY TOKENS
    =======================================
    
    There are a few rules with setting security tokens:
    
    - You cannot set your own security tokens. It must be done by a manager object with edit rights to your login (not user).
    
    - You must be a manager to edit security tokens.
    
    - You must "own" every single token currently in the target object, and, of course, have mod rights to that login.
    
    - Any security tokens that you wish to add must be ones that your login "owns." Ones you don't own will be ignored.
    
    - This will entirely replace all the tokens currently in the object.
    
    The object will force-reload its data after this operation, in order to reflect the new security tokens.
    
    \returns true, if the operation succeeded
     */
    function set_security_tokens(   $in_token_array ///< REQUIRED: An array of int.
                        ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && $this->_sdk_object->is_manager() && ($this->_sdk_object->current_login_id() != $this->id())) {
            $in_vals = array_map('intval', $in_token_array);
            $this->_object_data->security_tokens = $in_vals;
            $ret = $this->save_data();
            if ($ret) {
                $ret = $this->_load_data(true, true);
            } else {
                $this->_load_data(true, true);  // We reload the data in any case, but we don't record the result if the operation failed.
            }
        }
        
        return $ret;
    }
};
