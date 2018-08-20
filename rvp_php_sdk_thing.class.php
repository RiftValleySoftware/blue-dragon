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
class RVP_PHP_SDK_Thing extends A_RVP_PHP_SDK_Data_Object {
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
            'key' => (isset($this->_object_data->key) ? trim($this->_object_data->key) : NULL),
            'description' => (isset($this->_object_data->description) ? trim($this->_object_data->description) : NULL),
            'tag2' => (isset($this->_object_data->tag2) ? $this->_object_data->tag2 : NULL),
            'tag3' => (isset($this->_object_data->tag3) ? $this->_object_data->tag3 : NULL),
            'tag4' => (isset($this->_object_data->tag4) ? $this->_object_data->tag4 : NULL),
            'tag5' => (isset($this->_object_data->tag5) ? $this->_object_data->tag5 : NULL),
            'tag6' => (isset($this->_object_data->tag6) ? $this->_object_data->tag6 : NULL),
            'tag7' => (isset($this->_object_data->tag7) ? $this->_object_data->tag7 : NULL),
            'tag8' => (isset($this->_object_data->tag8) ? $this->_object_data->tag8 : NULL),
            'tag9' => (isset($this->_object_data->tag9) ? $this->_object_data->tag9 : NULL)
            ];
        
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
      
        if (isset($in_change_record_object->things)) {
            if (isset($in_change_record_object->things->changed_things)) {
                if (is_array($in_change_record_object->things->changed_things)) {
                    if (count($in_change_record_object->things->changed_things)) {
                        foreach ($in_change_record_object->things->changed_things as $changed_thing) {
                            if ($before = $changed_thing->before) {
                                $this->_changed_states[] = new RVP_PHP_SDK_Thing($this->_sdk_object, $before->id, $before, true);
                                $ret = true;
                            }
                        }
                    }
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
            if (isset($this->_object_data) && isset($this->_object_data->things) && is_array($this->_object_data->things) && (1 == count($this->_object_data->things))) {
                $this->_object_data = $this->_object_data->things[0];
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
        parent::__construct($in_sdk_object, $in_id, $in_data, $in_detailed_data, 'things');
    }
    
    /***********************/
    /**
    This requires a detailed load.
    
    \returns the key for this thing, as a string.
     */
    function key() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->key)) {
            $ret = $this->_object_data->key;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Change/Set the key for this thing.
    
    \returns true, if the operation succeeds.
     */
    function set_key(   $in_new_string_value    ///< REQUIRED: A string, with the new key value
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->key = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed load.
    
    \returns the description for this thing, as a string.
     */
    function description() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->key)) {
            $ret = $this->_object_data->description;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    Change/Set the description for this thing.
    
    \returns true, if the operation succeeds.
     */
    function set_description(   $in_new_string_value    ///< REQUIRED: A string, with the new description value
                            ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->description = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed load.
    
    \returns the string value of Tag 2, or NULL.
     */
    function tag2() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag2;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 2.
    
    \returns true, if the operation suceeded.
     */
    function set_tag2(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag2 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 3, or NULL.
     */
    function tag3() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag3;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 3.
    
    \returns true, if the operation suceeded.
     */
    function set_tag3(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag3 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 4, or NULL.
     */
    function tag4() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag4;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 4.
    
    \returns true, if the operation suceeded.
     */
    function set_tag4(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag4 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 5, or NULL.
     */
    function tag5() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag5;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 5.
    
    \returns true, if the operation suceeded.
     */
    function set_tag5(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag5 = trim(strval($in_new_string_value));
            $ret = $this->save_data();
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns the string value of Tag 6, or NULL.
     */
    function tag6() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $ret = $this->_object_data->tag6;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the value of tag 6.
    
    \returns true, if the operation suceeded.
     */
    function set_tag6(  $in_new_string_value    ///< REQUIRED: The new string value to be set. If empty, then the tag is cleared.
                    ) {
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            $this->_object_data->tag6 = trim(strval($in_new_string_value));
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
};
