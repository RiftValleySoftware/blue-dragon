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
class RVP_PHP_SDK_Place extends A_RVP_PHP_SDK_Data_Object {
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
            'address_venue' => (isset($this->_object_data->address_elements->venue) ? $this->_object_data->address_elements->venue : NULL),
            'address_street_address' => (isset($this->_object_data->address_elements->street_address) ? $this->_object_data->address_elements->street_address : NULL),
            'address_extra_information' => (isset($this->_object_data->address_elements->extra_information) ? $this->_object_data->address_elements->extra_information : NULL),
            'address_town' => (isset($this->_object_data->address_elements->town) ? $this->_object_data->address_elements->town : NULL),
            'address_county' => (isset($this->_object_data->address_elements->county) ? $this->_object_data->address_elements->county : NULL),
            'address_state' => (isset($this->_object_data->address_elements->state) ? $this->_object_data->address_elements->state : NULL),
            'address_postal_code' => (isset($this->_object_data->address_elements->postal_code) ? $this->_object_data->address_elements->postal_code : NULL),
            'address_nation' => (isset($this->_object_data->address_elements->nation) ? $this->_object_data->address_elements->nation : NULL),
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
      
        if (isset($in_change_record_object->places) && isset($in_change_record_object->places->changed_places) && is_array($in_change_record_object->places->changed_places) && count($in_change_record_object->places->changed_places)) {
            foreach ($in_change_record_object->places->changed_places as $changed_place) {
                if ($before = $changed_place->before) {
                    $this->_changed_states[] = new RVP_PHP_SDK_Place($this->_sdk_object, $before->id, $before, true);
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
            if (isset($this->_object_data) && isset($this->_object_data->places) && isset($this->_object_data->places->results) && is_array($this->_object_data->places->results) && (1 == count($this->_object_data->places->results))) {
                $this->_object_data = $this->_object_data->places->results[0];
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
    The basic constructor for the class.
     */
    function __construct(   $in_sdk_object,             ///< REQUIRED: The "owning" SDK object.
                            $in_id,                     ///< REQUIRED: The server ID of the object. An integer.
                            $in_data = NULL,            ///< OPTIONAL: Parsed JSON Data for the object. Default is NULL.
                            $in_detailed_data = false   ///< OPTIONAL: Ignored if $in_data is NULL. Default is false. If true, then the data sent in was in "detailed" format.
                        ) {
        parent::__construct($in_sdk_object, $in_id, $in_data, $in_detailed_data, 'places');
    }
    
    /***********************/
    /**
    This requires a load, but not a "detailed" load.
    
    \returns a string, containing the basic "readable" address of the place.
     */
    function basic_address() {
        $ret = NULL;
        
        $this->_load_data();
        
        if (isset($this->_object_data) && isset($this->_object_data->address)) {
            $ret = $this->_object_data->address;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a detailed string load.
    
    \returns an associative array of string, containing the individual address elements. This can contain:
                    - 'venue' This is the name of the venue/building.
                    - 'street_address' This is the number, street and any apartment/suite/other information.
                    - 'extra_information' This is "extra information," like "Behind the bridge entrance," etc.
                    - 'town' This is the municipality/city/town.
                    - 'county' The county or sub-province.
                    - 'state' The state or province.
                    - 'postal_code' The postal or ZIP code.
                    - 'nation' The nation.
             Not all elements need to be present. If an element is not represented, then that means that it is empty in the record.
     */
    function address_elements() {
        $ret = NULL;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data) && isset($this->_object_data->address_elements)) {
            $ret = (array)$this->_object_data->address_elements;
        }
        
        return $ret;
    }
    
    /***********************/
    /**
    This sets the address elements of the place.
    
    \returns true, if the operation suceeded.
     */
    function set_address_elements(  $in_address_elements_array  /**< REQUIRED: This is an associative array, with the new values:
                                                                                - 'venue' This is the name of the venue/building.
                                                                                - 'street_address' This is the number, street and any apartment/suite/other information.
                                                                                - 'extra_information' This is "extra information," like "Behind the bridge entrance," etc.
                                                                                - 'town' This is the municipality/city/town.
                                                                                - 'county' The county or sub-province.
                                                                                - 'state' The state or province.
                                                                                - 'postal_code' The postal or ZIP code.
                                                                                - 'nation' The nation.
                                                                                
                                                                            If an array element is not present, then that part of the address will not be affected.
                                                                            If an array element is present, but contains an empty string, then the corresponding address element will be removed.
                                                                */
                                ) {
        
        $ret = false;
        
        $this->_load_data(false, true);
        
        if (isset($this->_object_data)) {
            foreach($in_address_elements_array as $key => $value) {
                $key = strtolower(trim(strval($key)));
                $value = trim(strval($value));
                $this->_object_data->address_elements->$key = $value;
            }
            
            $ret = $this->save_data();
            
            $this->_load_data(true, true);  // Force a reload.
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
    This requires a forced detailed string load if successful.
    
    This method will force the server to look up a new longitude and latitude for the current address information.
    
    This requires that the server be configured to support the operation (has an API key, and allows the current user to perform lookups).
    
    \returns true, if the operation succeeded. This reloads the object after completion.
     */
    function geocode() {
        $ret = false;

        $result = json_decode($this->_sdk_object->put_data('/json/'.$this->_plugin_path.'/'.$this->id(), 'geocode'));
        
        if (isset($result) && $result) {
            $this->_save_change_record($result);
        }
        
        $this->_load_data(true, true);
        
        return $ret;
    }
    
    /***********************/
    /**
    This requires a forced detailed string load if successful.
    
    This method will force the server to look up a new address for the current longitude and latitude.
    
    This requires that the server be configured to support the operation (has an API key, and allows the current user to perform lookups).
    
    \returns true, if the operation succeeded. This reloads the object after completion.
     */
    function reverse_geocode() {
        $ret = false;

        $result = json_decode($this->_sdk_object->put_data('/json/'.$this->_plugin_path.'/'.$this->id(), 'reverse-geocode'));
        
        if (isset($result) && $result) {
            $this->_save_change_record($result);
        }
        
        $this->_load_data(true, true);
        
        return $ret;
    }
};
